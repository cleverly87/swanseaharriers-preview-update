<?php
/**
 * Simple, plugin-free field boxes for each content type.
 * Every field is stored as post meta under its key (all keys start with "hs_").
 * Plain-English labels and help text are deliberate: the editor is not technical.
 */

defined( 'ABSPATH' ) || exit;

function hs_field_definitions() {
	$platforms = array(
		''                      => '— none —',
		'OpenTrack'             => 'OpenTrack',
		'Fabian4'               => 'Fabian4',
		'Welsh Athletics'       => 'Welsh Athletics',
		'Race Timing Solutions' => 'Race Timing Solutions',
		'ResultsBase'           => 'ResultsBase',
		'Entry Central'         => 'Entry Central',
		'Full On Sport'         => 'Full On Sport',
		'Other'                 => 'Other website',
	);

	return array(
		'hs_fixture'   => array(
			'title'  => 'Fixture details',
			'intro'  => 'The title is the race name, e.g. "Gwent League XC · Match 3 of 5". Use the box on the right to pick a fixture type (League, Road, Championship…).',
			'fields' => array(
				'hs_date'           => array( 'label' => 'Date', 'type' => 'date', 'required' => true ),
				'hs_end_date'       => array( 'label' => 'End date', 'type' => 'date', 'help' => 'Only for events over more than one day. Leave blank otherwise.' ),
				'hs_time'           => array( 'label' => 'Start time', 'type' => 'text', 'help' => 'Optional, e.g. 11:00 or "from 10:30".' ),
				'hs_venue'          => array( 'label' => 'Venue', 'type' => 'text', 'help' => 'e.g. Llandaff Fields, Cardiff' ),
				'hs_details'        => array( 'label' => 'Short details', 'type' => 'text', 'help' => 'One line shown under the venue, e.g. "U12 to Masters" or "hosted by Run4All".' ),
				'hs_discipline'     => array( 'label' => 'Where is it run?', 'type' => 'select', 'options' => array( 'off' => 'Off track (cross country, road, trail)', 'on' => 'On the track (track & field)' ) ),
				'hs_entry_url'      => array( 'label' => 'Entry link', 'type' => 'url', 'help' => 'Where athletes enter. Leave blank for club league fixtures: they show "Team info" instead.' ),
				'hs_entry_platform' => array( 'label' => 'Entry website', 'type' => 'select', 'options' => $platforms, 'help' => 'Shown on the button, e.g. "Enter · OpenTrack".' ),
				'hs_info_url'       => array( 'label' => 'Team info link', 'type' => 'url', 'help' => 'Optional: a news post with selection or travel details.' ),
			),
		),
		'hs_league'    => array(
			'title'  => 'League settings',
			'intro'  => 'One entry per league per season, e.g. "Gwent League 2026/27". Rounds (fixtures and their results) are added under Leagues & results → Rounds.',
			'fields' => array(
				'hs_display_name'  => array( 'label' => 'Name shown on the site', 'type' => 'text', 'help' => 'e.g. Gwent League' ),
				'hs_full_name'     => array( 'label' => 'Full name', 'type' => 'text', 'help' => 'e.g. John H Collins Gwent Cross Country League · 5 fixtures' ),
				'hs_squad'         => array( 'label' => 'Who competes', 'type' => 'text', 'help' => 'e.g. U12 to Masters' ),
				'hs_season_type'   => array( 'label' => 'Season', 'type' => 'select', 'options' => array( 'winter' => 'Winter · cross country', 'summer' => 'Summer · track & field' ) ),
				'hs_season_label'  => array( 'label' => 'Season year', 'type' => 'text', 'help' => 'e.g. 2026/27 or 2026' ),
				'hs_current'       => array( 'label' => 'Current season', 'type' => 'checkbox', 'help' => 'Tick to show on the Results page. Untick at the end of the season and it moves to the archive.' ),
				'hs_official_url'  => array( 'label' => 'Official results link', 'type' => 'url' ),
				'hs_score_label'   => array( 'label' => 'Score column heading', 'type' => 'text', 'help' => 'e.g. Score, Match points' ),
				'hs_footnote'      => array( 'label' => 'Note under the table', 'type' => 'textarea', 'rows' => 2 ),
				'hs_standings'     => array( 'label' => 'Standings panel', 'type' => 'textarea', 'rows' => 4, 'help' => 'One card per line: Label | Value | Detail. Example: Men · after 2 of 5 | 1st · Div 1 | 16 league pts. Automatic leagues fill this in after each import.' ),
				'hs_source'        => array( 'label' => 'Results come from', 'type' => 'select', 'options' => array( 'manual' => 'Typed in by hand', 'gwent' => 'Gwent League website (automatic)', 'westglam' => 'West Glamorgan League website (automatic)' ) ),
				'hs_club_name'     => array( 'label' => 'Our club name on their site', 'type' => 'text', 'help' => 'Usually "Swansea Harriers". Used to find our teams in their results.' ),
				'hs_gwent_base'    => array( 'label' => 'Gwent: website address', 'type' => 'url', 'help' => 'https://www.gwent-league.org.uk' ),
				'hs_gwent_folder'  => array( 'label' => 'Gwent: results folder', 'type' => 'text', 'help' => 'The year folder their results live in, e.g. 2026' ),
				'hs_gwent_code'    => array( 'label' => 'Gwent: season code', 'type' => 'text', 'help' => 'Two digits in their file names, e.g. 26 (sm261t.htm = senior men, 2026, match 1)' ),
				'hs_wg_male_url'   => array( 'label' => 'West Glam: men\'s team table page', 'type' => 'url', 'help' => 'https://www.westglamleague.co.uk/team-tables-male/' ),
				'hs_wg_female_url' => array( 'label' => 'West Glam: women\'s team table page', 'type' => 'url', 'help' => 'https://www.westglamleague.co.uk/team-tables-female/' ),
			),
		),
		'hs_round'     => array(
			'title'  => 'Round details & results',
			'intro'  => 'One round of a league. For automatic leagues you only need the league, round number, date and venue: results fill themselves in after the race.',
			'fields' => array(
				'hs_league'     => array( 'label' => 'League', 'type' => 'league', 'required' => true ),
				'hs_round_no'   => array( 'label' => 'Round number', 'type' => 'number', 'required' => true ),
				'hs_date'       => array( 'label' => 'Date', 'type' => 'date', 'required' => true ),
				'hs_venue'      => array( 'label' => 'Venue', 'type' => 'text' ),
				'hs_cancelled'  => array( 'label' => 'Cancelled', 'type' => 'checkbox' ),
				'hs_source_url' => array( 'label' => 'Results page for this round', 'type' => 'url', 'help' => 'West Glam only: the venue page, e.g. https://www.westglamleague.co.uk/pembrey/. Gwent works this out itself.' ),
				'hs_auto'       => array( 'label' => 'Import results automatically', 'type' => 'checkbox', 'default' => '1', 'help' => 'Untick to stop the importer changing this round (e.g. after hand corrections).' ),
				'hs_status'     => array( 'label' => 'Results status', 'type' => 'select', 'options' => array( 'provisional' => 'Provisional', 'final' => 'Final' ) ),
				'hs_rows'       => array( 'label' => 'Results', 'type' => 'textarea', 'rows' => 14, 'help' => 'One race per line: Race | Position | Out of | Score | First Harrier home. Example: U12 Girls | 1 | of 27 teams | 55 | Molly Shelmerdine · 10th' ),
				'hs_last_import' => array( 'label' => 'Last checked', 'type' => 'readonly' ),
			),
		),
		'hs_training'  => array(
			'title'  => 'Training group details',
			'intro'  => 'The title is the group name. Pick "Who it\'s for" on the right; that decides which tab it appears under.',
			'fields' => array(
				'hs_ages'    => array( 'label' => 'Ages / level', 'type' => 'text' ),
				'hs_when'    => array( 'label' => 'When', 'type' => 'text', 'help' => 'e.g. Tue & Thu, meet 6.15pm for 6.30pm' ),
				'hs_cost'    => array( 'label' => 'Cost', 'type' => 'text' ),
				'hs_where'   => array( 'label' => 'Where', 'type' => 'text' ),
				'hs_coaches' => array( 'label' => 'Coaches', 'type' => 'text' ),
				'hs_note'    => array( 'label' => 'Description', 'type' => 'textarea', 'rows' => 3 ),
			),
		),
		'hs_volunteer' => array(
			'title'  => 'Volunteer details',
			'intro'  => 'The title is the person\'s name (or "Vacant"). Pick their role on the right, and an event group for coaches.',
			'fields' => array(
				'hs_role'   => array( 'label' => 'What they do', 'type' => 'text', 'help' => 'Coaches: events (e.g. Sprints & High Jump). Managers: team (e.g. U12 Girls). Officers: post (e.g. Treasurer).' ),
				'hs_detail' => array( 'label' => 'Extra detail', 'type' => 'text', 'help' => 'e.g. UKA Coach, School years 5 & 6, Trustee' ),
				'hs_list'   => array( 'label' => 'Team list', 'type' => 'select', 'options' => array( '' => '—', 'xc' => 'Road & cross country', 'tf' => 'Track & field' ), 'help' => 'Team managers only.' ),
			),
		),
		'hs_record'    => array(
			'title'  => 'Record details',
			'intro'  => 'The title is the athlete (or relay team). Pick the event group on the right.',
			'fields' => array(
				'hs_event'     => array( 'label' => 'Event', 'type' => 'text', 'help' => 'e.g. 400m Hurdles' ),
				'hs_age_group' => array( 'label' => 'Age group', 'type' => 'select', 'options' => array( 'Senior Men' => 'Senior Men', 'Senior Women' => 'Senior Women', 'U20 Men' => 'U20 Men', 'U20 Women' => 'U20 Women', 'U17 Men' => 'U17 Men', 'U17 Women' => 'U17 Women', 'U15 Boys' => 'U15 Boys', 'U15 Girls' => 'U15 Girls', 'U13 Boys' => 'U13 Boys', 'U13 Girls' => 'U13 Girls', 'U13' => 'U13' ) ),
				'hs_mark'      => array( 'label' => 'Mark', 'type' => 'text', 'help' => 'e.g. 47.84 or 7.62m' ),
				'hs_year'      => array( 'label' => 'Year', 'type' => 'number' ),
			),
		),
	);
}

add_action( 'add_meta_boxes', function () {
	foreach ( hs_field_definitions() as $type => $def ) {
		add_meta_box( 'hs_fields', $def['title'], 'hs_render_fields', $type, 'normal', 'high', array( 'def' => $def ) );
	}
} );

function hs_render_fields( $post, $box ) {
	$def = $box['args']['def'];
	wp_nonce_field( 'hs_save_fields', 'hs_fields_nonce' );
	echo '<p style="color:#4A5A50">' . esc_html( $def['intro'] ) . '</p><table class="form-table" role="presentation"><tbody>';
	foreach ( $def['fields'] as $key => $f ) {
		$is_new = 'auto-draft' === $post->post_status;
		$val    = get_post_meta( $post->ID, $key, true );
		if ( $is_new && '' === $val && isset( $f['default'] ) ) {
			$val = $f['default'];
		}
		$required = ! empty( $f['required'] );
		$help_id  = ! empty( $f['help'] ) ? $key . '-help' : '';
		// Attributes shared by every control: id, name, required, and a link to the help text for screen readers.
		$common = sprintf(
			'id="%1$s" name="%1$s"%2$s%3$s',
			esc_attr( $key ),
			$required ? ' required aria-required="true"' : '',
			$help_id ? ' aria-describedby="' . esc_attr( $help_id ) . '"' : ''
		);
		echo '<tr><th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $f['label'] ) . ( $required ? ' <span aria-hidden="true">*</span><span class="screen-reader-text">(required)</span>' : '' ) . '</label></th><td>';
		switch ( $f['type'] ) {
			case 'textarea':
				echo '<textarea ' . $common . ' rows="' . absint( $f['rows'] ?? 4 ) . '" class="large-text code">' . esc_textarea( $val ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $common is built from escaped parts above.
				break;
			case 'select':
				echo '<select ' . $common . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $common is built from escaped parts above.
				foreach ( $f['options'] as $ov => $ol ) {
					echo '<option value="' . esc_attr( $ov ) . '"' . selected( $val, $ov, false ) . '>' . esc_html( $ol ) . '</option>';
				}
				echo '</select>';
				break;
			case 'checkbox':
				echo '<input type="checkbox" ' . $common . ' value="1"' . checked( $val, '1', false ) . '> <label for="' . esc_attr( $key ) . '">Yes</label>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $common is built from escaped parts above.
				break;
			case 'league':
				$leagues = get_posts( array( 'post_type' => 'hs_league', 'numberposts' => -1, 'post_status' => 'any', 'orderby' => 'menu_order title', 'order' => 'ASC' ) );
				echo '<select ' . $common . '><option value="">— choose —</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $common is built from escaped parts above.
				foreach ( $leagues as $l ) {
					echo '<option value="' . absint( $l->ID ) . '"' . selected( (int) $val, $l->ID, false ) . '>' . esc_html( $l->post_title ) . '</option>';
				}
				echo '</select>';
				break;
			case 'readonly':
				echo '<span>' . ( $val ? esc_html( $val ) : '—' ) . '</span>';
				break;
			default:
				$type = in_array( $f['type'], array( 'date', 'url', 'number' ), true ) ? $f['type'] : 'text';
				echo '<input type="' . esc_attr( $type ) . '" ' . $common . ' value="' . esc_attr( $val ) . '" class="regular-text">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $common is built from escaped parts above.
		}
		if ( $help_id ) {
			echo '<p class="description" id="' . esc_attr( $help_id ) . '">' . esc_html( $f['help'] ) . '</p>';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
}

add_action( 'save_post', function ( $post_id, $post ) {
	if ( ! isset( $_POST['hs_fields_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['hs_fields_nonce'] ), 'hs_save_fields' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$defs = hs_field_definitions();
	if ( empty( $defs[ $post->post_type ] ) ) {
		return;
	}
	foreach ( $defs[ $post->post_type ]['fields'] as $key => $f ) {
		if ( 'readonly' === $f['type'] ) {
			continue;
		}
		if ( 'checkbox' === $f['type'] ) {
			update_post_meta( $post_id, $key, isset( $_POST[ $key ] ) ? '1' : '0' );
			continue;
		}
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$raw = wp_unslash( $_POST[ $key ] );
		switch ( $f['type'] ) {
			case 'url':
				$v = esc_url_raw( trim( $raw ) );
				break;
			case 'textarea':
				$v = sanitize_textarea_field( $raw );
				break;
			case 'number':
			case 'league':
				$v = '' === trim( $raw ) ? '' : (string) absint( $raw );
				break;
			case 'date':
				$v = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw ) ? $raw : '';
				break;
			default:
				$v = sanitize_text_field( $raw );
		}
		update_post_meta( $post_id, $key, $v );
	}
	// A round belongs to its league: keep post_parent in step so queries are simple.
	if ( 'hs_round' === $post->post_type ) {
		$league = (int) get_post_meta( $post_id, 'hs_league', true );
		if ( $league && (int) $post->post_parent !== $league ) {
			global $wpdb;
			// Direct update avoids re-triggering save_post.
			$wpdb->update( $wpdb->posts, array( 'post_parent' => $league ), array( 'ID' => $post_id ) );
			clean_post_cache( $post_id );
		}
	}
}, 10, 2 );
