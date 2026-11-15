<?php
/**
 * Content types.
 *
 * hs_fixture   Fixtures (public, archive at /fixtures/)
 * hs_league    A league in a season, e.g. "Gwent League 2026/27" (admin only)
 * hs_round     One fixture's results within a league; child of hs_league (admin only)
 * hs_training  Training groups
 * hs_volunteer Coaches, team managers, officials, officers
 * hs_record    Club records
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'hs_register_post_types' );

function hs_register_post_types() {
	$common = array(
		// Only Fixtures use the block editor, so only they need the REST API.
		// Everything else stays out of the public /wp-json/ feeds.
		'show_in_rest'  => false,
		'show_ui'       => true,
		'menu_position' => 21,
	);

	register_post_type( 'hs_fixture', array_merge( $common, array(
		'labels'        => hs_labels( 'Fixture', 'Fixtures' ),
		'public'        => true,
		'has_archive'   => 'fixtures',
		'rewrite'       => array( 'slug' => 'fixtures', 'with_front' => false ),
		'menu_icon'     => 'dashicons-calendar-alt',
		'supports'      => array( 'title', 'editor', 'thumbnail' ),
		'show_in_rest'  => true,
		'description'   => 'Races and league matches. The homepage shows the next fixture date automatically.',
	) ) );

	register_post_type( 'hs_league', $common + array(
		'labels'       => hs_labels( 'League', 'Leagues & results' ),
		'public'       => false,
		'menu_icon'    => 'dashicons-awards',
		'supports'     => array( 'title', 'page-attributes' ),
		'hierarchical' => false,
	) );

	register_post_type( 'hs_round', $common + array(
		'labels'       => hs_labels( 'Round', 'Rounds' ),
		'public'       => false,
		'show_in_menu' => 'edit.php?post_type=hs_league',
		'supports'     => array( 'title' ),
	) );

	register_post_type( 'hs_training', $common + array(
		'labels'    => hs_labels( 'Training group', 'Training groups' ),
		'public'    => false,
		'menu_icon' => 'dashicons-groups',
		'supports'  => array( 'title', 'page-attributes' ),
	) );

	register_post_type( 'hs_volunteer', $common + array(
		'labels'    => hs_labels( 'Volunteer', 'Volunteers' ),
		'public'    => false,
		'menu_icon' => 'dashicons-heart',
		'supports'  => array( 'title', 'thumbnail', 'page-attributes' ),
	) );

	register_post_type( 'hs_record', $common + array(
		'labels'    => hs_labels( 'Club record', 'Club records' ),
		'public'    => false,
		'menu_icon' => 'dashicons-chart-bar',
		'supports'  => array( 'title', 'page-attributes' ),
	) );

	$tax = function ( $slug, $types, $single, $plural ) {
		register_taxonomy( $slug, $types, array(
			'labels'            => array(
				'name'          => $plural,
				'singular_name' => $single,
				'add_new_item'  => 'Add ' . strtolower( $single ),
				'all_items'     => 'All ' . strtolower( $plural ),
			),
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => 'hs_fixture_type' === $slug,
			'public'            => false,
			'show_ui'           => true,
			'rewrite'           => false,
		) );
	};
	$tax( 'hs_fixture_type', 'hs_fixture', 'Fixture type', 'Fixture types' );
	$tax( 'hs_training_tab', 'hs_training', 'Who it\'s for', 'Who it\'s for' );
	$tax( 'hs_volunteer_role', 'hs_volunteer', 'Volunteer role', 'Volunteer roles' );
	$tax( 'hs_event_group', array( 'hs_volunteer', 'hs_record' ), 'Event group', 'Event groups' );
}

function hs_labels( $single, $plural ) {
	return array(
		'name'               => $plural,
		'singular_name'      => $single,
		'add_new'            => 'Add ' . strtolower( $single ),
		'add_new_item'       => 'Add ' . strtolower( $single ),
		'edit_item'          => 'Edit ' . strtolower( $single ),
		'new_item'           => 'New ' . strtolower( $single ),
		'view_item'          => 'View ' . strtolower( $single ),
		'search_items'       => 'Search ' . strtolower( $plural ),
		'not_found'          => 'None yet',
		'not_found_in_trash' => 'None in the bin',
		'all_items'          => $plural,
		'menu_name'          => $plural,
	);
}

/* Fixture admin list: date and venue columns, soonest first. */
add_filter( 'manage_hs_fixture_posts_columns', function ( $cols ) {
	$new = array();
	foreach ( $cols as $k => $v ) {
		$new[ $k ] = $v;
		if ( 'title' === $k ) {
			$new['hs_date']  = 'Date';
			$new['hs_venue'] = 'Venue';
		}
	}
	unset( $new['date'] );
	return $new;
} );
add_action( 'manage_hs_fixture_posts_custom_column', function ( $col, $id ) {
	if ( 'hs_date' === $col ) {
		$d = get_post_meta( $id, 'hs_date', true );
		echo $d ? esc_html( wp_date( 'D j M Y', strtotime( $d ) ) ) : '—';
	}
	if ( 'hs_venue' === $col ) {
		echo esc_html( get_post_meta( $id, 'hs_venue', true ) );
	}
}, 10, 2 );
add_filter( 'manage_edit-hs_fixture_sortable_columns', function ( $c ) {
	$c['hs_date'] = 'hs_date';
	return $c;
} );
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( 'hs_fixture' === $q->get( 'post_type' ) && ( ! $q->get( 'orderby' ) || 'hs_date' === $q->get( 'orderby' ) ) ) {
		$q->set( 'meta_key', 'hs_date' );
		$q->set( 'orderby', 'meta_value' );
		$q->set( 'order', $q->get( 'order' ) ?: 'ASC' );
	}
} );

/* Rounds list: show which league and import status. */
add_filter( 'manage_hs_round_posts_columns', function ( $cols ) {
	return array(
		'cb'        => $cols['cb'],
		'title'     => 'Round',
		'hs_league' => 'League',
		'hs_date'   => 'Date',
		'hs_status' => 'Results',
	);
} );
add_action( 'manage_hs_round_posts_custom_column', function ( $col, $id ) {
	if ( 'hs_league' === $col ) {
		$p = wp_get_post_parent_id( $id );
		echo $p ? esc_html( get_the_title( $p ) ) : '—';
	}
	if ( 'hs_date' === $col ) {
		$d = get_post_meta( $id, 'hs_date', true );
		echo $d ? esc_html( wp_date( 'j M Y', strtotime( $d ) ) ) : '—';
	}
	if ( 'hs_status' === $col ) {
		$rows = hs_parse_lines( get_post_meta( $id, 'hs_rows', true ), 5 );
		$st   = get_post_meta( $id, 'hs_status', true );
		echo $rows ? esc_html( count( $rows ) . ' races · ' . ( $st ?: 'provisional' ) ) : 'Waiting for results';
	}
}, 10, 2 );
