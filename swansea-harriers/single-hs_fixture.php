<?php
/**
 * One fixture (linked from the homepage and as "Team info" when there's no news post).
 */

defined( 'ABSPATH' ) || exit;
get_header();

while ( have_posts() ) :
	the_post();
	$f = hs_fixture_view( get_post() );
	hs_page_hero( $f['type'] ? $f['type'] . ' fixture' : 'Fixture', get_the_title() );
	?>
	<div class="hs-wrap hs-section--tight hs-section--last hs-with-aside">
		<div class="hs-main">
			<div class="hs-prose"><?php the_content(); ?></div>
			<?php if ( ! trim( get_the_content() ) ) : ?>
				<p class="hs-muted">Team details and travel information will be posted by the team managers before the race.</p>
			<?php endif; ?>
		</div>
		<aside class="hs-aside">
			<div class="hs-panel-dark on-dark hs-facts">
				<b style="font-size:18px;padding-top:4px">Race facts</b>
				<dl style="margin:0;display:flex;flex-direction:column;gap:12px">
					<div><dt>Date</dt><dd><?php echo esc_html( wp_date( 'l j F Y', strtotime( $f['date'] ) ) ); ?></dd></div>
					<?php if ( $f['venue'] ) : ?><div><dt>Venue</dt><dd><?php echo esc_html( $f['venue'] ); ?></dd></div><?php endif; ?>
					<?php if ( get_post_meta( get_the_ID(), 'hs_time', true ) ) : ?><div><dt>Start</dt><dd><?php echo esc_html( get_post_meta( get_the_ID(), 'hs_time', true ) ); ?></dd></div><?php endif; ?>
					<?php if ( get_post_meta( get_the_ID(), 'hs_details', true ) ) : ?><div><dt>Details</dt><dd><?php echo esc_html( get_post_meta( get_the_ID(), 'hs_details', true ) ); ?></dd></div><?php endif; ?>
				</dl>
				<?php if ( $f['external'] ) : ?>
					<a class="hs-btn hs-btn--bright" href="<?php echo esc_url( $f['cta_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $f['cta'] ); ?></a>
				<?php endif; ?>
				<a class="hs-btn hs-btn--outline-light" href="<?php echo esc_url( get_post_type_archive_link( 'hs_fixture' ) ); ?>">All fixtures</a>
			</div>
		</aside>
	</div>
	<?php
endwhile;
get_footer();
