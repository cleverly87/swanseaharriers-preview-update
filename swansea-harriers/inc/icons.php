<?php
/**
 * Inline stroke icons (24×24, currentColor). Used by the homepage tiles:
 * give a menu item in the "Homepage tiles" menu a CSS class like "icon-trophy".
 */

defined( 'ABSPATH' ) || exit;

function hs_icon_paths() {
	return array(
		'trophy'   => 'M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0V4zM17 5h3v2a3 3 0 0 1-3 3M7 5H4v2a3 3 0 0 0 3 3',
		'calendar' => 'M3 6h18v15H3zM3 10h18M8 3v4M16 3v4',
		'news'     => 'M4 5h13v14H6a2 2 0 0 1-2-2V5zM17 9h3v8a2 2 0 0 1-2 2M8 9h5M8 13h5',
		'run'      => 'M13 4a2 2 0 1 1 0 .1M7 21l3-6 3 3v3M10 15l1-5 4 2 3-1M11 10 8 12',
		'join'     => 'M9 7a4 4 0 1 1 0 .1M2 21a7 7 0 0 1 14 0M19 8v6M16 11h6',
		'star'     => 'M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.4 6.8 19.1l1-5.8L3.5 9.2l5.9-.9z',
		'chart'    => 'M4 20V10M10 20V4M16 20v-7M22 20H2',
		'people'   => 'M8 8a3 3 0 1 1 0 .1M16 8a3 3 0 1 1 0 .1M2 20a6 6 0 0 1 12 0M12 20a6 6 0 0 1 10 0',
		'shield'   => 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z',
		'pin'      => 'M12 21s-7-6-7-12a7 7 0 0 1 14 0c0 6-7 12-7 12zM12 9a2 2 0 1 1 0 .1',
		'shirt'    => 'M8 3l4 3 4-3 5 4-3 4-2-1v11H8V10L6 11 3 7z',
		'flag'     => 'M5 21V4M5 4h11l-2 4 2 4H5',
		'arrow'    => 'M7 17L17 7M8 7h9v9',
		'refresh'  => 'M21 12a9 9 0 1 1-3-6.7M21 4v5h-5',
		'download' => 'M12 4v12M6 10l6 6 6-6M5 20h14',
		'menu'     => 'M3 6h18M3 12h18M3 18h18',
	);
}

function hs_icon( $name, $size = 24, $label = '' ) {
	$paths = hs_icon_paths();
	$d = $paths[ $name ] ?? $paths['arrow'];
	$a11y = $label ? 'role="img" aria-label="' . esc_attr( $label ) . '"' : 'aria-hidden="true" focusable="false"';
	return sprintf(
		'<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" %2$s><path d="%3$s"/></svg>',
		(int) $size,
		$a11y,
		esc_attr( $d )
	);
}
