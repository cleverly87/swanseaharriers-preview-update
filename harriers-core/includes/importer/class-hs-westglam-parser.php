<?php
/**
 * Reads one West Glamorgan XC League round from westglamleague.co.uk.
 *
 * - Each venue page (e.g. /pembrey/) has one table per race with columns
 *   Position | Time | Bib | FirstName | Surname | Gender | Club | AgeCat | Points.
 * - The running team tables (/team-tables-male/, /team-tables-female/) have
 *   Club | MP1 | MP2 … | LP1 | LP2 … | MatchPoint | LeaguePoints | Division.
 *   LPn is the league points earned in round n: with N teams in a division the
 *   winner gets N, so the round position is N + 1 − LPn.
 */

defined( 'ABSPATH' ) || exit;

class HS_WestGlam_Parser {

	private $club;
	public $log = array();

	public function __construct( $club = 'Swansea Harriers' ) {
		$this->club = $club;
	}

	/**
	 * @param string|null $venue_html  Round venue page.
	 * @param string|null $male_html   Men's team table page.
	 * @param string|null $female_html Women's team table page.
	 * @return array{rows:array, standings:array, found:int}
	 */
	public function parse( $venue_html, $male_html, $female_html, $round, $total_rounds = 5 ) {
		$firsts = $venue_html ? $this->first_finishers( $venue_html ) : array();
		$rows = array();
		$standings = array();
		$found = 0;
		foreach ( array( 'M' => array( 'Men', $male_html ), 'F' => array( 'Women', $female_html ) ) as $g => $set ) {
			list( $label, $html ) = $set;
			if ( ! $html ) {
				continue;
			}
			$team = $this->team( $html, $round );
			if ( ! $team ) {
				$this->log[] = "$label: our team not found in team table";
				continue;
			}
			$standings[] = array(
				$label . ' · after ' . $team['rounds_done'] . ' of ' . $total_rounds,
				hs_ordinal( $team['standing'] ) . ' · Div ' . $team['div'],
				$team['league_pts'] . ' league pts · ' . number_format( (float) $team['match_total'] ) . ' match pts',
			);
			if ( null === $team['pos'] ) {
				$this->log[] = "$label: no score for round $round yet";
				continue;
			}
			$found++;
			$first = '';
			if ( isset( $firsts[ $g ] ) ) {
				$f = $firsts[ $g ];
				$first = $f['name'] . ' · ' . hs_ordinal( $f['pos'] ) . ' of ' . $f['total'];
			}
			$rows[] = array( $label . ' · Division ' . $team['div'], $team['pos'], 'of ' . $team['teams'] . ' teams', $team['mp'] . ' pts', $first );
		}
		return array( 'rows' => $rows, 'standings' => $standings, 'found' => $found );
	}

	/** First Harrier in each race on a venue page, keyed by gender M/F. */
	public function first_finishers( $html ) {
		$out = array();
		foreach ( $this->tables( $html ) as $rows ) {
			if ( count( $rows ) < 2 ) {
				continue;
			}
			$h = array_map( 'strtolower', $rows[0] );
			$ci = array_search( 'club', $h, true );
			$pi = array_search( 'position', $h, true );
			$gi = array_search( 'gender', $h, true );
			$fi = array_search( 'firstname', $h, true );
			$si = array_search( 'surname', $h, true );
			if ( false === $ci || false === $pi ) {
				continue;
			}
			$data = array_slice( $rows, 1 );
			$gender = false !== $gi ? strtoupper( substr( $data[0][ $gi ] ?? '', 0, 1 ) ) : '';
			$best = null;
			foreach ( $data as $r ) {
				if ( 0 === stripos( $r[ $ci ] ?? '', $this->club ) && is_numeric( $r[ $pi ] ?? '' ) ) {
					$p = (int) $r[ $pi ];
					if ( null === $best || $p < $best['pos'] ) {
						$best = array( 'pos' => $p, 'name' => trim( ( $r[ $fi ] ?? '' ) . ' ' . ( $r[ $si ] ?? '' ) ) );
					}
				}
			}
			if ( $best && $gender ) {
				$best['total'] = count( $data );
				$out[ $gender ] = $best;
			}
		}
		return $out;
	}

	/** Our team's numbers from a team-table page. */
	public function team( $html, $round ) {
		foreach ( $this->tables( $html ) as $rows ) {
			if ( count( $rows ) < 2 ) {
				continue;
			}
			$h = array_map( fn( $x ) => strtolower( str_replace( ' ', '', $x ) ), $rows[0] );
			$ci = array_search( 'club', $h, true );
			$di = array_search( 'division', $h, true );
			$lpi = array_search( 'lp' . $round, $h, true );
			$mpi = array_search( 'mp' . $round, $h, true );
			$tl = array_search( 'leaguepoints', $h, true );
			$tm = array_search( 'matchpoint', $h, true );
			if ( false === $ci || false === $di ) {
				continue;
			}
			$data = array_slice( $rows, 1 );
			$ours = null;
			foreach ( $data as $r ) {
				if ( 0 === strcasecmp( trim( $r[ $ci ] ?? '' ), $this->club ) ) {
					$ours = $r;
					break;
				}
			}
			if ( ! $ours ) {
				continue;
			}
			$div = $ours[ $di ];
			$same = array_values( array_filter( $data, fn( $r ) => ( $r[ $di ] ?? '' ) === $div ) );
			$n = count( $same );
			$lp = false !== $lpi ? trim( $ours[ $lpi ] ?? '' ) : '';
			$pos = is_numeric( $lp ) && (float) $lp > 0 ? $n + 1 - (int) $lp : null;
			// Rounds completed = highest LPn column with any value.
			$done = 0;
			foreach ( $h as $idx => $name ) {
				if ( preg_match( '/^lp(\d+)$/', $name, $m ) ) {
					foreach ( $data as $r ) {
						if ( '' !== trim( $r[ $idx ] ?? '' ) ) {
							$done = max( $done, (int) $m[1] );
							break;
						}
					}
				}
			}
			usort( $same, function ( $a, $b ) use ( $tl, $tm ) {
				$c = (float) ( $b[ $tl ] ?? 0 ) <=> (float) ( $a[ $tl ] ?? 0 );
				return 0 !== $c ? $c : ( (float) ( $b[ $tm ] ?? 0 ) <=> (float) ( $a[ $tm ] ?? 0 ) );
			} );
			$standing = 1;
			foreach ( $same as $i => $r ) {
				if ( 0 === strcasecmp( trim( $r[ $ci ] ), $this->club ) ) {
					$standing = $i + 1;
				}
			}
			return array(
				'div'         => $div,
				'teams'       => $n,
				'pos'         => $pos,
				'mp'          => false !== $mpi ? trim( $ours[ $mpi ] ?? '' ) : '',
				'league_pts'  => false !== $tl ? trim( $ours[ $tl ] ) : '',
				'match_total' => false !== $tm ? trim( $ours[ $tm ] ) : '',
				'standing'    => $standing,
				'rounds_done' => $done ?: $round,
			);
		}
		return null;
	}

	/** @return string[][][] tables → rows → cell text */
	private function tables( $html ) {
		$doc = new DOMDocument();
		libxml_use_internal_errors( true );
		$doc->loadHTML( '<?xml encoding="UTF-8">' . $html );
		libxml_clear_errors();
		$out = array();
		foreach ( $doc->getElementsByTagName( 'table' ) as $table ) {
			$rows = array();
			foreach ( $table->getElementsByTagName( 'tr' ) as $tr ) {
				$cells = array();
				foreach ( $tr->childNodes as $c ) {
					if ( XML_ELEMENT_NODE === $c->nodeType && in_array( strtolower( $c->nodeName ), array( 'td', 'th' ), true ) ) {
						$cells[] = trim( preg_replace( '/\s+/u', ' ', str_replace( "\xC2\xA0", ' ', $c->textContent ) ) );
					}
				}
				if ( $cells ) {
					$rows[] = $cells;
				}
			}
			$out[] = $rows;
		}
		return $out;
	}
}
