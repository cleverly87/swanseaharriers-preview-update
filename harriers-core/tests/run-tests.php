<?php
/**
 * Importer tests against saved league pages. Run inside WordPress:
 *   wp eval-file wp-content/plugins/harriers-core/tests/run-tests.php
 * Exits non-zero on failure.
 */

$dir   = __DIR__ . '/fixtures';
$fails = 0;
$check = function ( $label, $got, $want ) use ( &$fails ) {
	$ok = $got === $want;
	if ( ! $ok ) {
		$fails++;
	}
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $label . ( $ok ? '' : ' — got ' . var_export( $got, true ) . ', want ' . var_export( $want, true ) ) . "\n";
};

/* Gwent: fetcher maps https://gwent.test/2026/<file> to fixtures/gwent/<file> */
$fetch = function ( $url ) use ( $dir ) {
	$f = $dir . '/gwent/' . basename( wp_parse_url( $url, PHP_URL_PATH ) );
	return file_exists( $f ) ? file_get_contents( $f ) : null;
};
$p = new HS_Gwent_Parser( $fetch, 'Swansea Harriers' );

// Junior format after match 1: list is in OVERALL order; match position must be re-ranked.
$t = $p->junior_team( file_get_contents( $dir . '/gwent/u12g262t.htm' ) );
$check( 'Gwent junior: match position re-ranked from match scores', $t['match_pos'], 1 );
$check( 'Gwent junior: teams that ran (excludes TROTS, no score)', $t['ran'], 36 );
$check( 'Gwent junior: overall league position', $t['overall'], 1 );
$i = $p->junior_first( file_get_contents( $dir . '/gwent/u12g262i.htm' ) );
$check( 'Gwent junior: first Harrier ignores "Current Standings" block', $i, array( 'pos' => 7, 'name' => 'Molly Shelmerdine' ) );

// Match 1 format (same score in both columns).
$t1 = $p->junior_team( file_get_contents( $dir . '/gwent/u14b261t.htm' ) );
$check( 'Gwent junior match 1: B team not mistaken for A team', $t1['score'], 130 );
$check( 'Gwent junior match 1: position', $t1['match_pos'], 3 );

// Senior tables.
$s = $p->senior_team( file_get_contents( $dir . '/gwent/sm262t.htm' ) );
$check( 'Gwent senior: our A team position', $s['pos'], 8 );
$check( 'Gwent senior: our points', $s['pts'], '2437' );
$check( 'Gwent senior: teams that ran (blank-points row excluded)', $s['ran'], 7 );
$f = $p->senior_first( file_get_contents( $dir . '/gwent/sm262i.htm' ) );
$check( 'Gwent senior: first Harrier', $f, array( 'pos' => 27, 'name' => 'Matt Rees' ) );
$d = $p->senior_division( file_get_contents( $dir . '/gwent/sm262d.htm' ) );
$check( 'Gwent senior: division found (Div 1, not the B Division)', array( $d['short'], $d['pos'], $d['cum_pos'] ), array( 'Div 1', 5, 3 ) );

// Whole match.
$res = $p->parse( 'https://gwent.test', '2026', '26', 2, 5 );
$rows = array_column( $res['rows'], null, 0 );
$check( 'Gwent parse: final flag from "FINAL RESULTS"', $res['final'], true );
$check( 'Gwent parse: U12 Girls row', $rows['U12 Girls'] ?? null, array( 'U12 Girls', 1, 'of 36 teams', '55', 'Molly Shelmerdine · 7th' ) );
$check( 'Gwent parse: Senior Men row', $rows['Senior Men · Div 1'] ?? null, array( 'Senior Men · Div 1', 5, 'in Div 1 · 8th of 7 overall', '2437 pts', 'Matt Rees · 27th' ) );
$check( 'Gwent parse: standings', $res['standings'][0] ?? null, array( 'Senior men · after 2 of 5', '3rd · Div 1', '4,275 pts' ) );

/* West Glam */
$w = new HS_WestGlam_Parser( 'Swansea Harriers' );
$v = file_get_contents( $dir . '/westglam/pembrey.html' );
$firsts = $w->first_finishers( $v );
$check( 'West Glam: first woman', $firsts['F'] ?? null, array( 'pos' => 9, 'name' => 'Bethan Froome', 'total' => 172 ) );
$check( 'West Glam: first man', $firsts['M'] ?? null, array( 'pos' => 1, 'name' => 'Marc Hobbs', 'total' => 219 ) );
$res = $w->parse( $v, file_get_contents( $dir . '/westglam/team-tables-male.html' ), file_get_contents( $dir . '/westglam/team-tables-female.html' ), 2, 5 );
$check( 'West Glam: men round 2 row', $res['rows'][0] ?? null, array( 'Men · Division 1', 1, 'of 8 teams', '2466 pts', 'Marc Hobbs · 1st of 219' ) );
$check( 'West Glam: women round 2 row', $res['rows'][1] ?? null, array( 'Women · Division 2', 8, 'of 8 teams', '915 pts', 'Bethan Froome · 9th of 172' ) );
$res1 = $w->parse( null, file_get_contents( $dir . '/westglam/team-tables-male.html' ), file_get_contents( $dir . '/westglam/team-tables-female.html' ), 1, 5 );
$check( 'West Glam: women round 1 position from LP1', $res1['rows'][1][1] ?? null, 6 );
$check( 'West Glam: men standing', $res['standings'][0] ?? null, array( 'Men · after 2 of 5', '1st · Div 1', '16 league pts · 4,947 match pts' ) );

/* End-to-end: run_round writes the round and league, and never wipes results. */
$league = wp_insert_post( array( 'post_type' => 'hs_league', 'post_title' => 'TEST Gwent', 'post_status' => 'publish' ) );
foreach ( array( 'hs_source' => 'gwent', 'hs_gwent_base' => 'https://gwent.test', 'hs_gwent_folder' => '2026', 'hs_gwent_code' => '26', 'hs_club_name' => 'Swansea Harriers' ) as $k => $val ) {
	update_post_meta( $league, $k, $val );
}
$round = wp_insert_post( array( 'post_type' => 'hs_round', 'post_title' => 'Match 2', 'post_status' => 'publish', 'post_parent' => $league ) );
update_post_meta( $round, 'hs_round_no', 2 );
update_post_meta( $round, 'hs_date', wp_date( 'Y-m-d', strtotime( '-4 days' ) ) );
update_post_meta( $round, 'hs_auto', '1' );
$msg = HS_Importer::run_round( $round, $fetch );
$check( 'run_round: message', $msg, 'Imported 2 races (final).' );
$check( 'run_round: status saved', get_post_meta( $round, 'hs_status', true ), 'final' );
$check( 'run_round: rows saved', count( hs_parse_lines( get_post_meta( $round, 'hs_rows', true ) ) ), 2 );
$check( 'run_round: league standings saved', (bool) get_post_meta( $league, 'hs_standings', true ), true );
$before = get_post_meta( $round, 'hs_rows', true );
HS_Importer::run_round( $round, fn() => null );
$check( 'run_round: site down leaves results untouched', get_post_meta( $round, 'hs_rows', true ), $before );
update_post_meta( $round, 'hs_status', 'provisional' );
$due = wp_list_pluck( HS_Importer::due_rounds(), 'ID' );
$check( 'due_rounds: provisional recent round is due', in_array( $round, $due, true ), true );
update_post_meta( $round, 'hs_auto', '0' );
$check( 'due_rounds: auto off is skipped', in_array( $round, wp_list_pluck( HS_Importer::due_rounds(), 'ID' ), true ), false );
wp_delete_post( $round, true );
wp_delete_post( $league, true );

/* Homepage grouping: two fixtures on one weekend show together. */
$sat = gmdate( 'Y-m-d', strtotime( 'next saturday', strtotime( '+60 days' ) ) );
$sun = gmdate( 'Y-m-d', strtotime( '+1 day', strtotime( $sat ) ) );
$tmp = array();
foreach ( get_posts( array( 'post_type' => 'hs_fixture', 'numberposts' => -1, 'fields' => 'ids' ) ) as $id ) {
	wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) );
	$tmp[] = $id;
}
$a = wp_insert_post( array( 'post_type' => 'hs_fixture', 'post_title' => 'TEST Sat race', 'post_status' => 'publish' ) );
$b = wp_insert_post( array( 'post_type' => 'hs_fixture', 'post_title' => 'TEST Sun race', 'post_status' => 'publish' ) );
$c = wp_insert_post( array( 'post_type' => 'hs_fixture', 'post_title' => 'TEST later race', 'post_status' => 'publish' ) );
update_post_meta( $a, 'hs_date', $sat );
update_post_meta( $b, 'hs_date', $sun );
update_post_meta( $c, 'hs_date', gmdate( 'Y-m-d', strtotime( '+10 days', strtotime( $sat ) ) ) );
$g = hs_get_next_fixture_group();
$check( 'Next fixture group: whole weekend listed', wp_list_pluck( $g['fixtures'], 'ID' ), array( $a, $b ) );
$check( 'Next fixture group: weekend label', 0 === strpos( $g['label'], 'Weekend of' ), true );
foreach ( array( $a, $b, $c ) as $id ) {
	wp_delete_post( $id, true );
}
foreach ( $tmp as $id ) {
	wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
}

echo "\n" . ( $fails ? "$fails FAILED" : 'ALL PASSED' ) . "\n";
if ( $fails ) {
	exit( 1 );
}
