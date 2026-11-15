<?php
/**
 * Appearance → Customise → "Homepage" and "Club details".
 * Everything on the homepage that isn't fixtures, results or news is set here,
 * so it can be changed without touching code.
 */

defined( 'ABSPATH' ) || exit;

function hs_mod_defaults() {
	$img = get_template_directory_uri() . '/assets/images/';
	return array(
		'hero_image'      => $img . 'team.jpg',
		'hero_eyebrow'    => 'Athletic club · Swansea · since 1962',
		'hero_title'      => 'Run, jump, throw',
		'hero_title_2'    => 'for Swansea.',
		'hero_text'       => 'From school year 5 to masters, from first parkrun to world championships. New members of every level are welcome.',
		'spot_image'      => $img . 'brier.jpg',
		'spot_eyebrow'    => 'Hall of Fame spotlight',
		'spot_name'       => 'Hannah Brier',
		'spot_text'       => 'The youngest ever Welsh track and field athlete at a Commonwealth Games in 2014, and back again in 2022 over 100m and 200m. Hannah came through the same junior sessions that run every week at the club.',
		'spot_link'       => '/club/',
		'venue_image'     => $img . 'venue.jpg',
		'venue_title'     => 'Swansea University Athletics Centre',
		'venue_text'      => 'Outdoor track, indoor athletics centre and the club café, all on Sketty Lane, SA2 9AU.',
		'stats'           => "1962 | Founded\n53 | Qualified volunteer coaches\n~50 | Training sessions a week\n2× | UK Athletics Club of the Year",
		'join_title'      => 'Wear the green and white',
		'join_text'       => '£50 under 19 · £60 over 19. Includes Welsh Athletics registration and insurance.',
		'charity'         => 'Registered charity 1177846',
		'address'         => "Swansea University Athletics Centre\nSketty Lane, Swansea SA2 9AU",
		'email'           => 'info@swanseaharriers.co.uk',
		'tagline'         => 'Athletic Club · Since 1962',
	);
}

function hs_mod( $key ) {
	$d = hs_mod_defaults();
	$v = get_theme_mod( 'hs_' . $key, $d[ $key ] ?? '' );
	return '' === $v ? ( $d[ $key ] ?? '' ) : $v;
}

add_action( 'customize_register', function ( WP_Customize_Manager $c ) {
	$c->add_section( 'hs_home', array( 'title' => 'Homepage', 'priority' => 30 ) );
	$c->add_section( 'hs_club', array( 'title' => 'Club details', 'priority' => 31 ) );

	$text = function ( $key, $label, $section, $type = 'text', $desc = '' ) use ( $c ) {
		$c->add_setting( 'hs_' . $key, array( 'default' => hs_mod_defaults()[ $key ] ?? '', 'sanitize_callback' => 'textarea' === $type ? 'sanitize_textarea_field' : 'sanitize_text_field' ) );
		$c->add_control( 'hs_' . $key, array( 'label' => $label, 'section' => $section, 'type' => $type, 'description' => $desc ) );
	};
	$image = function ( $key, $label ) use ( $c ) {
		$c->add_setting( 'hs_' . $key, array( 'default' => hs_mod_defaults()[ $key ], 'sanitize_callback' => 'esc_url_raw' ) );
		$c->add_control( new WP_Customize_Image_Control( $c, 'hs_' . $key, array( 'label' => $label, 'section' => 'hs_home' ) ) );
	};

	$image( 'hero_image', 'Top banner photo' );
	$text( 'hero_eyebrow', 'Banner: small line above the headline', 'hs_home' );
	$text( 'hero_title', 'Banner: headline (white)', 'hs_home' );
	$text( 'hero_title_2', 'Banner: headline (green)', 'hs_home' );
	$text( 'hero_text', 'Banner: paragraph', 'hs_home', 'textarea' );
	$image( 'spot_image', 'Spotlight photo' );
	$text( 'spot_eyebrow', 'Spotlight: small heading', 'hs_home' );
	$text( 'spot_name', 'Spotlight: athlete name', 'hs_home' );
	$text( 'spot_text', 'Spotlight: paragraph', 'hs_home', 'textarea' );
	$text( 'spot_link', 'Spotlight: button link', 'hs_home' );
	$image( 'venue_image', '"Our home" photo' );
	$text( 'venue_title', '"Our home": title', 'hs_home' );
	$text( 'venue_text', '"Our home": paragraph', 'hs_home', 'textarea' );
	$text( 'stats', 'Club facts', 'hs_home', 'textarea', 'One per line: Number | Label' );
	$text( 'join_title', 'Join band: heading', 'hs_home' );
	$text( 'join_text', 'Join band: paragraph', 'hs_home', 'textarea' );

	$text( 'tagline', 'Line under the club name', 'hs_club' );
	$text( 'charity', 'Charity line', 'hs_club' );
	$text( 'address', 'Address', 'hs_club', 'textarea' );
	$text( 'email', 'Club email', 'hs_club' );
} );
