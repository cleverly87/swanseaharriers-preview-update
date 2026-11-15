<?php
/**
 * News (the posts page) and category archives.
 * A sticky post becomes the large feature at the top of page 1.
 */

defined( 'ABSPATH' ) || exit;
get_header();

$news_url = get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/' );
$current  = is_category() ? get_queried_object_id() : 0;
$title    = is_category() ? single_cat_title( '', false ) : ( is_search() ? 'Search: ' . get_search_query() : 'News & round-ups' );

ob_start();
?>
<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label for="hs-news-search">Search news</label>
	<input class="hs-input" id="hs-news-search" type="search" name="s" placeholder="Athlete, race or league" style="width:240px">
</form>
<?php
$search = ob_get_clean();
hs_page_hero( 'Club news', $title, '', '<div class="hs-page-hero__row">' . $search . '</div>' );

$sticky = ( is_home() && ! is_paged() ) ? get_option( 'sticky_posts' ) : array();
$feature = $sticky ? get_post( $sticky[0] ) : null;
?>
<div class="hs-wrap hs-section--tight hs-section--last" style="display:flex;flex-direction:column;gap:28px">
	<?php if ( $feature ) : ?>
		<a class="hs-card hs-feature" href="<?php echo esc_url( get_permalink( $feature ) ); ?>">
			<div class="hs-feature__img"><?php echo wp_kses_post( get_the_post_thumbnail( $feature, 'large', array( 'alt' => '' ) ) ); ?></div>
			<div class="hs-feature__body">
				<div class="hs-news-card__meta"><span class="hs-tag hs-tag--pinned">Pinned</span><?php hs_the_post_tag( $feature ); ?><span><?php echo esc_html( get_the_date( 'F Y', $feature ) ); ?></span></div>
				<h2><?php echo esc_html( get_the_title( $feature ) ); ?></h2>
				<p class="hs-muted" style="margin:0;font-size:16px"><?php echo esc_html( wp_trim_words( get_the_excerpt( $feature ), 35 ) ); ?></p>
				<span style="font-weight:700;color:var(--hs-green-500)">Read the full post →</span>
			</div>
		</a>
	<?php endif; ?>

	<div class="hs-with-aside">
		<div class="hs-main">
			<nav class="hs-chips" aria-label="News categories">
				<a class="hs-chip<?php echo $current ? '' : ' is-active'; ?>" href="<?php echo esc_url( $news_url ); ?>"<?php echo $current ? '' : ' aria-current="page"'; ?>>All</a>
				<?php foreach ( get_categories( array( 'hide_empty' => true, 'exclude' => get_option( 'default_category' ) ) ) as $cat ) : ?>
					<a class="hs-chip<?php echo $current === $cat->term_id ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_category_link( $cat ) ); ?>"<?php echo $current === $cat->term_id ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $cat->name ); ?> · <?php echo (int) $cat->count; ?></a>
				<?php endforeach; ?>
			</nav>

			<?php if ( have_posts() ) : ?>
				<?php
				while ( have_posts() ) :
					the_post();
					if ( $feature && get_the_ID() === $feature->ID ) {
						continue;
					}
					hs_news_card( get_post(), true );
				endwhile;
				?>
				<div class="hs-pagination"><?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => '← Newer', 'next_text' => 'Older posts →' ) ); ?></div>
			<?php else : ?>
				<div class="hs-empty"><b>No posts yet</b><span>News will appear here.</span></div>
			<?php endif; ?>
		</div>

		<aside class="hs-aside">
			<div class="hs-card hs-card--pad" style="display:flex;flex-direction:column;gap:12px">
				<b style="font-size:18px">League results</b>
				<span class="hs-muted" style="font-size:14px">Every round's team results and tables, updated automatically.</span>
				<a class="hs-btn hs-btn--sm" style="align-self:start" href="<?php echo esc_url( function_exists( 'hs_results_page_url' ) ? hs_results_page_url() : home_url( '/results/' ) ); ?>">Results &amp; tables</a>
			</div>
			<div class="hs-panel-dark on-dark" style="display:flex;flex-direction:column;gap:10px">
				<b style="font-size:18px">Coming up</b>
				<?php
				if ( function_exists( 'hs_get_upcoming_fixtures' ) ) {
					foreach ( hs_get_upcoming_fixtures( 3 ) as $p ) {
						$f = hs_fixture_view( $p );
						echo '<span style="font-size:14px;color:var(--hs-on-dark-strong)"><b style="color:#fff">' . esc_html( $f['day'] . ' ' . $f['mon'] ) . '</b> · ' . esc_html( $f['title'] ) . '</span>';
					}
				}
				?>
				<a class="hs-btn hs-btn--bright hs-btn--sm" style="align-self:start" href="<?php echo esc_url( get_post_type_archive_link( 'hs_fixture' ) ?: home_url( '/fixtures/' ) ); ?>">All fixtures</a>
			</div>
		</aside>
	</div>
</div>
<?php
get_footer();
