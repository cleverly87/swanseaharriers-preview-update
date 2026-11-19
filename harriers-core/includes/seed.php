<?php
/**
 * One-off starter content: the club data from the website design.
 * Idempotent: anything that already exists (matched by title) is skipped.
 */

defined( 'ABSPATH' ) || exit;

/** Steps, in order. Each is small enough to finish quickly even on slow hosts (or WordPress Playground). */
function hs_seed_steps() {
	return array(
		'leagues'    => 'Leagues and results',
		'fixtures'   => 'Fixtures',
		'training'   => 'Training groups',
		'volunteers' => 'Coaches, team managers and officers',
		'records'    => 'Club records',
		'pages'      => 'Pages, news and menus',
	);
}

/**
 * @param string|null $step One key from hs_seed_steps(), or null for everything at once.
 */
function hs_seed_starter_content( $step = null ) {
	$made = 0;
	wp_defer_term_counting( true );

	/* ---------- taxonomy terms ---------- */
	$terms = array(
		'hs_fixture_type'   => array( 'League', 'Championship', 'Road', 'Relays', 'International', 'Track & field', 'Open meeting' ),
		'hs_training_tab'   => array( 'Under 10s', 'Juniors · school years 5–11', 'Track & field squads', 'Endurance · seniors & masters' ),
		'hs_volunteer_role' => array( 'Coaches', 'Team managers', 'Officials', 'Officers & trustees' ),
		'hs_event_group'    => array( 'Juniors', 'Endurance', 'Sprints & hurdles', 'Jumps & vault', 'Throws', 'Middle & long distance', 'Walks & relays' ),
		'category'          => array( 'Announcements', 'Results', 'Fixtures', 'Weekly updates' ),
	);
	foreach ( $terms as $tax => $names ) {
		foreach ( $names as $i => $n ) {
			if ( ! term_exists( $n, $tax ) ) {
				$t = wp_insert_term( $n, $tax );
				if ( ! is_wp_error( $t ) && 'hs_training_tab' === $tax ) {
					update_term_meta( $t['term_id'], 'hs_order', $i );
				}
			}
		}
	}

	$post = function ( $type, $title, $meta = array(), $tax = array(), $extra = array() ) use ( &$made ) {
		$existing = get_posts( array( 'post_type' => $type, 'title' => $title, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'post_parent' => $extra['post_parent'] ?? '' ) );
		if ( $existing ) {
			return $existing[0];
		}
		$id = wp_insert_post( array_merge( array( 'post_type' => $type, 'post_title' => $title, 'post_status' => 'publish' ), $extra ) );
		if ( is_wp_error( $id ) ) {
			return 0;
		}
		foreach ( $meta as $k => $v ) {
			update_post_meta( $id, $k, $v );
		}
		foreach ( $tax as $t => $v ) {
			wp_set_object_terms( $id, $v, $t );
		}
		$made++;
		return $id;
	};

	/* ---------- leagues & rounds ---------- */
	if ( ! $step || 'leagues' === $step ) {
	$lines = fn( $rows ) => implode( "\n", array_map( fn( $r ) => implode( ' | ', $r ), $rows ) );

	$gwent = $post( 'hs_league', 'Gwent League 2026/27', array(
		'hs_display_name' => 'Gwent League', 'hs_full_name' => 'John H Collins Gwent Cross Country League · 5 fixtures', 'hs_squad' => 'U12 to Masters',
		'hs_season_type' => 'winter', 'hs_season_label' => '2026/27', 'hs_current' => '1',
		'hs_official_url' => 'https://www.gwent-league.org.uk/', 'hs_score_label' => 'Score',
		'hs_footnote' => 'Junior races score lowest-wins on placings; senior and masters races score highest-wins on points. Senior teams race in divisions.',
		'hs_source' => 'gwent', 'hs_club_name' => 'Swansea Harriers', 'hs_gwent_base' => 'https://www.gwent-league.org.uk', 'hs_gwent_folder' => '2026', 'hs_gwent_code' => '26',
		'hs_standings' => $lines( array(
			array( 'Senior men · after 2 of 5', '3rd · Div 1', '4,275 pts' ),
			array( 'Senior women · after 2 of 5', '7th · Div 1', '2,527 pts' ),
			array( 'Juniors · after 2 of 5', '3 teams lead', 'U12 Girls, U14 Girls, U20 Women top the league' ),
		) ),
	), array(), array( 'menu_order' => 1 ) );

	$g1 = array(
		array( 'U12 Girls', '1', 'of 27 teams', '55', 'Molly Shelmerdine · 10th' ), array( 'U12 Boys', '6', 'of 35 teams', '146', 'Joshua Thompson · 15th' ),
		array( 'U14 Girls', '2', 'of 39 teams', '52', 'Molly-Mae McQueen · 1st' ), array( 'U14 Boys', '7', 'of 38 teams', '130', 'Jackson Jones · 5th' ),
		array( 'U16 Girls', '2', 'of 29 teams', '42', 'Emily Dando · 2nd' ), array( 'U16 Boys', '5', 'of 29 teams', '73', 'Oliver Cobbold · 2nd' ),
		array( 'U18 Women', '6', 'of 18 teams', '102', 'Isabelle Lawley · 10th' ), array( 'U18 Men', '5', 'of 23 teams', '118', 'Osian Morgan · 10th' ),
		array( 'U20 Women', '1', 'of 9 teams', '21', 'Holly Humphreys · 1st' ), array( 'U20 Men', '3', 'of 11 teams', '71', 'Jack Sullivan · 7th' ),
		array( 'Senior Women · Div 1', '3', 'of 81 teams', '1316 pts', 'Beatrice Rusby · 5th' ), array( 'Senior Men · Div 1', '2', 'of 104 teams', '1838 pts', 'Dafydd Jones · 1st' ),
		array( 'Women U24', '2', 'of 14 teams', '59 pts', 'Beatrice Rusby · 2nd U24' ), array( 'Men U24', '3', 'of 20 teams', '111 pts', 'Dafydd Jones · 1st U24' ),
		array( 'Female Masters 35', '15', 'of 62 teams', '672 pts', 'Michelle Cole · 30th W35' ), array( 'Male Masters 35', '7', 'of 79 teams', '1142 pts', 'Marc Hobbs · 5th M35' ),
		array( 'Female Masters 45', '6', 'of 57 teams', '438 pts', 'Michelle Cole · 11th W45' ), array( 'Male Masters 45', '12', 'of 69 teams', '504 pts', 'Robert Hughes · 38th M45' ),
		array( 'Female Masters 55', '5', 'of 33 teams', '131 pts', 'Sharon Leech · 3rd W55' ), array( 'Male Masters 55', '4', 'of 42 teams', '125 pts', 'Simon Brewer · 15th M55' ),
		array( 'Female Masters 65', '2', 'of 10 teams', '11 pts', 'Sian Phillips · 1st W65' ), array( 'Male Masters 65', '3', 'of 39 teams', '101 pts', 'Martin Kearns · 9th M65' ),
	);
	$g2 = array(
		array( 'U12 Girls', '1', 'of 36 teams', '55', 'Molly Shelmerdine · 7th' ), array( 'U12 Boys', '7', 'of 47 teams', '152', 'Joshua Thompson · 2nd' ),
		array( 'U14 Girls', '2', 'of 44 teams', '58', 'Olive Knutson-Watson · 8th' ), array( 'U14 Boys', '11', 'of 40 teams', '199', 'Jackson Jones · 6th' ),
		array( 'U16 Girls', '2', 'of 27 teams', '61', 'Emily Dando · 1st' ), array( 'U16 Boys', '2', 'of 35 teams', '53', 'Tomi Jones · 5th' ),
		array( 'U18 Women', '16', 'of 22 teams', '199', 'Isabelle Lawley · 22nd' ), array( 'U18 Men', '11', 'of 27 teams', '178', 'Osian Morgan · 10th' ),
		array( 'U20 Women', '1', 'of 16 teams', '25', 'Holly Humphreys · 1st' ), array( 'U20 Men', '11', 'of 20 teams', '133', 'Jack Sullivan · 20th' ),
		array( 'Senior Women · Div 1', '9', 'in Div 1 · 23rd of 97 overall', '1211 pts', 'Beatrice Rusby · 9th' ), array( 'Senior Men · Div 1', '5', 'in Div 1 · 8th of 131 overall', '2437 pts', 'Matt Rees · 27th' ),
		array( 'Women U24', '10', 'of 18 teams', '38 pts', 'Beatrice Rusby · 2nd U24' ), array( 'Men U24', '7', 'of 26 teams', '83 pts', 'Morgan James · 12th U24' ),
		array( 'Female Masters 35', '27', 'of 71 teams', '655 pts', 'Michelle Cole · 46th W35' ), array( 'Male Masters 35', '32', 'of 93 teams', '894 pts', 'Matt Rees · 8th M35' ),
		array( 'Female Masters 45', '12', 'of 62 teams', '472 pts', 'Michelle Cole · 19th W45' ), array( 'Male Masters 45', '47', 'of 76 teams', '203 pts', 'Geoff Robinson · 107th M45' ),
		array( 'Female Masters 55', '27', 'of 41 teams', '47 pts', 'Sian Phillips · 34th W55' ), array( 'Male Masters 55', '13', 'of 45 teams', '111 pts', 'Geoff Robinson · 27th M55' ),
		array( 'Female Masters 65', '4', 'of 13 teams', '12 pts', 'Sian Phillips · 4th W65' ), array( 'Male Masters 65', '2', 'of 44 teams', '129 pts', 'Ifan Lloyd · 1st M65' ),
	);
	$rounds = array(
		array( $gwent, 'Match 1', 1, '2026-10-10', 'Pembrey Country Park', $lines( $g1 ), 'final', '' ),
		array( $gwent, 'Match 2', 2, '2026-11-08', 'Llandaff Fields, Cardiff', $lines( $g2 ), 'final', '' ),
		array( $gwent, 'Match 3', 3, '2026-12-05', 'Blaise Castle, Bristol', '', '', '' ),
		array( $gwent, 'Match 4', 4, '2027-02-14', 'Trelai Park, Cardiff', '', '', '' ),
		array( $gwent, 'Match 5', 5, '2027-03-06', 'Venue to be confirmed', '', '', '' ),
	);

	$wg = $post( 'hs_league', 'West Glamorgan XC League 2026/27', array(
		'hs_display_name' => 'West Glamorgan XC', 'hs_full_name' => 'West Glamorgan Cross Country League · 5 fixtures', 'hs_squad' => 'Senior & masters men and women',
		'hs_season_type' => 'winter', 'hs_season_label' => '2026/27', 'hs_current' => '1',
		'hs_official_url' => 'https://www.westglamleague.co.uk/', 'hs_score_label' => 'Match points',
		'hs_footnote' => 'Teams earn league points by finishing position in their division each fixture.',
		'hs_source' => 'westglam', 'hs_club_name' => 'Swansea Harriers',
		'hs_wg_male_url' => 'https://www.westglamleague.co.uk/team-tables-male/', 'hs_wg_female_url' => 'https://www.westglamleague.co.uk/team-tables-female/',
		'hs_standings' => $lines( array(
			array( 'Men · after 2 of 5', '1st · Div 1', '16 league pts · 4,947 match pts' ),
			array( 'Women · after 2 of 5', '8th · Div 2', '4 league pts · 2,773 match pts' ),
		) ),
	), array(), array( 'menu_order' => 2 ) );
	$wg1 = array( array( 'Men · Division 1', '1', 'of 8 teams', '2481 pts', 'Finley Hines · 1st of 204' ), array( 'Women · Division 2', '6', 'of 8 teams', '1858 pts', 'Bethan Froome · 13th of 153' ) );
	$wg2 = array( array( 'Men · Division 1', '1', 'of 8 teams', '2466 pts', 'Marc Hobbs · 1st of 220' ), array( 'Women · Division 2', '8', 'of 8 teams', '915 pts', 'Bethan Froome · 9th of 181' ) );
	$rounds = array_merge( $rounds, array(
		array( $wg, 'Round 1', 1, '2026-09-27', 'Tairgwaith', $lines( $wg1 ), 'final', 'https://www.westglamleague.co.uk/tairgwaith/' ),
		array( $wg, 'Round 2', 2, '2026-10-18', 'Pembrey Country Park', $lines( $wg2 ), 'final', 'https://www.westglamleague.co.uk/pembrey/' ),
		array( $wg, 'Round 3', 3, '2026-11-22', 'Aberavon Beach', '', '', 'https://www.westglamleague.co.uk/aberavon/' ),
		array( $wg, 'Round 4', 4, '2027-01-10', 'Margam Park', '', '', 'https://www.westglamleague.co.uk/margam/' ),
		array( $wg, 'Round 5', 5, '2027-02-07', 'Kenfig Nature Reserve', '', '', 'https://www.westglamleague.co.uk/kenfig/' ),
	) );

	$summer = array(
		array( 'National Athletics League 2026', 'National Athletics League', 'Premiership · senior men & women', 'Seniors', 'https://www.nationalathleticsleague.org.uk/results-2026/',
			array( array( 'Final position · 2026', '8th of 8', '3 league pts · 923.5 match pts' ), array( 'Champions', 'Thames Valley', '23 league pts · 1,914 match pts' ) ),
			array( array( 'Match 1', '2026-05-23', 'Derby', 0 ), array( 'Match 2', '2026-07-11', 'Woodford, London', 0 ), array( 'Match 3', '2026-08-22', 'Eton, London', 0 ) ) ),
		array( 'YDL Upper Age Group 2026', 'YDL Upper Age Group', 'Midlands Premier South West', 'U18 & U20', 'https://www.ukydl.org.uk/page101.html',
			array( array( 'Final position · 2026', '4th of 10', '878 match pts (502 + 376) · Match 3 cancelled' ), array( 'Division winners', 'Team Avon', '1,974 match pts' ) ),
			array( array( 'Match 1', '2026-04-19', 'Cheltenham', 0 ), array( 'Match 2', '2026-05-24', 'Yate', 0 ), array( 'Match 3', '2026-07-12', 'Gloucester', 1 ) ) ),
		array( 'YDL Lower Age Group 2026', 'YDL Lower Age Group', 'Midlands Premier South West', 'U14 & U16', 'https://www.ukydl.org.uk/page201.html',
			array( array( 'Final position · 2026', '5th of 7', '573.5 + 480.5 + 530 match pts · subject to paper check' ), array( 'Division winners', 'Cheltenham', 'Through to the National Final' ) ),
			array( array( 'Match 1', '2026-05-03', 'Swindon', 0 ), array( 'Match 2', '2026-05-31', 'Aberdare', 0 ), array( 'Match 3', '2026-07-18', 'Cardiff', 0 ) ) ),
		array( 'Welsh Junior League 2026', 'Welsh Junior League', 'West Division', 'U12, U14 & U16', 'https://www.swanseaharriers.co.uk/league-tables',
			array(),
			array( array( 'Match 1', '2026-05-17', 'Neath', 0 ), array( 'Match 2', '2026-06-20', 'Brecon', 0 ), array( 'Match 3', '2026-08-08', 'Carmarthen', 0 ) ) ),
	);
	foreach ( $summer as $i => $s ) {
		$lid = $post( 'hs_league', $s[0], array(
			'hs_display_name' => $s[1], 'hs_full_name' => $s[2], 'hs_squad' => $s[3], 'hs_season_type' => 'summer', 'hs_season_label' => '2026', 'hs_current' => '1',
			'hs_official_url' => $s[4], 'hs_score_label' => 'Score', 'hs_source' => 'manual', 'hs_standings' => $lines( $s[5] ),
		), array(), array( 'menu_order' => 10 + $i ) );
		foreach ( $s[6] as $n => $m ) {
			$rid = $post( 'hs_round', $m[0], array( 'hs_league' => $lid, 'hs_round_no' => $n + 1, 'hs_date' => $m[1], 'hs_venue' => $m[2], 'hs_cancelled' => $m[3] ? '1' : '0', 'hs_auto' => '0' ), array(), array( 'post_parent' => $lid ) );
		}
	}
	foreach ( $rounds as $r ) {
		$post( 'hs_round', $r[1], array( 'hs_league' => $r[0], 'hs_round_no' => $r[2], 'hs_date' => $r[3], 'hs_venue' => $r[4], 'hs_rows' => $r[5], 'hs_status' => $r[6] ?: 'provisional', 'hs_source_url' => $r[7], 'hs_auto' => '1', 'hs_cancelled' => '0' ), array(), array( 'post_parent' => $r[0] ) );
	}

	}
	/* ---------- fixtures ---------- */
	if ( ! $step || 'fixtures' === $step ) {
	$fx = array(
		array( 'British Masters XC International', '2026-11-14', 'Dublin', 'Selected masters', 'International', 'off', '', '' ),
		array( 'Severn Bridge 5 Mile Night Race · 3 of 4', '2026-11-19', 'Chepstow', 'Road series', 'Road', 'off', '', '' ),
		array( 'Welsh Inter Regional XC Championships', '2026-11-21', 'Dolerw Park, Newtown', 'Selection fixture', 'Championship', 'off', '', '' ),
		array( 'West Glamorgan XC League · Round 3 of 5', '2026-11-22', 'Aberavon Beach', 'Senior & masters', 'League', 'off', '', '' ),
		array( 'British Masters 5km Championships', '2026-11-29', 'Battersea Park, London', '', 'Championship', 'off', '', '' ),
		array( 'Gwent League XC · Match 3 of 5', '2026-12-05', 'Blaise Castle, Bristol', 'Hosted by Westbury Harriers · U12 to Masters', 'League', 'off', '', '' ),
		array( 'West Glamorgan XC League · Round 4 of 5', '2027-01-10', 'Margam Park', 'Hosted by Run4All', 'League', 'off', '', '' ),
		array( 'West Glamorgan XC League · Round 5 of 5', '2027-02-07', 'Kenfig Nature Reserve', 'Hosted by Cornelly Striders', 'League', 'off', '', '' ),
		array( 'Gwent League XC · Match 4 of 5', '2027-02-14', 'Trelai Park, Cardiff', 'Hosted by Cardiff AAC', 'League', 'off', '', '' ),
		array( 'Gwent League XC · Match 5 of 5', '2027-03-06', 'Venue to be confirmed', '6 or 7 March', 'League', 'off', '', '' ),
	);
	foreach ( $fx as $f ) {
		$post( 'hs_fixture', $f[0], array( 'hs_date' => $f[1], 'hs_venue' => $f[2], 'hs_details' => $f[3], 'hs_discipline' => $f[5], 'hs_entry_url' => $f[6], 'hs_entry_platform' => $f[7] ), array( 'hs_fixture_type' => $f[4] ) );
	}

	}
	/* ---------- training groups ---------- */
	if ( ! $step || 'training' === $step ) {
	$tg = array(
		array( 'Under 10s', 'Indoor track satellite', 'School years 4 & 5', 'Mon or Thu, 4–5pm', '£44 per 10-week term', 'Swansea University Indoor Track, SA2 9AU', '', 'Led by UK Athletics qualified coaches.' ),
		array( 'Under 10s', 'LC2 satellite', 'Ages 5–12', 'Wed, 4.15pm (5–7s) · 5pm (8–12s)', '£5 per session', 'LC2 Swansea, SA1 3ST', '', 'Booked as a 10-week course directly with LC2.' ),
		array( 'Under 10s', 'Swansea Prom junior parkrun', 'Ages 4–14', 'Sundays, 9am', 'Free', 'Swansea Promenade, Mumbles Road SA2 0AY', '', 'A weekly 2k. Register once with parkrun and bring your barcode.' ),
		array( 'Juniors · school years 5–11', 'Fundamentals', 'School years 5–7', 'Tue, Wed or Thu, 5–6.15pm', '£3.60 per session', 'Indoor Athletics Centre, Swansea University', '', 'Skill development over performance. Contactless payment on arrival at the club café.' ),
		array( 'Juniors · school years 5–11', 'Foundation', 'School years 8–11', 'Wed, 6.30–8pm', '£3.60 per session', 'Indoor Athletics Centre, Swansea University', '', 'All events, building towards league and championship competition.' ),
		array( 'Juniors · school years 5–11', 'Junior XC & endurance', 'Juniors', 'Varies by age & ability', '£3.60 per session', 'Swansea University Athletics Centre', 'Anthony Clements (lead coach) and his junior endurance coaching team', 'Led by Anthony Clements, our junior endurance team looks after 50+ athletes, preparing them for the Gwent League, West Glamorgan XC, road and track.' ),
		array( 'Track & field squads', 'Sprints & hurdles', 'Junior & senior squads', 'Varies by age & ability', '£3.60 per session', 'Swansea University Athletics Centre', 'Andrew Griffiths, Judith Gooding, Lynne Brier, Neal Merry, Wendy Slade, Rob Jones, Graham (Tony) Morris', '' ),
		array( 'Track & field squads', 'Long & triple jump', 'Junior & senior squads', 'Varies by age & ability', '£3.60 per session', 'Swansea University Athletics Centre', 'Steve Ingram, Rob Gilroy (juniors)', '' ),
		array( 'Track & field squads', 'High jump', 'Junior & senior squads', 'Varies by age & ability', '£3.60 per session', 'Swansea University Athletics Centre', 'Stephen Ryan, Rob Jones', '' ),
		array( 'Track & field squads', 'Pole vault', 'Junior & senior squads', 'Varies by age & ability', '£3.60 per session', 'Swansea University Athletics Centre', 'Mark Beharrell, Neil Bezodis, Craig Guite, Ieuan Hosgood, Jane Hosgood, Islwyn Rees', '' ),
		array( 'Track & field squads', 'Throws', 'Junior & senior squads', 'Varies by age & ability', '£3.60 per session', 'Swansea University Athletics Centre', 'Peter Roberts (shot, discus, hammer), Jeff Squire and Richard Jones (javelin), Jason Lee Cameron, David Dix', '' ),
		array( 'Track & field squads', 'Middle distance', 'Junior & senior squads', 'Varies by age & ability', '£3.60 per session', 'Swansea University Athletics Centre', 'Kevin Tobin, John Griffiths, Rob Buchan', '' ),
		array( 'Endurance · seniors & masters', 'Senior & masters group', '17+, roughly 50-min 10K or 2-hr half', 'Tue & Thu, meet 6.15pm for 6.30pm', 'Free (track sessions £3.60)', 'Club café, Ashleigh Road, SA2 9AU', 'Daniel Newton, Craig Jones, Simon Brewer, Joe Tucker, Kelly Gipson, Paul Flynn', '6–9 miles per session, from short intervals to long tempo, on track, cycle path, grass, beach and hills.' ),
		array( 'Endurance · seniors & masters', 'Faster endurance group', '17+', 'Tue 6pm (track) · Sat 9am (Clyne Valley)', 'Free (track sessions £3.60)', 'Tue: Swansea University Athletics Centre track · Sat: meet at the Woodman, Blackpill, for Clyne Valley. Saturday details are confirmed in the group WhatsApp.', 'Andrew Cleverly, Marc Hobbs, Kevin Tobin', 'For runners chasing championship and league standards, competing on the national and international stage. Track, road and cross country: builds through the winter on road and XC, then prepares for the track season from 800m upwards.' ),
	);
	foreach ( $tg as $i => $t ) {
		$post( 'hs_training', $t[1], array( 'hs_ages' => $t[2], 'hs_when' => $t[3], 'hs_cost' => $t[4], 'hs_where' => $t[5], 'hs_coaches' => $t[6], 'hs_note' => $t[7] ), array( 'hs_training_tab' => $t[0] ), array( 'menu_order' => $i ) );
	}

	}
	/* ---------- volunteers ---------- */
	if ( ! $step || 'volunteers' === $step ) {
	$groups = function ( $events ) {
		$g = array();
		$tests = array( 'Juniors' => '/junior/i', 'Endurance' => '/endurance/i', 'Sprints & hurdles' => '/sprint|hurdle/i', 'Jumps & vault' => '/jump|vault/i', 'Throws' => '/throw|shot|discus|hammer|javelin/i' );
		foreach ( $tests as $name => $re ) {
			if ( preg_match( $re, $events ) ) {
				$g[] = $name;
			}
		}
		return $g;
	};
	$coaches = array(
		array( 'Adrianne Cleverly', 'Coaching Assistant', 'Juniors' ), array( 'Andrew Cleverly', 'Coaching Assistant', 'Juniors · Seniors Endurance (faster group)' ),
		array( 'Andrew Griffiths', 'Coach', 'Hurdles & Juniors' ), array( 'Andrew Jenkins', 'Coach', 'Juniors · Club Coaching Coordinator' ),
		array( 'Anthony Clements', 'CiRF & Coach', 'Juniors Endurance lead coach' ), array( 'Bethan Holley', 'Coaching Assistant', 'Juniors' ),
		array( 'Caroline Wade', 'Coach', 'Juniors' ), array( 'Catherine Collins', 'Coach', 'Juniors' ), array( 'Chris Higgs', 'Coach', 'Juniors' ),
		array( 'Craig Guite', 'Coach', 'Pole Vault & Juniors' ), array( 'Daniel Newton', 'Coach · LiRF', 'Seniors Endurance' ),
		array( 'David Lewis', 'Coaching Assistant', 'Juniors' ), array( 'David Dix', 'Coach', 'Juniors, Jumps & Throws' ),
		array( 'Emily Marchant', 'LiRF Coach', 'Seniors Endurance' ), array( 'Evan Lewis', 'Coach', 'Juniors' ),
		array( 'Evie Aitchison', 'Coaching Assistant', 'Juniors' ), array( 'Ieuan Hosgood', 'Coach', 'Pole Vault & Juniors' ),
		array( 'Islwyn Rees', 'Coach', 'Pole Vault & Juniors' ), array( 'Jane Hosgood', 'Coach', 'Juniors & Pole Vault' ),
		array( 'Jason Lee Cameron', 'Coach', 'Throws' ), array( 'Joanna Allen-Chaplin', 'Coach', 'Juniors' ), array( 'Jeff Squire', 'Coach', 'Javelin' ),
		array( 'Joseph Tucker', 'LiRF Coach', 'Seniors Endurance' ), array( 'John Griffiths', 'Coach', 'Juniors Endurance' ),
		array( 'Judith Gooding', 'Coach', 'Hurdles, Sprints & Long Jump · Seniors' ), array( 'Kelly Gipson', 'LiRF Coach', 'Seniors Endurance' ),
		array( 'Kevin Tobin', 'Coach', 'Juniors & Seniors Endurance' ), array( 'Lee Wheatley', 'Coaching Assistant', 'Juniors' ),
		array( 'Leigh Walker', 'Coach', 'Juniors' ), array( 'Lynne Brier', 'Coach', 'Sprints' ), array( 'Mark Beharrell', 'Coach', 'Pole Vault' ),
		array( 'Melissa Thomas', 'Coach', 'Juniors' ), array( 'Natalie Evans', 'Coaching Assistant', 'Juniors' ), array( 'Neal Merry', 'Coach', 'Sprints' ),
		array( 'Neil Bezodis', 'Coach', 'Pole Vault' ), array( 'Nelia Roberts', 'Coaching Assistant', 'Juniors' ),
		array( 'Owen Davies', 'LiRF Coach', 'Seniors Endurance' ), array( 'Paul Flynn', 'CiRF Coach', 'Seniors Endurance' ),
		array( 'Peter Roberts', 'Coach', 'Shot, Discus & Hammer' ), array( 'Rebekah Pratt', 'Coaching Assistant', 'Juniors' ),
		array( 'Richard Jones', 'Coaching Assistant', 'Javelin' ), array( 'Rob Buchan', 'Coach', 'Juniors Endurance' ),
		array( 'Rob Gilroy', 'Coach', 'Juniors Long & Triple Jump' ), array( 'Rob Jones', 'Coach', 'Sprints & High Jump' ),
		array( 'Sandra Birt', 'Coaching Assistant', 'Juniors' ), array( 'Shannon Thomas', 'Coaching Assistant', 'Juniors' ),
		array( 'Sophie Taylor', 'Coaching Assistant', 'Juniors' ), array( 'Steve Ingram', 'Coach', 'Long & Triple Jump' ),
		array( 'Stephen Ryan', 'Coach', 'High Jump' ), array( 'Thomas Guest', 'LiRF Coach', 'Seniors Endurance' ),
		array( 'Tina Gabb', 'CiRF Coach', 'Juniors' ), array( 'Graham (Tony) Morris', 'Coach', 'Juniors, Hurdles & Long Jump' ),
		array( 'Wendy Slade', 'Coach', 'Sprints' ),
	);
	foreach ( $coaches as $i => $c ) {
		$post( 'hs_volunteer', $c[0], array( 'hs_role' => $c[2], 'hs_detail' => 'UKA ' . $c[1] ), array( 'hs_volunteer_role' => 'Coaches', 'hs_event_group' => $groups( $c[2] ) ), array( 'menu_order' => $i ) );
	}
	$managers = array(
		array( 'Annell Prosser', 'U12 Girls', 'School years 5 & 6', 'xc' ), array( 'Brian Brookes', 'U12 Boys', 'School years 5 & 6', 'xc' ),
		array( 'Laura Brannigan', 'U14 Girls', 'School years 7 & 8', 'xc' ), array( 'Kevin Tobin', 'U14 Boys', 'School years 7 & 8', 'xc' ),
		array( 'Penny Gruffydd', 'U16 Girls', 'School years 9 & 10', 'xc' ), array( 'Jennie Mcadie', 'U16 Boys', 'School years 9 & 10', 'xc' ),
		array( 'Julie Hartley-Green', 'U18 & U20 Women', 'School years 11–14', 'xc' ), array( 'Tony Clements', 'U18 & U20 Men', 'School years 11–14', 'xc' ),
		array( 'Brian Fox', 'Senior & Masters Men & Women', 'Gwent League & West Glamorgan XC', 'xc' ),
		array( 'Vacant', 'U12 Boys', 'School years 5 & 6 · volunteer needed', 'tf' ), array( 'Hayley Shepherd', 'U12 Girls', 'School years 5 & 6', 'tf' ),
		array( 'Hannah Burton', 'U14 Boys', 'School years 7 & 8', 'tf' ), array( 'Emily Collins', 'U14 Girls', 'School years 7 & 8', 'tf' ),
		array( 'Christian Cobbold', 'U16 Boys', 'School years 9 & 10', 'tf' ), array( 'Catherine Collins', 'U16 Girls · Senior Women', 'School years 9 & 10 · Seniors', 'tf' ),
		array( 'Tony Clements', 'U18 & U20 Men', 'School years 11–14', 'tf' ), array( 'Wendy Slade', 'U18 & U20 Women', 'School years 11–14', 'tf' ),
		array( 'Lynne Brier', 'Senior Men', 'National Athletics League', 'tf' ),
	);
	foreach ( $managers as $i => $m ) {
		// Same person can manage in both lists, so match on name + list.
		$title = $m[0];
		$exists = get_posts( array( 'post_type' => 'hs_volunteer', 'title' => $title, 'numberposts' => -1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => 'hs_list', 'value' => $m[3] ), array( 'key' => 'hs_role', 'value' => $m[1] ) ) ) );
		if ( ! $exists ) {
			$id = wp_insert_post( array( 'post_type' => 'hs_volunteer', 'post_title' => $title, 'post_status' => 'publish', 'menu_order' => $i ) );
			update_post_meta( $id, 'hs_role', $m[1] );
			update_post_meta( $id, 'hs_detail', $m[2] );
			update_post_meta( $id, 'hs_list', $m[3] );
			wp_set_object_terms( $id, 'Team managers', 'hs_volunteer_role' );
			$made++;
		}
	}
	$people = array(
		array( 'Officials', 'Chris Berry', 'Club Officials Coordinator', 'Contact for officiating courses and rotas' ),
		array( 'Officials', 'Ian Griffiths', 'DBS Verifier', '' ), array( 'Officials', 'Andrew Jenkins', 'DBS Verifier', '' ), array( 'Officials', 'Lynne Brier', 'DBS Verifier', '' ),
		array( 'Officers & trustees', 'Barrie Owen', 'President', 'Trustee' ), array( 'Officers & trustees', 'Lynne Brier', 'Chairperson', 'Trustee · Female Welfare Officer' ),
		array( 'Officers & trustees', 'Ian Griffiths', 'Vice Chairperson', 'Trustee · Male Welfare Officer · Press Officer' ), array( 'Officers & trustees', 'Tony Clements', 'Secretary', 'Trustee' ),
		array( 'Officers & trustees', 'Dawn Holley', 'Treasurer', 'Trustee' ), array( 'Officers & trustees', 'Christine McCoubrey', 'Membership Secretary', 'Trustee' ),
		array( 'Officers & trustees', 'Brian Fox', 'Cross Country Secretary', 'Trustee' ), array( 'Officers & trustees', 'Nigel Jones', 'Trustee', '' ),
		array( 'Officers & trustees', 'Catherine Collins', 'Trustee', '' ), array( 'Officers & trustees', 'Caroline Wade', 'Trustee', '' ),
		array( 'Officers & trustees', 'Robert Beresford', 'Trustee', '' ), array( 'Officers & trustees', 'Ieuan Hosgood', 'Trustee', '' ),
		array( 'Officers & trustees', 'Megan Stratton-Thomas', 'Trustee', '' ), array( 'Officers & trustees', 'Neal Merry', 'Trustee', '' ),
	);
	foreach ( $people as $i => $p ) {
		$exists = get_posts( array( 'post_type' => 'hs_volunteer', 'title' => $p[1], 'numberposts' => 1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => 'hs_role', 'value' => $p[2] ) ), 'tax_query' => array( array( 'taxonomy' => 'hs_volunteer_role', 'field' => 'name', 'terms' => $p[0] ) ) ) );
		if ( ! $exists ) {
			$id = wp_insert_post( array( 'post_type' => 'hs_volunteer', 'post_title' => $p[1], 'post_status' => 'publish', 'menu_order' => $i ) );
			update_post_meta( $id, 'hs_role', $p[2] );
			update_post_meta( $id, 'hs_detail', $p[3] );
			wp_set_object_terms( $id, $p[0], 'hs_volunteer_role' );
			$made++;
		}
	}

	}
	/* ---------- records ---------- */
	if ( ! $step || 'records' === $step ) {
	$rec = array(
		array( 'Sprints & hurdles', '100m', 'Senior Men', 'Daniel Beadsley', '10.36', '2021' ),
		array( 'Sprints & hurdles', '100m', 'Senior Women', 'Hannah Brier', '11.26', '2025' ),
		array( 'Sprints & hurdles', '200m', 'Senior Men', 'Peter Maitland', '20.96', '1994' ),
		array( 'Sprints & hurdles', '400m', 'Senior Women', 'Hannah Brier', '52.23', '2026' ),
		array( 'Sprints & hurdles', '75m', 'U13', 'Joe Crosby', '12.08', '2011' ),
		array( 'Sprints & hurdles', '400m Hurdles', 'Senior Men', 'David Greene', '47.84', '2012' ),
		array( 'Sprints & hurdles', '110m Hurdles', 'Senior Men', 'Berwyn Price', '13.73', '1982' ),
		array( 'Sprints & hurdles', '100m Hurdles', 'Senior Women', 'Caryl Granville', '13.45', '2021' ),
		array( 'Middle & long distance', '800m', 'Senior Women', 'Hayley Tullett', '2:00.49', '2003' ),
		array( 'Middle & long distance', '1500m', 'Senior Men', 'Chris Gowell', '3:39.08', '2014' ),
		array( 'Middle & long distance', '5000m', 'Senior Men', 'Ian Hamer', '13:09.80', '1992' ),
		array( 'Middle & long distance', '3000m Steeplechase', 'Senior Men', 'Jonathan Hopkins', '8:30.52', '2018' ),
		array( 'Middle & long distance', 'Marathon', 'Senior Women', 'Caryl Jones', '2:34:12', '2017' ),
		array( 'Jumps & vault', 'Long Jump', 'Senior Men', 'Gareth Davies', '7.62m', '1994' ),
		array( 'Jumps & vault', 'High Jump', 'Senior Women', 'Ceri Stokoe', '1.79m', '2006' ),
		array( 'Jumps & vault', 'Pole Vault', 'Senior Men', 'Timothy Thomas', '5.40m', '1997' ),
		array( 'Jumps & vault', 'Triple Jump', 'U17 Men', 'Osaze Aghedo', '14.23m', '2015' ),
		array( 'Throws', 'Shot 7.26kg', 'Senior Men', 'Ryan Spencer-Jones', '15.45m', '2008' ),
		array( 'Throws', 'Discus 1kg', 'Senior Women', 'Philippa Roles', '61.41m', '2008' ),
		array( 'Throws', 'Hammer 7.26kg', 'Senior Men', 'Adam King', '63.98m', '2020' ),
		array( 'Throws', 'Javelin 600g', 'Senior Women', 'Bethany Moule', '48.81m', '2022' ),
		array( 'Walks & relays', '3000m Walk', 'U17 Men', 'Antonio Cirillo', '13:59.76', '2007' ),
		array( 'Walks & relays', '3000m Walk', 'U15 Girls', 'Megan Stratton-Thomas', '17:18.96', '2013' ),
		array( 'Walks & relays', '4 × 100m', 'Senior Men', 'Hawker, Beadsley, Warzocha, Kovalenko', '40.82', '2024' ),
	);
	foreach ( $rec as $i => $r ) {
		$exists = get_posts( array( 'post_type' => 'hs_record', 'title' => $r[3], 'numberposts' => 1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => 'hs_event', 'value' => $r[1] ), array( 'key' => 'hs_age_group', 'value' => $r[2] ) ) ) );
		if ( ! $exists ) {
			$id = wp_insert_post( array( 'post_type' => 'hs_record', 'post_title' => $r[3], 'post_status' => 'publish', 'menu_order' => $i ) );
			foreach ( array( 'hs_event' => $r[1], 'hs_age_group' => $r[2], 'hs_mark' => $r[4], 'hs_year' => $r[5] ) as $k => $v ) {
				update_post_meta( $id, $k, $v );
			}
			wp_set_object_terms( $id, $r[0], 'hs_event_group' );
			$made++;
		}
	}

	}
	/* ---------- pages, news, menus (content lives in hs_seed_pages) ---------- */
	if ( ! $step || 'pages' === $step ) {
	$made += hs_seed_pages();

	}
	wp_defer_term_counting( false );
	return sprintf( 'Starter content loaded: %d new items. Anything that already existed was left alone.', $made );
}

function hs_seed_pages() {
	$made = 0;
	$page = function ( $title, $slug, $template = '', $content = '', $excerpt = '' ) use ( &$made ) {
		$p = get_page_by_path( $slug );
		if ( $p ) {
			return $p->ID;
		}
		$id = wp_insert_post( array( 'post_type' => 'page', 'post_title' => $title, 'post_name' => $slug, 'post_status' => 'publish', 'post_content' => $content, 'post_excerpt' => $excerpt ) );
		if ( $template ) {
			update_post_meta( $id, '_wp_page_template', $template );
		}
		$made++;
		return $id;
	};
	$tpl = HS_CORE_DIR . 'includes/seed-content/';
	$c   = fn( $f ) => file_exists( $tpl . $f ) ? file_get_contents( $tpl . $f ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local plugin file.

	// Remove WordPress's untouched sample content.
	foreach ( array( 'hello-world' => 'post', 'sample-page' => 'page' ) as $slug => $type ) {
		$p = get_page_by_path( $slug, OBJECT, $type );
		if ( $p && $p->post_modified_gmt === $p->post_date_gmt ) {
			wp_trash_post( $p->ID );
		}
	}
	$home = $page( 'Home', 'home' );
	$news = $page( 'News', 'news' );
	$ids = array(
		'results'    => $page( 'Results', 'results', 'page-templates/results.php', '', 'Every team league in one place' ),
		'training'   => $page( 'Training', 'training', 'page-templates/training.php', '', 'Find your training group' ),
		'volunteers' => $page( 'Volunteers', 'volunteers', 'page-templates/volunteers.php', '', 'The people who make it happen' ),
		'records'    => $page( 'Club records', 'club-records', 'page-templates/records.php', '', 'The marks to beat' ),
		'join'       => $page( 'Join', 'join', '', $c( 'join.html' ), 'Join Swansea Harriers' ),
		'about'      => $page( 'Club', 'club', '', $c( 'about.html' ), 'From Cockett Boys Club to world champions' ),
		'welfare'    => $page( 'Safeguarding & welfare', 'safeguarding', '', $c( 'welfare.html' ), 'Everyone safe, everyone welcome' ),
		'contact'    => $page( 'Contact', 'contact', '', $c( 'contact.html' ), 'Find us at Sketty Lane' ),
		'support'    => $page( 'Support the club', 'support', '', $c( 'support.html' ), 'Help keep Harriers running' ),
	);
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home );
	update_option( 'page_for_posts', $news );

	$posts = array(
		array( 'Alan Davies wins M70 title at British & Irish Masters XC; three Harriers for Wales in Glasgow', 'Results', '2026-11-18 18:00:00', 'M70 Alan Davies won his age group at the British &amp; Irish Masters Cross Country International in Dublin, and three Harriers represented Wales at the Scottish Schools Indoor Combined Events in Glasgow.', '<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">British &amp; Irish Masters Cross Country International, Abbotstown, Dublin · Sat 14 Nov</h3><!-- /wp:heading --><!-- wp:paragraph --><p><strong>Alan Davies (M70)</strong> won his age group against the best masters runners from England, Ireland, Scotland, Wales and Northern Ireland, placing 13th overall in the combined M65+ race.</p><!-- /wp:paragraph --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Scottish Schools Indoor Combined Events Invitational, Glasgow · Sat 14 Nov</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Three Harriers were selected to represent Wales against the best junior combined events athletes in the UK.</p><!-- /wp:paragraph --><!-- wp:list --><ul><li><strong>Tabitha Ventress-Brett (U18W)</strong>: team bronze for Wales, 13th individually with 3027 points (11.05m shot, 9.75 60m hurdles, 4.67m long jump, 1.42m high jump, 2:32.32 800m)</li><li><strong>Aaron Campbell (U18M)</strong>: 11th with 2724 points (9.61 60m hurdles, 9.74m shot, 23.98 200m, 5.38m long jump, 2:30.68 800m)</li><li><strong>Katriel Udoh (U16)</strong>: 15th with 2540 points after bad luck in the hurdles (1.72m high jump, 5.41m long jump, 9.00m shot, 2:44.53 800m)</li></ul><!-- /wp:list -->' ),
		array( 'Junior Awards Evening: Friday 20 November', 'Announcements', '2026-11-14 18:00:00', 'Celebrating our U12, U14 and U16 athletes who competed for the club this summer. Friday 20 November from 6.30pm at Swansea Dockers Club, St Thomas.', '<!-- wp:paragraph --><p>Our awards night for U12, U14 and U16 athletes who competed for Swansea Harriers in the 2026 summer season takes place on <strong>Friday 20 November from 6.30pm</strong> at <strong>Swansea Dockers Club, St Thomas, SA1 8BT</strong>.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Tickets are £12 per adult and £6 per child, including a buffet, booked online through the club\'s ticket page. The booking deadline was Friday 6 November so final numbers could be confirmed with the venue. Questions to info@swanseaharriers.co.uk.</p><!-- /wp:paragraph --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Awards on the night</h3><!-- /wp:heading --><!-- wp:list --><ul><li>U12, U14 &amp; U16 Team Managers\' Athlete of the Year</li><li>U12, U14 &amp; U16 Loyalty Awards for athletes who competed regularly in league fixtures during 2026</li><li>U14 &amp; U16 performance certificates (England Athletics PB award level 4 and above)</li><li>U14 &amp; U16 Gold Awards, based on the 2026 Gold Award Standards</li><li>U12 participation certificates</li></ul><!-- /wp:list --><!-- wp:paragraph --><p>The main awards night for U18, U20, senior and masters athletes is planned for early 2027. Date and venue to be confirmed.</p><!-- /wp:paragraph -->' ),
		array( 'Good luck to Matilda Quick at the World U20 Championships', 'Announcements', '2026-08-04 18:00:00', 'U20W Matilda Quick has been selected to represent Great Britain &amp; Northern Ireland in the heptathlon at the World Athletics U20 Championships in Oregon, USA.', '<!-- wp:paragraph --><p>Congratulations to <strong>Matilda Quick (U20W)</strong>, selected to represent Great Britain &amp; Northern Ireland in the <strong>heptathlon</strong> at the World Athletics U20 Championships in Oregon, USA.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Matilda joined Swansea Harriers in 2017 as an under-11 and has been a dedicated member and team player ever since. She is a former pupil of Ynystawe Primary School and Morriston Comprehensive School.</p><!-- /wp:paragraph --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Schedule (UK times)</h3><!-- /wp:heading --><!-- wp:list --><ul><li><strong>Wed 5 Aug:</strong> 100m hurdles 6pm · high jump 6.50pm</li><li><strong>Thu 6 Aug:</strong> shot 2.20am · 200m 3.35am · long jump 6.25pm · javelin 8.30pm</li><li><strong>Fri 7 Aug:</strong> 800m 2.10am</li></ul><!-- /wp:list -->' ),
		array( 'Good luck to Harrison Walsh at the Commonwealth Games', 'Announcements', '2026-07-28 18:00:00', 'Harrison Walsh competes in the men\'s para discus final for Wales at the Glasgow Commonwealth Games on 29 July, and joins the club\'s Hall of Fame.', '<!-- wp:paragraph --><p><strong>Harrison Walsh</strong> represents Wales in the men\'s para discus final at the <strong>Commonwealth Games in Glasgow on Wednesday 29 July</strong>.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Harrison is also being added to the club\'s Hall of Fame, joining the Harriers who have competed at the Olympic, World, European and Commonwealth championships.</p><!-- /wp:paragraph -->' ),
		array( 'Thank you to our officials', 'Announcements', '2026-07-26 18:00:00', 'Our volunteer officials make every league, cup and championship fixture possible. New volunteers are always welcome. Contact officials coordinator Chris Berry.', '<!-- wp:paragraph --><p>A big thank you to the volunteer officials who give up their weekends to support our league, cup and championship fixtures. Without them, none of our athletes could compete.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Every match needs officials, and Level 1 courses are available in Timekeeper, Field Judge, Track Judge and Start/Marksman. If you\'d like to get involved, contact our officials coordinator <strong>Chris Berry</strong>.</p><!-- /wp:paragraph -->' ),
		array( 'Gwent League Match 2: three Harriers win at Llandaff Fields', 'Results', '2026-11-09 18:00:00', 'Emily Dando (U16), Holly Humphreys (U20) and Ifan Lloyd (M65) won their races. U12 girls and U20 women won again as a team, and the senior men sit 3rd in Division 1 after two matches.', '<!-- wp:paragraph --><p>A huge turnout of close to 100 Harriers at the Cardiff Cross Challenge, Round 2 of the John Collins Cross Country League, on Sunday 8 November. Emily Dando (U16G), Holly Humphreys (U20W) and Ifan Lloyd (M65+) all won their races.</p><!-- /wp:paragraph --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Team positions in the league</h3><!-- /wp:heading --><!-- wp:list --><ul><li><strong>U12 girls, 1st:</strong> 7th Molly Shelmerdine, 8th Zara De Wall, 9th Ada Williams, 31st Lucy Hughes</li><li><strong>U12 boys, 5th:</strong> 2nd Joshua Thompson, 25th Elias Kihara, 48th John Jones, 77th Joel Williams</li><li><strong>U14 girls, 1st:</strong> 8th Olive Knutson-Watson, 9th Molly-Mae McQueen, 19th Sienna Wooliscroft, 22nd Leila Finney</li><li><strong>U14 boys, 8th:</strong> 6th Jackson Jones, 31st Aeron Hill, 67th Max Rankin, 93rd Ellis Davies</li><li><strong>U16 girls, 2nd:</strong> 1st Emily Dando, 18th Zoe Thompson, 20th Aisha Keogh, 22nd Emily Evans-Williams</li><li><strong>U16 boys, 3rd:</strong> 5th Tomi Jones, 6th Oliver Cobbold, 19th Sean Mu, 21st Carwyn Stock</li><li><strong>U20 women, 1st:</strong> 1st Holly Humphreys, 2nd Millie Pierce, 9th Sioned John, 13th Chloe Hartley-Green</li><li><strong>U18 men, 10th:</strong> 10th Osian Morgan, 52nd Harvey Southam, 57th Iestyn Rees-Jones, 59th Joseph Newton</li><li><strong>Senior &amp; masters women, 7th in Division 1:</strong> 8th Beatrice Rusby, 90th Michelle Cole, 142nd Lois Woodward, 219th Felicity Jones, 259th Sian Phillips</li><li><strong>Senior &amp; masters men, 3rd in Division 1:</strong> 27th Matthew Rees, 30th Finley Hines, 33rd Brychan Price-Davies, 43rd Morgan James, 60th Daniel May</li><li><strong>Masters men 65+:</strong> 1st Ifan Lloyd, 20th Peter Osborne</li></ul><!-- /wp:list --><!-- wp:paragraph --><p>Isabelle Lawley (U18W, 22nd) and Jack Sullivan and Dylan Strawbridge (U20M) also ran. Full results are on the <a href="https://www.gwent-league.org.uk/">Gwent League site</a> and on our Results page.</p><!-- /wp:paragraph -->' ),
		array( 'Welsh Inter Regional XC Championships @ Newtown', 'Fixtures', '2026-10-28 18:00:00', 'Saturday 21 November at Dolerw Park, Newtown. Athletes can represent West Wales by birth or residence. Team managers will confirm selection.' ),
		array( 'West Glamorgan XC: men top Division 1 after two rounds', 'Results', '2026-10-20 18:00:00', 'Finley Hines won at Tairgwaith and Marc Hobbs at Pembrey as the men won both rounds.' ),
		array( 'Gwent League Match 1: senior men second at Pembrey', 'Results', '2026-10-12 18:00:00', 'Dafydd Jones won the senior race, with U20 women and U12 girls topping their team results.' ),
		array( 'Honours Round Up', 'Announcements', '2026-10-05 18:00:00', 'Harriers selected to represent Wales and the regions this month.' ),
	);
	$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
	$author = get_current_user_id() ? get_current_user_id() : (int) ( $admins[0] ?? 0 );
	foreach ( $posts as $p ) {
		if ( ! get_posts( array( 'post_type' => 'post', 'title' => $p[0], 'numberposts' => 1, 'fields' => 'ids' ) ) ) {
			$id = wp_insert_post( array( 'post_type' => 'post', 'post_title' => $p[0], 'post_status' => 'publish', 'post_author' => $author, 'post_date' => $p[2], 'post_content' => $p[4] ?? '<!-- wp:paragraph --><p>' . esc_html( $p[3] ) . '</p><!-- /wp:paragraph -->', 'post_excerpt' => wp_strip_all_tags( $p[3] ) ) );
			wp_set_object_terms( $id, $p[1], 'category' );
			$made++;
		}
	}

	// Menus.
	$menu = function ( $name, $location, $items ) use ( &$made ) {
		$obj = wp_get_nav_menu_object( $name );
		if ( $obj ) {
			$id = $obj->term_id;
		} else {
			$id = wp_create_nav_menu( $name );
			foreach ( $items as $i => $it ) {
				$args = array( 'menu-item-title' => $it[0], 'menu-item-status' => 'publish', 'menu-item-position' => $i + 1 );
				if ( is_int( $it[1] ) ) {
					$args += array( 'menu-item-object-id' => $it[1], 'menu-item-object' => 'page', 'menu-item-type' => 'post_type' );
				} else {
					$args += array( 'menu-item-url' => $it[1], 'menu-item-type' => 'custom' );
				}
				if ( ! empty( $it[2] ) ) {
					$args['menu-item-description'] = $it[2];
				}
				if ( ! empty( $it[3] ) ) {
					$args['menu-item-classes'] = $it[3];
				}
				wp_update_nav_menu_item( $id, 0, $args );
			}
			$made++;
		}
		$locs = get_theme_mod( 'nav_menu_locations', array() );
		$locs[ $location ] = $id;
		set_theme_mod( 'nav_menu_locations', $locs );
	};
	$fixtures = home_url( '/fixtures/' );
	$menu( 'Main menu', 'primary', array(
		array( 'Results', $ids['results'] ), array( 'Fixtures', $fixtures ), array( 'News', $news ), array( 'Training', $ids['training'] ),
		array( 'Club', $ids['about'] ), array( 'Volunteers', $ids['volunteers'] ), array( 'Contact', $ids['contact'] ),
	) );
	$menu( 'Athlete tools', 'tools', array(
		array( 'Power of 10', 'https://www.thepowerof10.info', '', 'is-highlight' ), array( 'OpenTrack', 'https://opentrack.run' ),
		array( 'Fabian4', 'https://www.fabian4.co.uk' ), array( 'ResultsBase', 'https://www.resultsbase.net' ),
		array( 'Race Timing Solutions', 'https://www.racetimingsolutions.co.uk' ),
	) );
	$menu( 'Explore tiles', 'explore', array(
		array( 'Results & league tables', $ids['results'], 'Gwent, West Glam, YDL and National League', 'icon-trophy is-dark' ),
		array( 'Fixtures', $fixtures, 'Every race this season, with where to enter', 'icon-calendar' ),
		array( 'News', $news, 'Round-ups, selections and club announcements', 'icon-news' ),
		array( 'Training groups', $ids['training'], 'Under 10s to masters, on and off track', 'icon-run is-dark' ),
		array( 'Join or renew', $ids['join'], 'Fees, what\'s included and how to sign up', 'icon-join' ),
		array( 'History & Hall of Fame', $ids['about'], 'Since 1962 · world, Olympic and Commonwealth Harriers', 'icon-star' ),
		array( 'Club records', $ids['records'], 'The marks to beat, by event and age group', 'icon-chart' ),
		array( 'Coaches & volunteers', $ids['volunteers'], 'Coaches, team managers, officials and trustees', 'icon-people is-dark' ),
		array( 'Safeguarding & welfare', $ids['welfare'], 'Welfare officers, policies and governance', 'icon-shield' ),
		array( 'Location & contact', $ids['contact'], 'Find us at Sketty Lane or send a message', 'icon-pin' ),
		array( 'Kit & support the club', $ids['support'], 'Club kit, easyfundraising and donations', 'icon-shirt' ),
		array( 'Enter a race', $fixtures, 'OpenTrack, Fabian4, Welsh Athletics and more', 'icon-flag is-dark' ),
	) );
	$menu( 'Footer · Compete', 'footer-1', array(
		array( 'Results & league tables', $ids['results'] ), array( 'Fixtures', $fixtures ), array( 'Club records', $ids['records'] ), array( 'Power of 10', 'https://www.thepowerof10.info' ),
	) );
	$menu( 'Footer · Club', 'footer-2', array(
		array( 'History, honours & Hall of Fame', $ids['about'] ), array( 'Coaches, managers & officials', $ids['volunteers'] ), array( 'Club kit & donate', $ids['support'] ), array( 'Join or renew', $ids['join'] ),
	) );
	$menu( 'Footer · Welfare & governance', 'footer-3', array(
		array( 'Safeguarding & welfare', $ids['welfare'] ), array( 'Location & contact', $ids['contact'] ), array( 'Privacy & cookies', home_url( '/privacy-policy/' ) ),
	) );
	return $made;
}
