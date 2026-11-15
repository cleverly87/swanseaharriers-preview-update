<?php
/**
 * Swansea Harriers theme setup.
 */

defined( 'ABSPATH' ) || exit;

define( 'HS_THEME_VERSION', '1.0.0' );

require_once get_template_directory() . '/inc/icons.php';
require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/template-tags.php';

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array( 'height' => 120, 'width' => 120, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'assets/css/main.css', hs_fonts_url() ) );

	register_nav_menus( array(
		'primary'  => 'Main menu (header)',
		'tools'    => 'Athlete tools (top bar)',
		'explore'  => 'Homepage tiles (title + description; add an icon-* class)',
		'footer-1' => 'Footer column 1 · Compete',
		'footer-2' => 'Footer column 2 · Club',
		'footer-3' => 'Footer column 3 · Welfare & governance',
	) );

	add_image_size( 'hs-card', 720, 405, true );
	add_image_size( 'hs-hero', 2000, 1100, false );

	// Brand colours in the block editor's colour picker.
	add_theme_support( 'editor-color-palette', array(
		array( 'name' => 'Club green (dark)', 'slug' => 'green-900', 'color' => '#05351E' ),
		array( 'name' => 'Club green (bright)', 'slug' => 'bright', 'color' => '#12B24F' ),
		array( 'name' => 'Highlight green', 'slug' => 'glow', 'color' => '#3EE07F' ),
		array( 'name' => 'Link green', 'slug' => 'green-500', 'color' => '#0A7A3B' ),
		array( 'name' => 'Mint', 'slug' => 'mint', 'color' => '#D9F7E4' ),
		array( 'name' => 'White', 'slug' => 'white', 'color' => '#FFFFFF' ),
		array( 'name' => 'Ink', 'slug' => 'ink', 'color' => '#0E1A14' ),
	) );
} );

function hs_fonts_url() {
	return 'https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap';
}

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'hs-fonts', hs_fonts_url(), array(), null );
	wp_enqueue_style( 'hs-main', get_template_directory_uri() . '/assets/css/main.css', array( 'hs-fonts' ), HS_THEME_VERSION );
	wp_enqueue_script( 'hs-site', get_template_directory_uri() . '/assets/js/site.js', array(), HS_THEME_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
} );

add_action( 'wp_head', function () {
	echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}, 1 );

/* Block styles for buttons and reusable block patterns for editors. */
add_action( 'init', function () {
	register_block_style( 'core/button', array( 'name' => 'dark', 'label' => 'Dark green' ) );
	register_block_style( 'core/button', array( 'name' => 'light', 'label' => 'White' ) );
	register_block_pattern_category( 'harriers', array( 'label' => 'Swansea Harriers' ) );
	foreach ( glob( get_template_directory() . '/patterns-src/*.html' ) as $file ) {
		$slug = basename( $file, '.html' );
		$raw  = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
		if ( preg_match( '/^<!--\s*title:\s*(.+?)\s*-->\s*/', $raw, $m ) ) {
			register_block_pattern( 'harriers/' . $slug, array(
				'title'      => $m[1],
				'categories' => array( 'harriers' ),
				'content'    => substr( $raw, strlen( $m[0] ) ),
			) );
		}
	}
} );

/* Notice if the content plugin is missing. */
add_action( 'admin_notices', function () {
	if ( ! function_exists( 'hs_get_upcoming_fixtures' ) && current_user_can( 'activate_plugins' ) ) {
		echo '<div class="notice notice-warning"><p><strong>Swansea Harriers theme:</strong> please activate the <em>Harriers Core</em> plugin. It provides fixtures, results, training groups, volunteers and records.</p></div>';
	}
} );

/* News archive: 9 per page; fixtures archive shows all upcoming. */
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( $q->is_post_type_archive( 'hs_fixture' ) && function_exists( 'hs_today' ) ) {
		$q->set( 'posts_per_page', -1 );
		$q->set( 'meta_key', 'hs_date' );
		$q->set( 'orderby', 'meta_value' );
		$q->set( 'order', 'ASC' );
		$q->set( 'meta_query', array( array( 'key' => 'hs_date', 'value' => hs_today(), 'compare' => '>=', 'type' => 'DATE' ) ) );
	}
	if ( $q->is_home() || $q->is_category() ) {
		$q->set( 'posts_per_page', 9 );
	}
} );

/* Excerpts: short and clean. */
add_filter( 'excerpt_length', fn() => 28 );
add_filter( 'excerpt_more', fn() => '…' );
