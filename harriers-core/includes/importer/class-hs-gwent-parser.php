<?php
/**
 * Reads one Gwent League fixture's results from gwent-league.org.uk.
 *
 * Their files follow a fixed pattern: {base}/{folder}/{race}{yy}{match}{t|i|d}.htm
 *   t = team results, i = individual results, d = senior divisions
 *   e.g. /2026/sm262t.htm = senior men, 2026/27 season, match 2, teams.
 *
 * Two formats exist:
 *   - Juniors (u12g … u20b): preformatted text. After match 1 the team list is in
 *     OVERALL order with two numbers (match score, cumulative score), so the match
 *     position has to be worked out by ranking the match scores.
 *   - Seniors and masters: HTML tables. Columns Pos | Team | Pts | Cum Pts | Cum Pos.
 *
 * Pure PHP with an injected fetcher so it can be tested against saved pages.
 */

defined( 'ABSPATH' ) || exit;

class HS_Gwent_Parser {

	const JUNIOR = array(
		'u12g' => 'U12 Girls', 'u12b' => 'U12 Boys',
		'u14g' => 'U14 Girls', 'u14b' => 'U14 Boys',
		'u16g' => 'U16 Girls', 'u16b' => 'U16 Boys',
		'u18g' => 'U18 Women', 'u18b' => 'U18 Men',
		'u20g' => 'U20 Women', 'u20b' => 'U20 Men',
	);

	const SENIOR = array(
		'sl'   => array( 'Senior Women', '' ),
		'sm'   => array( 'Senior Men', '' ),
		'sl24' => array( 'Women U24', 'U24' ),
		'sm24' => array( 'Men U24', 'U24' ),
		'sl35' => array( 'Female Masters 35', 'W35' ),
		'sm35' => array( 'Male Masters 35', 'M35' ),
		'sl45' => array( 'Female Masters 45', 'W45' ),
		'sm45' => array( 'Male Masters 45', 'M45' ),
		'sl55' => array( 'Female Masters 55', 'W55' ),
		'sm55' => array( 'Male Masters 55', 'M55' ),
		'sl65' => array( 'Female Masters 65', 'W65' ),
		'sm65' => array( 'Male Masters 65', 'M65' ),
	);

	/** @var callable(string):?string */
	private $fetch;
	private $club;
	public $log = array();

	public function __construct( callable $fetch, $club = 'Swansea Harriers' ) {
		$this->fetch = $fetch;
		$this->club  = $club;
	}

	/**
	 * @return array{rows:array, standings:array, final:bool, found:int}
	 */
	public function parse( $base, $folder, $code, $match, $total_rounds = 5 ) {
		$prefix = rtrim( $base, '/' ) . '/' . trim( $folder, '/' ) . '/';
		$suffix = $code . $match;
		$rows = array();
		$standings = array();
		$final = false;
		$found = 0;
		$junior_leads = array();

		foreach ( self::JUNIOR as $race => $label ) {
			$t = $this->get( $prefix . $race . $suffix . 't.htm' );
			if ( null === $t ) {
				continue;
			}
			$team = $this->junior_team( $t );
			if ( ! $team ) {
				$this->log[] = "$label: our team not found";
				continue;
			}
			$found++;
			$first = '';
			$i = $this->get( $prefix . $race . $suffix . 'i.htm' );
			if ( null !== $i ) {
				$ind = $this->junior_first( $i );
				if ( $ind ) {
					$first = $ind['name'] . ' · ' . hs_ordinal( $ind['pos'] );
				}
			}
			if ( null === $team['match_pos'] ) {
				$this->log[] = "$label: no team score this match";
				continue;
			}
			$rows[] = array( $label, $team['match_pos'], 'of ' . $team['ran'] . ' teams', (string) $team['score'], $first );
			if ( 1 === $team['overall'] ) {
				$junior_leads[] = $label;
			}
		}

		foreach ( self::SENIOR as $race => $meta ) {
			list( $label, $cat ) = $meta;
			$t = $this->get( $prefix . $race . $suffix . 't.htm' );
			if ( null === $t ) {
				continue;
			}
			if ( false !== stripos( $t, 'FINAL RESULTS' ) ) {
				$final = true;
			}
			$team = $this->senior_team( $t );
			if ( ! $team ) {
				$this->log[] = "$label: our team not found";
				continue;
			}
			$found++;
			$first = '';
			$i = $this->get( $prefix . $race . $suffix . 'i.htm' );
			if ( null !== $i ) {
				$ind = $this->senior_first( $i );
				if ( $ind ) {
					$first = $ind['name'] . ' · ' . hs_ordinal( $ind['pos'] ) . ( $cat ? ' ' . $cat : '' );
				}
			}
			$race_label = $label;
			$pos = $team['pos'];
			$of = 'of ' . $team['ran'] . ' teams';
			if ( in_array( $race, array( 'sm', 'sl' ), true ) ) {
				$d = $this->get( $prefix . $race . $suffix . 'd.htm' );
				$div = null !== $d ? $this->senior_division( $d ) : null;
				if ( $div ) {
					$race_label = $label . ' · ' . $div['short'];
					$of = 'in ' . $div['short'] . ' · ' . hs_ordinal( $team['pos'] ) . ' of ' . $team['ran'] . ' overall';
					$pos = $div['pos'];
					$standings[] = array(
						( 'sm' === $race ? 'Senior men' : 'Senior women' ) . ' · after ' . $match . ' of ' . $total_rounds,
						hs_ordinal( $div['cum_pos'] ) . ' · ' . $div['short'],
						number_format( (float) $div['cum_pts'] ) . ' pts',
					);
				}
			}
			$rows[] = array( $race_label, $pos, $of, $team['pts'] . ' pts', $first );
		}

		if ( $junior_leads ) {
			$standings[] = array(
				'Juniors · after ' . $match . ' of ' . $total_rounds,
				count( $junior_leads ) . ( 1 === count( $junior_leads ) ? ' team leads' : ' teams lead' ),
				implode( ', ', $junior_leads ) . ' top the league',
			);
		}

		return array( 'rows' => $rows, 'standings' => $standings, 'final' => $final, 'found' => $found );
	}

	/* ---------- juniors (preformatted text) ---------- */

	public function junior_team( $html ) {
		$text = $this->text( $html );
		$p = stripos( $text, 'Team Results' );
		if ( false === $p ) {
			return null;
		}
		$body = substr( $text, $p );
		$end  = stripos( $body, 'Overall team results' );
		if ( false !== $end ) {
			$body = substr( $body, 0, $end );
		}
		$teams = array();
		foreach ( preg_split( '/\r\n|\r|\n/', $body ) as $line ) {
			if ( preg_match( '/^\s*(\d+)=?\s+(.+?)\s{2,}(\d+)?\s+(\d+)\s*$/', $line, $m ) ) {
				$teams[] = array(
					'overall' => (int) $m[1],
					'name'    => $this->norm( $m[2] ),
					'score'   => '' === $m[3] ? null : (int) $m[3],
					'cum'     => (int) $m[4],
				);
			}
		}
		$ours = null;
		foreach ( $teams as $t ) {
			if ( $this->is_a_team( $t['name'] ) ) {
				$ours = $t;
				break;
			}
		}
		if ( ! $ours ) {
			return null;
		}
		$scored = array_filter( $teams, fn( $t ) => null !== $t['score'] );
		$pos = null;
		if ( null !== $ours['score'] ) {
			$better = count( array_filter( $scored, fn( $t ) => $t['score'] < $ours['score'] ) );
			$pos = $better + 1;
		}
		return array( 'match_pos' => $pos, 'ran' => count( $scored ), 'score' => $ours['score'], 'overall' => $ours['overall'] );
	}

	public function junior_first( $html ) {
		$text = $this->text( $html );
		$best = null;
		foreach ( preg_split( '/\r\n|\r|\n/', $text ) as $line ) {
			if ( preg_match( '/^\s*(\d+)\s+(.+?)\s{2,}(.+?)\s{2,}(\d+:\d\d)\s+\d+/', $line, $m ) ) {
				if ( 0 === stripos( $this->norm( $m[3] ), $this->club ) ) {
					$pos = (int) $m[1];
					if ( null === $best || $pos < $best['pos'] ) {
						$best = array( 'pos' => $pos, 'name' => $this->norm( $m[2] ) );
					}
				}
			}
		}
		return $best;
	}

	/* ---------- seniors (HTML tables) ---------- */

	public function senior_team( $html ) {
		$rows = $this->table_rows( $html );
		$ran = 0;
		$ours = null;
		foreach ( $rows as $r ) {
			if ( count( $r ) < 3 || ! preg_match( '/^\d/', $r[0] ) && '' !== $r[0] ) {
				continue;
			}
			if ( '' !== $r[2] && is_numeric( $r[2] ) ) {
				$ran++;
			}
			if ( ! $ours && $this->is_a_team( $r[1] ) && '' !== $r[2] ) {
				$ours = array( 'pos' => (int) $r[0], 'pts' => $r[2] );
			}
		}
		if ( ! $ours ) {
			return null;
		}
		$ours['ran'] = $ran;
		return $ours;
	}

	public function senior_first( $html ) {
		foreach ( $this->table_rows( $html ) as $r ) {
			if ( count( $r ) < 4 ) {
				continue;
			}
			if ( 0 === stripos( $r[3], $this->club ) && preg_match( '/(\d+)/', $r[0], $m ) ) {
				return array( 'pos' => (int) $m[1], 'name' => $r[1] );
			}
		}
		return null;
	}

	public function senior_division( $html ) {
		$div = '';
		foreach ( $this->table_rows( $html ) as $r ) {
			if ( 1 === count( $r ) || ( count( $r ) >= 1 && preg_match( '/Division/i', $r[0] ) && ! is_numeric( $r[0] ) ) ) {
				$div = $r[0];
				continue;
			}
			if ( count( $r ) >= 3 && $this->is_a_team( $r[1] ) && '' !== $r[2] ) {
				$short = preg_match( '/Division\s+(\w+)/i', $div, $m ) ? 'Div ' . $m[1] : $div;
				return array(
					'name'    => $div,
					'short'   => $short,
					'pos'     => (int) $r[0],
					'cum_pts' => $r[3] ?? $r[2],
					'cum_pos' => isset( $r[4] ) && '' !== $r[4] ? (int) $r[4] : (int) $r[0],
				);
			}
		}
		return null;
	}

	/* ---------- utilities ---------- */

	/** True for our first team only (not "B", "C"…). */
	private function is_a_team( $name ) {
		$n = $this->norm( $name );
		if ( 0 !== stripos( $n, $this->club ) ) {
			return false;
		}
		return ! preg_match( '/["\'][A-Z]["\']\s*$/', $n );
	}

	private function norm( $s ) {
		$s = html_entity_decode( (string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$s = str_replace( "\xC2\xA0", ' ', $s );
		return trim( preg_replace( '/\s+/u', ' ', $s ) );
	}

	private function text( $html ) {
		$t = preg_replace( '/<br\s*\/?>/i', "\n", $html );
		$t = wp_strip_all_tags( $t );
		return html_entity_decode( $t, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	/** @return string[][] cell text per row */
	private function table_rows( $html ) {
		$out = array();
		$doc = new DOMDocument();
		libxml_use_internal_errors( true );
		$doc->loadHTML( '<?xml encoding="UTF-8">' . $html );
		libxml_clear_errors();
		foreach ( $doc->getElementsByTagName( 'tr' ) as $tr ) {
			$cells = array();
			foreach ( $tr->childNodes as $c ) {
				if ( XML_ELEMENT_NODE === $c->nodeType && in_array( strtolower( $c->nodeName ), array( 'td', 'th' ), true ) ) {
					$cells[] = $this->norm( $c->textContent );
				}
			}
			if ( $cells ) {
				$out[] = $cells;
			}
		}
		return $out;
	}

	private function get( $url ) {
		$body = call_user_func( $this->fetch, $url );
		if ( null === $body || '' === $body ) {
			return null;
		}
		return $body;
	}
}
