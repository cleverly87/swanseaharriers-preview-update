<?php
defined( 'ABSPATH' ) || exit;
get_header();
hs_page_hero( 'Page not found', 'Off course', 'That page has moved or no longer exists. Try one of these instead.' );
?>
<div class="hs-wrap hs-section--tight hs-section--last hs-actions">
	<a class="hs-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
	<a class="hs-btn hs-btn--ghost" href="<?php echo esc_url( home_url( '/results/' ) ); ?>">Results</a>
	<a class="hs-btn hs-btn--ghost" href="<?php echo esc_url( home_url( '/fixtures/' ) ); ?>">Fixtures</a>
	<a class="hs-btn hs-btn--ghost" href="<?php echo esc_url( home_url( '/news/' ) ); ?>">News</a>
</div>
<?php
get_footer();
