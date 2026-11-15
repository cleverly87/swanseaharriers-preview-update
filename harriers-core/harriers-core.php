<?php
/**
 * Plugin Name:       Harriers Core
 * Description:       Fixtures, league results (with automatic import from the Gwent and West Glamorgan league sites), training groups, volunteers and club records for Swansea Harriers.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Swansea Harriers
 * Text Domain:       harriers
 *
 * Content lives in this plugin (not the theme) so it survives any future theme change.
 */

defined( 'ABSPATH' ) || exit;

define( 'HS_CORE_VERSION', '1.0.0' );
define( 'HS_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'HS_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once HS_CORE_DIR . 'includes/post-types.php';
require_once HS_CORE_DIR . 'includes/fields.php';
require_once HS_CORE_DIR . 'includes/helpers.php';
require_once HS_CORE_DIR . 'includes/importer/class-hs-gwent-parser.php';
require_once HS_CORE_DIR . 'includes/importer/class-hs-westglam-parser.php';
require_once HS_CORE_DIR . 'includes/importer/class-hs-importer.php';
require_once HS_CORE_DIR . 'includes/admin.php';
require_once HS_CORE_DIR . 'includes/seed.php';
require_once HS_CORE_DIR . 'includes/security.php';

register_activation_hook( __FILE__, function () {
	hs_register_post_types();
	flush_rewrite_rules();
	HS_Importer::schedule();
} );

register_deactivation_hook( __FILE__, function () {
	HS_Importer::unschedule();
	flush_rewrite_rules();
} );
