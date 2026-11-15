<?php defined( 'ABSPATH' ) || exit; ?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="hs-site-header">
<a class="skip-link screen-reader-text" href="#main">Skip to content</a>

<div class="hs-topbar">
	<div class="hs-wrap">
		<span class="hs-topbar__note"><?php echo esc_html( hs_mod( 'charity' ) ); ?> · Sketty Lane, Swansea</span>
		<nav aria-label="Athlete tools">
			<?php
			if ( has_nav_menu( 'tools' ) ) {
				wp_nav_menu( array( 'theme_location' => 'tools', 'container' => false, 'depth' => 1 ) );
			} else {
				echo '<ul>';
				$first = true;
				foreach ( hs_default_tools() as $label => $url ) {
					echo '<li' . ( $first ? ' class="is-highlight"' : '' ) . '><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
					$first = false;
				}
				echo '</ul>';
			}
			?>
		</nav>
	</div>
</div>

<div class="hs-header">
	<div class="hs-wrap">
		<a class="hs-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<img src="<?php echo esc_url( hs_logo_url() ); ?>" alt="" width="58" height="58">
			<span>
				<span class="hs-brand__name"><?php bloginfo( 'name' ); ?></span>
				<span class="hs-brand__tag"><?php echo esc_html( hs_mod( 'tagline' ) ); ?></span>
			</span>
		</a>
		<nav class="hs-nav" aria-label="Main">
			<button class="hs-nav__toggle" type="button" aria-expanded="false" aria-controls="hs-primary-menu"><?php hs_the_icon( 'menu', 20 ); ?> Menu</button>
			<?php
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_id'        => 'hs-primary-menu',
				'depth'          => 1,
				'fallback_cb'    => function () {
					echo '<ul id="hs-primary-menu">';
					wp_list_pages( array( 'title_li' => '', 'depth' => 1 ) );
					echo '</ul>';
				},
			) );
			$join = get_page_by_path( 'join' );
			?>
			<a class="hs-btn hs-btn--bright" href="<?php echo esc_url( $join ? get_permalink( $join ) : home_url( '/join/' ) ); ?>">Join the club</a>
		</nav>
	</div>
</div>
</header>
<main id="main" tabindex="-1">
