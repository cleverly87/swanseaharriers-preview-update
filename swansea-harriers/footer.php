<?php defined( 'ABSPATH' ) || exit; ?>
</main>
<footer class="hs-footer">
	<div class="hs-wrap hs-footer__cols">
		<div class="hs-footer__brand">
			<div style="display:flex;align-items:center;gap:12px">
				<img src="<?php echo esc_url( hs_logo_url() ); ?>" alt="" width="54" height="54">
				<span class="hs-brand__name"><?php bloginfo( 'name' ); ?></span>
			</div>
			<span><?php echo nl2br( esc_html( hs_mod( 'address' ) ) ); ?></span>
			<a href="mailto:<?php echo esc_attr( hs_mod( 'email' ) ); ?>"><?php echo esc_html( hs_mod( 'email' ) ); ?></a>
		</div>
		<?php
		$cols = array( 'footer-1' => 'Compete', 'footer-2' => 'Club', 'footer-3' => 'Welfare & governance' );
		foreach ( $cols as $loc => $fallback ) :
			if ( ! has_nav_menu( $loc ) ) {
				continue;
			}
			$locations = get_nav_menu_locations();
			$menu = wp_get_nav_menu_object( $locations[ $loc ] );
			$title = $menu ? preg_replace( '/^Footer\s*·\s*/', '', $menu->name ) : $fallback;
			?>
			<nav aria-label="<?php echo esc_attr( $title ); ?>">
				<h2><?php echo esc_html( $title ); ?></h2>
				<?php wp_nav_menu( array( 'theme_location' => $loc, 'container' => false, 'depth' => 1 ) ); ?>
			</nav>
		<?php endforeach; ?>
	</div>
	<div class="hs-wrap hs-footer__base">
		<span><?php echo esc_html( hs_mod( 'charity' ) ); ?> · Registered in Wales</span>
		<span>© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></span>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
