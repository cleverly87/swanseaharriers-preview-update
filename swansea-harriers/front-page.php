<?php
/**
 * Homepage. Everything here is read from WordPress:
 *  - next fixture(s) and "Coming up": Fixtures (soonest first; the strip groups
 *    every fixture on the next racing day, or the whole weekend)
 *  - latest result: the most recent league round with results
 *  - tiles: the "Homepage tiles" menu
 *  - news: latest posts
 *  - banner, spotlight, venue, facts: Appearance → Customise → Homepage
 */

defined( 'ABSPATH' ) || exit;
get_header();

$has_core = function_exists( 'hs_get_next_fixture_group' );
$next     = $has_core ? hs_get_next_fixture_group() : null;
$latest   = $has_core ? hs_get_latest_result() : null;
$coming   = $has_core ? hs_get_upcoming_fixtures( 5 ) : array();
$join     = get_page_by_path( 'join' );
$training = get_page_by_path( 'training' );
$fix_url  = get_post_type_archive_link( 'hs_fixture' ) ?: home_url( '/fixtures/' );
?>

<section class="hs-hero on-dark">
	<img class="hs-hero__img" src="<?php echo esc_url( hs_mod( 'hero_image' ) ); ?>" alt="" fetchpriority="high">
	<div class="hs-wrap">
		<p class="hs-eyebrow"><?php echo esc_html( hs_mod( 'hero_eyebrow' ) ); ?></p>
		<h1><?php echo esc_html( hs_mod( 'hero_title' ) ); ?><br><em><?php echo esc_html( hs_mod( 'hero_title_2' ) ); ?></em></h1>
		<p><?php echo esc_html( hs_mod( 'hero_text' ) ); ?></p>
		<div class="hs-actions">
			<a class="hs-btn hs-btn--bright" href="<?php echo esc_url( $join ? get_permalink( $join ) : home_url( '/join/' ) ); ?>">Join the club</a>
			<a class="hs-btn hs-btn--outline-light" href="<?php echo esc_url( $training ? get_permalink( $training ) : home_url( '/training/' ) ); ?>">Find a training group</a>
		</div>
	</div>
</section>

<section class="hs-wrap hs-strip" aria-label="At a glance">
	<div class="hs-strip__inner">
		<a class="hs-strip__item" href="<?php echo esc_url( $fix_url ); ?>">
			<span class="hs-eyebrow" style="font-size:12px">
				<?php echo $next && count( $next['fixtures'] ) > 1 ? 'Next up · ' . esc_html( $next['label'] ) : 'Next fixture'; ?>
			</span>
			<?php if ( ! $next ) : ?>
				<strong>New fixtures coming soon</strong>
			<?php elseif ( 1 === count( $next['fixtures'] ) ) : $f = hs_fixture_view( $next['fixtures'][0] ); ?>
				<strong><?php echo esc_html( $f['title'] ); ?></strong>
				<span class="hs-muted" style="font-size:14px"><?php echo esc_html( wp_date( 'D j M', strtotime( $f['date'] ) ) . ( $f['venue'] ? ' · ' . $f['venue'] : '' ) ); ?></span>
			<?php else : ?>
				<ul>
					<?php foreach ( array_slice( $next['fixtures'], 0, 4 ) as $p ) : $f = hs_fixture_view( $p ); ?>
						<li><b><?php echo esc_html( $f['title'] ); ?></b> · <?php echo esc_html( wp_date( 'D', strtotime( $f['date'] ) ) . ( $f['venue'] ? ' · ' . $f['venue'] : '' ) ); ?></li>
					<?php endforeach; ?>
					<?php if ( count( $next['fixtures'] ) > 4 ) : ?><li>+ <?php echo (int) count( $next['fixtures'] ) - 4; ?> more</li><?php endif; ?>
				</ul>
			<?php endif; ?>
		</a>
		<a class="hs-strip__item" href="<?php echo esc_url( $latest ? $latest['url'] : home_url( '/results/' ) ); ?>">
			<span class="hs-eyebrow" style="font-size:12px">Latest result</span>
			<?php if ( $latest ) : ?>
				<strong><?php echo esc_html( $latest['league'] . ' · ' . $latest['round'] ); ?></strong>
				<span class="hs-muted" style="font-size:14px">
					<?php
					if ( $latest['wins'] ) {
						$n = count( $latest['wins'] );
						$names = $n > 3 ? implode( ', ', array_slice( $latest['wins'], 0, 3 ) ) . ' and ' . ( $n - 3 ) . ' more' : wp_sprintf( '%l', $latest['wins'] );
						echo esc_html( $names . ( 1 === $n ? ' wins' : ' win' ) . ( $latest['venue'] ? ' at ' . $latest['venue'] : '' ) );
					} elseif ( $latest['team_wins'] ) {
						echo esc_html( wp_sprintf( '%l', $latest['team_wins'] ) . ' win as a team' );
					} else {
						echo 'Full results and tables';
					}
					?>
				</span>
			<?php else : ?>
				<strong>Results &amp; league tables</strong>
			<?php endif; ?>
		</a>
		<a class="hs-strip__item hs-strip__item--dark" href="https://www.thepowerof10.info">
			<span class="hs-eyebrow" style="font-size:12px">Rankings</span>
			<strong>Power of 10 →</strong>
			<span>PBs, rankings and athlete profiles</span>
		</a>
	</div>
</section>

<?php
$tiles = array();
$locs = get_nav_menu_locations();
if ( ! empty( $locs['explore'] ) ) {
	$tiles = wp_get_nav_menu_items( $locs['explore'] ) ?: array();
}
if ( $tiles ) :
	?>
	<section class="hs-wrap hs-section">
		<div class="hs-section-head"><div><p class="hs-eyebrow">Explore</p><h2 class="hs-h2">Everything Harriers</h2></div></div>
		<div class="hs-grid hs-grid--tiles">
			<?php
			foreach ( $tiles as $t ) :
				$classes = implode( ' ', (array) $t->classes );
				$icon = preg_match( '/\bicon-([a-z]+)/', $classes, $m ) ? $m[1] : 'arrow';
				$dark = false !== strpos( $classes, 'is-dark' );
				?>
				<a class="hs-card hs-tile" href="<?php echo esc_url( $t->url ); ?>">
					<span class="hs-icon-box<?php echo $dark ? ' is-dark' : ''; ?>"><?php hs_the_icon( $icon ); ?></span>
					<span><strong><?php echo esc_html( $t->title ); ?></strong><span class="hs-muted"><?php echo esc_html( $t->description ); ?></span></span>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
<?php endif; ?>

<?php if ( hs_mod( 'spot_name' ) ) : ?>
<section class="hs-wrap hs-section">
	<div class="hs-spotlight on-dark">
		<img src="<?php echo esc_url( hs_mod( 'spot_image' ) ); ?>" alt="<?php echo esc_attr( hs_mod( 'spot_name' ) ); ?>" loading="lazy">
		<div class="hs-spotlight__body">
			<p class="hs-eyebrow"><?php echo esc_html( hs_mod( 'spot_eyebrow' ) ); ?></p>
			<h2><?php echo esc_html( hs_mod( 'spot_name' ) ); ?></h2>
			<p><?php echo esc_html( hs_mod( 'spot_text' ) ); ?></p>
			<div class="hs-actions">
				<a class="hs-btn hs-btn--bright" href="<?php echo esc_url( hs_mod( 'spot_link' ) ); ?>">Meet the Hall of Fame</a>
				<a class="hs-btn hs-btn--outline-light" href="<?php echo esc_url( home_url( '/club-records/' ) ); ?>">Club records</a>
			</div>
		</div>
	</div>
</section>
<?php endif; ?>

<section class="hs-wrap hs-section hs-split">
	<div class="hs-list">
		<div class="hs-section-head" style="margin-bottom:6px"><h2 class="hs-h2" style="font-size:38px">Coming up</h2><a class="hs-more" href="<?php echo esc_url( $fix_url ); ?>">All fixtures →</a></div>
		<?php if ( ! $coming ) : ?>
			<p class="hs-muted">No upcoming fixtures listed yet.</p>
		<?php endif; ?>
		<?php foreach ( $coming as $p ) : $f = hs_fixture_view( $p ); ?>
			<a class="hs-card hs-row-card" href="<?php echo esc_url( $f['external'] ? $f['permalink'] : $f['cta_url'] ); ?>">
				<span class="hs-datebox"><b><?php echo esc_html( $f['day'] ); ?></b><span><?php echo esc_html( $f['mon'] ); ?></span></span>
				<span class="hs-row-card__body"><span class="hs-row-card__title"><?php echo esc_html( $f['title'] ); ?></span><span class="hs-row-card__meta"><?php echo esc_html( $f['meta'] ); ?></span></span>
			</a>
		<?php endforeach; ?>
	</div>
	<div class="hs-list">
		<div class="hs-section-head" style="margin-bottom:6px"><h2 class="hs-h2" style="font-size:38px">Latest news</h2><a class="hs-more" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/news/' ) ); ?>">All news →</a></div>
		<?php
		$news = get_posts( array( 'numberposts' => 3, 'ignore_sticky_posts' => true ) );
		foreach ( $news as $p ) :
			?>
			<a class="hs-card hs-news-card" href="<?php echo esc_url( get_permalink( $p ) ); ?>">
				<div class="hs-news-card__body">
					<div class="hs-news-card__meta"><?php hs_the_post_tag( $p ); ?><span><?php echo esc_html( get_the_date( 'M Y', $p ) ); ?></span></div>
					<span class="hs-news-card__title"><?php echo esc_html( get_the_title( $p ) ); ?></span>
					<span class="hs-news-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $p ), 24 ) ); ?></span>
				</div>
			</a>
		<?php endforeach; ?>
	</div>
</section>

<section class="hs-wrap hs-section">
	<div class="hs-venue on-dark">
		<img src="<?php echo esc_url( hs_mod( 'venue_image' ) ); ?>" alt="Aerial view of the athletics track and sports fields at Sketty Lane" loading="lazy">
		<div class="hs-venue__body">
			<div>
				<p class="hs-eyebrow">Our home</p>
				<span class="hs-venue__title"><?php echo esc_html( hs_mod( 'venue_title' ) ); ?></span>
				<p><?php echo esc_html( hs_mod( 'venue_text' ) ); ?></p>
			</div>
			<div class="hs-actions">
				<a class="hs-btn hs-btn--bright" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Directions</a>
				<a class="hs-btn hs-btn--ghost" href="<?php echo esc_url( $training ? get_permalink( $training ) : home_url( '/training/' ) ); ?>">Session times</a>
			</div>
		</div>
	</div>
</section>

<?php $stats = function_exists( 'hs_parse_lines' ) ? hs_parse_lines( hs_mod( 'stats' ), 2 ) : array(); ?>
<?php if ( $stats ) : ?>
<section class="hs-wrap hs-section">
	<div class="hs-grid hs-grid--stats">
		<?php foreach ( $stats as $s ) : ?>
			<div class="hs-card hs-stat"><b><?php echo esc_html( $s[0] ); ?></b><span><?php echo esc_html( $s[1] ); ?></span></div>
		<?php endforeach; ?>
	</div>
</section>
<?php endif; ?>

<section class="hs-wrap hs-section hs-section--last">
	<div class="hs-join-band">
		<div>
			<h2><?php echo esc_html( hs_mod( 'join_title' ) ); ?></h2>
			<p><?php echo esc_html( hs_mod( 'join_text' ) ); ?></p>
		</div>
		<div class="hs-actions">
			<a class="hs-btn hs-btn--dark" href="<?php echo esc_url( $join ? get_permalink( $join ) : home_url( '/join/' ) ); ?>">Join Swansea Harriers</a>
			<a class="hs-btn hs-btn--ghost" href="<?php echo esc_url( home_url( '/support/' ) ); ?>">Support the club</a>
		</div>
	</div>
</section>

<?php
get_footer();
