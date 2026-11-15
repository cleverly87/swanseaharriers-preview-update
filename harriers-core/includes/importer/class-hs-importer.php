<?php
/**
 * Automatic results import.
 *
 * Every 6 hours WP-Cron looks for rounds that:
 *   - belong to a league whose source is Gwent or West Glam,
 *   - have "Import results automatically" ticked,
 *   - took place in the last 21 days, and
 *   - are not yet marked Final.
 * It fetches the league's pages, finds our teams, and writes the results and
 * the league's standings panel. Results are never wiped: if the league site is
 * down or our team isn't listed yet, the round is left as it was.
 *
 * Gwent publishes a first issue on race weekend, corrections up to the Tuesday,
 * and later marks pages "FINAL RESULTS"; the round stays Provisional until then.
 *
 * Tip for reliable timing: WP-Cron only runs when someone visits the site. On a
 * quiet site, add a real server cron hitting wp-cron.php (see DEVELOPER.md).
 */

defined( 'ABSPATH' ) || exit;

class HS_Importer {

	const HOOK = 'hs_results_import';
	const LOG  = 'hs_import_log';

	public static function init() {
		add_filter( 'cron_schedules', function ( $s ) {
			$s['hs_six_hours'] = array( 'interval' => 6 * HOUR_IN_SECONDS, 'display' => 'Every 6 hours' );
			return $s;
		} );
		add_action( self::HOOK, array( __CLASS__, 'run_due' ) );
		add_action( 'init', function () {
			if ( ! wp_next_scheduled( self::HOOK ) ) {
				self::schedule();
			}
		} );
	}

	public static function schedule() {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + 300, 'hs_six_hours', self::HOOK );
		}
	}

	public static function unschedule() {
		wp_clear_scheduled_hook( self::HOOK );
	}

	/** Rounds the cron should look at right now. */
	public static function due_rounds() {
		$from = wp_date( 'Y-m-d', strtotime( '-21 days' ) );
		$rounds = get_posts( array(
			'post_type'      => 'hs_round',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array( 'key' => 'hs_date', 'value' => array( $from, hs_today() ), 'compare' => 'BETWEEN', 'type' => 'DATE' ),
			),
		) );
		return array_values( array_filter( $rounds, function ( $r ) {
			if ( '0' === get_post_meta( $r->ID, 'hs_auto', true ) || 'final' === get_post_meta( $r->ID, 'hs_status', true ) || '1' === get_post_meta( $r->ID, 'hs_cancelled', true ) ) {
				return false;
			}
			$src = get_post_meta( $r->post_parent, 'hs_source', true );
			return in_array( $src, array( 'gwent', 'westglam' ), true );
		} ) );
	}

	public static function run_due() {
		foreach ( self::due_rounds() as $r ) {
			self::run_round( $r->ID );
		}
	}

	/**
	 * Import one round. Returns a one-line message for the admin screen.
	 *
	 * @param int           $round_id
	 * @param callable|null $fetch    Override the HTTP fetcher (used by tests).
	 */
	public static function run_round( $round_id, $fetch = null ) {
		$round  = get_post( $round_id );
		$league = $round ? get_post( $round->post_parent ) : null;
		if ( ! $round || ! $league ) {
			return self::log( $round_id, 'Round has no league set.' );
		}
		$fetch  = $fetch ?: array( __CLASS__, 'http_get' );
		$source = get_post_meta( $league->ID, 'hs_source', true );
		$club   = get_post_meta( $league->ID, 'hs_club_name', true ) ?: 'Swansea Harriers';
		$no     = (int) get_post_meta( $round_id, 'hs_round_no', true );
		$total  = max( $no, count( hs_get_rounds( $league->ID ) ) );
		$stamp  = wp_date( 'j M Y, H:i' );

		if ( 'gwent' === $source ) {
			$base   = get_post_meta( $league->ID, 'hs_gwent_base', true ) ?: 'https://www.gwent-league.org.uk';
			$folder = get_post_meta( $league->ID, 'hs_gwent_folder', true );
			$code   = get_post_meta( $league->ID, 'hs_gwent_code', true );
			if ( ! $folder || ! $code || ! $no ) {
				return self::log( $round_id, 'Gwent settings incomplete (folder, season code or round number).' );
			}
			$parser = new HS_Gwent_Parser( $fetch, $club );
			$res = $parser->parse( $base, $folder, $code, $no, $total );
			$status = $res['final'] ? 'final' : 'provisional';
		} elseif ( 'westglam' === $source ) {
			$venue = get_post_meta( $round_id, 'hs_source_url', true );
			$male  = get_post_meta( $league->ID, 'hs_wg_male_url', true );
			$fem   = get_post_meta( $league->ID, 'hs_wg_female_url', true );
			$parser = new HS_WestGlam_Parser( $club );
			$res = $parser->parse(
				$venue ? call_user_func( $fetch, $venue ) : null,
				$male ? call_user_func( $fetch, $male ) : null,
				$fem ? call_user_func( $fetch, $fem ) : null,
				$no,
				$total
			);
			// West Glam doesn't label results final; treat as final 7 days after the race.
			$date = get_post_meta( $round_id, 'hs_date', true );
			$status = $res['rows'] && $date && strtotime( $date ) < strtotime( '-7 days' ) ? 'final' : 'provisional';
		} else {
			return self::log( $round_id, 'This league is typed in by hand.' );
		}

		update_post_meta( $round_id, 'hs_last_import', $stamp . ( $res['rows'] ? '' : ' (nothing published yet)' ) );
		if ( ! $res['rows'] ) {
			return self::log( $round_id, 'No results found yet. ' . implode( '; ', array_slice( $parser->log, 0, 3 ) ) );
		}
		$lines = array_map( fn( $r ) => implode( ' | ', array_map( fn( $c ) => str_replace( '|', '/', (string) $c ), $r ) ), $res['rows'] );
		update_post_meta( $round_id, 'hs_rows', implode( "\n", $lines ) );
		update_post_meta( $round_id, 'hs_status', $status );
		if ( $res['standings'] ) {
			$st = array_map( fn( $r ) => implode( ' | ', $r ), $res['standings'] );
			update_post_meta( $league->ID, 'hs_standings', implode( "\n", $st ) );
		}
		return self::log( $round_id, sprintf( 'Imported %d races (%s).', count( $res['rows'] ), $status ) );
	}

	/** Polite HTTP GET returning UTF-8 text or null. */
	/**
	 * Only these sites (and their www. versions) are ever fetched. Stops a
	 * mistyped or malicious address in a round making the server request
	 * anything else. Add hosts with the 'hs_importer_allowed_hosts' filter.
	 */
	public static function allowed_hosts() {
		return (array) apply_filters( 'hs_importer_allowed_hosts', array( 'gwent-league.org.uk', 'westglamleague.co.uk' ) );
	}

	public static function is_allowed_url( $url ) {
		$parts = wp_parse_url( $url );
		if ( empty( $parts['host'] ) || ! in_array( strtolower( $parts['scheme'] ?? '' ), array( 'https', 'http' ), true ) ) {
			return false;
		}
		$host = preg_replace( '/^www\./', '', strtolower( $parts['host'] ) );
		return in_array( $host, self::allowed_hosts(), true );
	}

	public static function http_get( $url ) {
		if ( ! self::is_allowed_url( $url ) ) {
			return null;
		}
		// wp_safe_remote_get also refuses local/private network addresses and unsafe redirects.
		$resp = wp_safe_remote_get( $url, array(
			'redirection' => 2,
			'timeout'    => 20,
			'user-agent' => 'SwanseaHarriersResults/1.0 (+' . home_url( '/' ) . ')',
		) );
		if ( is_wp_error( $resp ) || 200 !== (int) wp_remote_retrieve_response_code( $resp ) ) {
			return null;
		}
		$body = wp_remote_retrieve_body( $resp );
		if ( '' === $body ) {
			return null;
		}
		if ( ! mb_check_encoding( $body, 'UTF-8' ) ) {
			$body = mb_convert_encoding( $body, 'UTF-8', 'Windows-1252' );
		}
		return $body;
	}

	private static function log( $round_id, $msg ) {
		$log = get_option( self::LOG, array() );
		array_unshift( $log, array( 'time' => wp_date( 'j M H:i' ), 'round' => $round_id ? get_the_title( $round_id ) . ' — ' . get_the_title( wp_get_post_parent_id( $round_id ) ) : '', 'msg' => $msg ) );
		update_option( self::LOG, array_slice( $log, 0, 50 ), false );
		return $msg;
	}
}

HS_Importer::init();
