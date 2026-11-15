<?php
/**
 * Read-side helpers the theme uses. Kept here so the theme only presents data.
 */

defined( 'ABSPATH' ) || exit;

/* Titles use "·" as a separator; keep it out of URLs (…-match-3-of-5, not …-%c2%b7-…). */
add_filter( 'sanitize_title', function ( $title ) {
	return str_replace( array( '·', '×', '–', '—' ), array( ' ', 'x', '-', '-' ), $title );
}, 5 );

/**
 * Split pipe-separated lines into arrays: "a | b | c" => ['a','b','c'].
 */
function hs_parse_lines( $text, $min_cols = 1 ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$cols = array_map( 'trim', explode( '|', $line ) );
		$cols = array_pad( $cols, $min_cols, '' );
		$out[] = $cols;
	}
	return $out;
}

/** 1 => 1st, 22 => 22nd. Non-numbers pass through. */
function hs_ordinal( $n ) {
	if ( ! is_numeric( $n ) ) {
		return (string) $n;
	}
	$n = (int) $n;
	$s = array( 'th', 'st', 'nd', 'rd' );
	$v = $n % 100;
	return $n . ( $s[ ( $v - 20 ) % 10 ] ?? $s[ $v ] ?? $s[0] );
}

function hs_today() {
	return wp_date( 'Y-m-d' );
}

/**
 * Upcoming fixtures (today onwards), soonest first.
 *
 * @return WP_Post[]
 */
function hs_get_upcoming_fixtures( $limit = 6, $args = array() ) {
	return get_posts( array_merge( array(
		'post_type'      => 'hs_fixture',
		'posts_per_page' => $limit,
		'meta_key'       => 'hs_date',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'meta_query'     => array(
			'relation' => 'OR',
			array( 'key' => 'hs_date', 'value' => hs_today(), 'compare' => '>=', 'type' => 'DATE' ),
			array( 'key' => 'hs_end_date', 'value' => hs_today(), 'compare' => '>=', 'type' => 'DATE' ),
		),
	), $args ) );
}

/**
 * The next fixture "slot" for the homepage: every fixture on the next racing
 * day, or across the whole weekend if the next date is a Saturday or Sunday.
 *
 * @return array{label:string, fixtures:WP_Post[]}|null
 */
function hs_get_next_fixture_group() {
	$upcoming = hs_get_upcoming_fixtures( 20 );
	if ( ! $upcoming ) {
		return null;
	}
	$first = get_post_meta( $upcoming[0]->ID, 'hs_date', true );
	$ts    = strtotime( $first );
	$dow   = (int) gmdate( 'N', $ts ); // 6 = Sat, 7 = Sun.
	if ( $dow >= 6 ) {
		$sat   = 6 === $dow ? $ts : strtotime( '-1 day', $ts );
		$start = gmdate( 'Y-m-d', $sat );
		$end   = gmdate( 'Y-m-d', strtotime( '+1 day', $sat ) );
	} else {
		$start = $end = $first;
	}
	$group = array_values( array_filter( $upcoming, function ( $p ) use ( $start, $end ) {
		$d = get_post_meta( $p->ID, 'hs_date', true );
		return $d >= $start && $d <= $end;
	} ) );
	if ( $start === $end || count( array_unique( array_map( fn( $p ) => get_post_meta( $p->ID, 'hs_date', true ), $group ) ) ) === 1 ) {
		$label = wp_date( 'D j M', strtotime( $first ) );
	} else {
		$label = 'Weekend of ' . wp_date( 'j M', strtotime( $start ) );
	}
	return array( 'label' => $label, 'fixtures' => $group );
}

/**
 * Everything the theme needs to print one fixture.
 */
function hs_fixture_view( $post ) {
	$id   = $post->ID;
	$date = get_post_meta( $id, 'hs_date', true );
	$ts   = $date ? strtotime( $date ) : 0;
	$url  = get_post_meta( $id, 'hs_entry_url', true );
	$plat = get_post_meta( $id, 'hs_entry_platform', true );
	$info = get_post_meta( $id, 'hs_info_url', true );
	$types = get_the_terms( $id, 'hs_fixture_type' );
	$type  = $types && ! is_wp_error( $types ) ? $types[0]->name : '';
	$venue = get_post_meta( $id, 'hs_venue', true );
	$det   = get_post_meta( $id, 'hs_details', true );
	if ( $url ) {
		$cta = $plat && 'Other' !== $plat ? 'Enter · ' . $plat : 'Enter online';
		$cta_url = $url;
		$external = true;
	} else {
		$cta = 'Team info';
		$cta_url = $info ?: get_permalink( $post );
		$external = false;
	}
	return array(
		'id'         => $id,
		'title'      => get_the_title( $post ),
		'date'       => $date,
		'day'        => $ts ? wp_date( 'j', $ts ) : '',
		'dow'        => $ts ? strtoupper( wp_date( 'D', $ts ) ) : '',
		'mon'        => $ts ? strtoupper( wp_date( 'M', $ts ) ) : '',
		'month_key'  => $ts ? wp_date( 'Y-m', $ts ) : '',
		'month_name' => $ts ? wp_date( 'F Y', $ts ) : '',
		'venue'      => $venue,
		'meta'       => implode( ' · ', array_filter( array( $venue, $det ) ) ),
		'type'       => $type,
		'type_slug'  => $types && ! is_wp_error( $types ) ? $types[0]->slug : '',
		'discipline' => get_post_meta( $id, 'hs_discipline', true ) ?: 'off',
		'cta'        => $cta,
		'cta_url'    => $cta_url,
		'external'   => $external,
		'permalink'  => get_permalink( $post ),
	);
}

/**
 * Leagues for the Results page.
 *
 * @param string $season winter|summer
 * @param bool   $current true = current season, false = archived seasons
 * @return WP_Post[]
 */
function hs_get_leagues( $season = '', $current = true ) {
	$mq = array( array( 'key' => 'hs_current', 'value' => '1', 'compare' => $current ? '=' : '!=' ) );
	if ( $season ) {
		$mq[] = array( 'key' => 'hs_season_type', 'value' => $season );
	}
	return get_posts( array(
		'post_type'      => 'hs_league',
		'posts_per_page' => -1,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'meta_query'     => $mq,
	) );
}

/** Rounds of a league in order. */
function hs_get_rounds( $league_id ) {
	return get_posts( array(
		'post_type'      => 'hs_round',
		'post_parent'    => $league_id,
		'posts_per_page' => -1,
		'meta_key'       => 'hs_round_no',
		'orderby'        => 'meta_value_num',
		'order'          => 'ASC',
	) );
}

/**
 * Turn a round into display data: status, rows with medal colours.
 */
function hs_round_view( $round, $is_next = false ) {
	$id    = $round->ID;
	$rows  = hs_parse_lines( get_post_meta( $id, 'hs_rows', true ), 5 );
	$date  = get_post_meta( $id, 'hs_date', true );
	$past  = $date && $date < hs_today();
	$canc  = '1' === get_post_meta( $id, 'hs_cancelled', true );
	if ( $canc ) {
		$status = 'Cancelled';
	} elseif ( $rows ) {
		$status = 'Results in';
	} elseif ( $past ) {
		$status = 'Completed';
	} elseif ( $is_next ) {
		$status = 'Next up';
	} else {
		$status = 'Upcoming';
	}
	$out_rows = array();
	foreach ( $rows as $r ) {
		$pos   = $r[1];
		$num   = is_numeric( $pos ) ? (int) $pos : 0;
		$medal = 1 === $num ? 'gold' : ( $num && $num <= 3 ? 'podium' : 'plain' );
		$out_rows[] = array(
			'race'  => $r[0],
			'pos'   => $num ? hs_ordinal( $num ) : ( '' === $pos ? '—' : $pos ),
			'of'    => $r[2],
			'score' => $r[3],
			'first' => $r[4],
			'medal' => $medal,
		);
	}
	$no = get_post_meta( $id, 'hs_round_no', true );
	return array(
		'id'        => $id,
		'label'     => get_the_title( $round ) ?: 'Round ' . $no,
		'detail'    => implode( ' · ', array_filter( array( get_post_meta( $id, 'hs_venue', true ), $date ? wp_date( 'D j M', strtotime( $date ) ) : '' ) ) ),
		'status'    => $status,
		'rows'      => $out_rows,
		'final'     => 'final' === get_post_meta( $id, 'hs_status', true ),
		'checked'   => get_post_meta( $id, 'hs_last_import', true ),
		'cancelled' => $canc,
		'has_rows'  => (bool) $out_rows,
		'past'      => $past,
	);
}

/**
 * Most recent round that has results, across current leagues (for the homepage).
 */
function hs_get_latest_result() {
	$rounds = get_posts( array(
		'post_type'      => 'hs_round',
		'posts_per_page' => 10,
		'meta_key'       => 'hs_date',
		'orderby'        => 'meta_value',
		'order'          => 'DESC',
		'meta_query'     => array( array( 'key' => 'hs_date', 'value' => hs_today(), 'compare' => '<=', 'type' => 'DATE' ) ),
	) );
	foreach ( $rounds as $r ) {
		$rows = hs_parse_lines( get_post_meta( $r->ID, 'hs_rows', true ), 5 );
		if ( ! $rows ) {
			continue;
		}
		$league = get_post( $r->post_parent );
		// Headline: the best team placings and any race winners.
		$wins = array();
		$team_wins = array();
		foreach ( $rows as $row ) {
			if ( preg_match( '/^(.+?) · 1st\b/u', $row[4], $m ) ) {
				$wins[] = trim( $m[1] );
			}
			if ( '1' === $row[1] ) {
				$team_wins[] = preg_replace( '/ · Div.*$/u', '', $row[0] );
			}
		}
		$wins = array_values( array_unique( $wins ) );
		return array(
			'league'    => $league ? ( get_post_meta( $league->ID, 'hs_display_name', true ) ?: $league->post_title ) : '',
			'round'     => get_the_title( $r ),
			'venue'     => get_post_meta( $r->ID, 'hs_venue', true ),
			'wins'      => $wins,
			'team_wins' => $team_wins,
			'url'       => hs_results_page_url(),
		);
	}
	return null;
}

/** URL of the page using the Results template, or /results/. */
function hs_results_page_url() {
	$pages = get_pages( array( 'meta_key' => '_wp_page_template', 'meta_value' => 'page-templates/results.php', 'number' => 1 ) );
	return $pages ? get_permalink( $pages[0] ) : home_url( '/results/' );
}

/** People for the Volunteers page, grouped as the page needs them. */
function hs_get_volunteers( $role_slug ) {
	return get_posts( array(
		'post_type'      => 'hs_volunteer',
		'posts_per_page' => -1,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'tax_query'      => array( array( 'taxonomy' => 'hs_volunteer_role', 'field' => 'slug', 'terms' => $role_slug ) ),
	) );
}

function hs_initials( $name ) {
	if ( 0 === stripos( $name, 'vacant' ) ) {
		return '?';
	}
	$clean = trim( preg_replace( '/\(.*?\)/', '', $name ) );
	$parts = preg_split( '/\s+/', $clean );
	$first = mb_substr( $parts[0], 0, 1 );
	$last  = count( $parts ) > 1 ? mb_substr( end( $parts ), 0, 1 ) : '';
	return mb_strtoupper( $first . $last );
}
