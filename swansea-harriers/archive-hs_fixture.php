<?php
/**
 * Fixtures page (/fixtures/): every upcoming fixture, grouped by month,
 * with filter chips. Past fixtures drop off automatically.
 */

defined( 'ABSPATH' ) || exit;
get_header();

hs_page_hero(
	'Fixtures ' . wp_date( 'Y' ) . '/' . wp_date( 'y', strtotime( '+1 year' ) ),
	'Where we\'re racing next',
	'Every club fixture on and off the track. Each race shows where to enter, so you\'re not hunting through five different sites.'
);

$months = array();
while ( have_posts() ) {
	the_post();
	$f = hs_fixture_view( get_post() );
	$months[ $f['month_key'] ]['name'] = $f['month_name'];
	$months[ $f['month_key'] ]['items'][] = $f;
}
?>
<div class="hs-wrap hs-section--tight hs-section--last hs-with-aside">
	<div class="hs-main" data-fixtures>
		<div class="hs-chips" role="group" aria-label="Filter fixtures">
			<button type="button" class="hs-chip" data-filter="all" aria-pressed="true">All fixtures</button>
			<button type="button" class="hs-chip" data-filter="off" aria-pressed="false">Off track · XC &amp; road</button>
			<button type="button" class="hs-chip" data-filter="on" aria-pressed="false">On track</button>
			<button type="button" class="hs-chip" data-filter="league" aria-pressed="false">League matches only</button>
		</div>

		<?php if ( ! $months ) : ?>
			<div class="hs-empty"><b>No upcoming fixtures</b><span>New fixtures will appear here as soon as they're added.</span></div>
		<?php endif; ?>
		<div class="hs-empty" data-fixtures-empty hidden><b>Nothing matches that filter</b><span>Try another filter, or check back when the next season's calendar is published.</span></div>

		<?php foreach ( $months as $m ) : ?>
			<section class="hs-month" data-month>
				<h2><?php echo esc_html( $m['name'] ); ?></h2>
				<?php foreach ( $m['items'] as $f ) { hs_fixture_row( $f ); } ?>
			</section>
		<?php endforeach; ?>
	</div>

	<aside class="hs-aside">
		<div class="hs-card hs-card--pad hs-platforms" style="display:flex;flex-direction:column;gap:14px">
			<b style="font-size:18px">Where to enter</b>
			<?php foreach ( array( 'OpenTrack', 'Fabian4', 'Welsh Athletics', 'Race Timing Solutions', 'ResultsBase' ) as $label ) : ?>
				<a href="<?php echo esc_url( hs_default_tools()[ $label ] ); ?>" target="_blank" rel="noopener"><b><?php echo esc_html( $label ); ?></b><span><?php echo esc_html( hs_platform_blurbs()[ $label ] ); ?></span></a>
			<?php endforeach; ?>
		</div>
		<div class="hs-panel-bright" style="display:flex;flex-direction:column;gap:10px">
			<b style="font-size:18px">League results</b>
			<span style="font-size:14px">Gwent and West Glamorgan results load automatically after each round.</span>
			<a class="hs-btn hs-btn--dark hs-btn--sm" style="align-self:start" href="<?php echo esc_url( function_exists( 'hs_results_page_url' ) ? hs_results_page_url() : home_url( '/results/' ) ); ?>">See results</a>
		</div>
	</aside>
</div>
<?php
get_footer();
