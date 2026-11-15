<?php
/**
 * A news post.
 */

defined( 'ABSPATH' ) || exit;
get_header();

while ( have_posts() ) :
	the_post();
	$news_url = get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/' );
	$cats     = get_the_category();
	$url      = rawurlencode( get_permalink() );
	$ttl      = rawurlencode( get_the_title() );
	?>
	<div class="hs-wrap hs-section--tight hs-section--last">
		<nav class="hs-breadcrumb" aria-label="Breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a><span class="hs-muted">/</span>
			<a href="<?php echo esc_url( $news_url ); ?>">News</a>
			<?php if ( $cats ) : ?><span class="hs-muted">/</span><a href="<?php echo esc_url( get_category_link( $cats[0] ) ); ?>"><?php echo esc_html( $cats[0]->name ); ?></a><?php endif; ?>
		</nav>
		<div class="hs-with-aside">
			<article class="hs-main hs-article" style="gap:22px">
				<div class="hs-news-card__meta"><?php hs_the_post_tag(); ?><span>Posted <?php echo esc_html( get_the_date( 'j F Y' ) ); ?> · by <?php the_author(); ?></span></div>
				<h1><?php the_title(); ?></h1>
				<?php if ( has_excerpt() ) : ?><p class="hs-article__lead"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
				<?php if ( has_post_thumbnail() ) : ?><div class="hs-article__img"><?php the_post_thumbnail( 'large' ); ?></div><?php endif; ?>
				<div class="hs-prose" style="max-width:760px"><?php the_content(); ?></div>
				<div class="hs-share">
					<span class="hs-muted" style="font-size:14px">Share:</span>
					<a class="hs-btn hs-btn--ghost hs-btn--sm" href="https://wa.me/?text=<?php echo esc_attr( $ttl . '%20' . $url ); ?>" target="_blank" rel="noopener">WhatsApp</a>
					<a class="hs-btn hs-btn--ghost hs-btn--sm" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_attr( $url ); ?>" target="_blank" rel="noopener">Facebook</a>
					<button class="hs-btn hs-btn--ghost hs-btn--sm" type="button" data-copy="<?php echo esc_url( get_permalink() ); ?>">Copy link</button>
				</div>
			</article>
			<aside class="hs-aside">
				<div class="hs-card hs-card--pad" style="display:flex;flex-direction:column;gap:12px">
					<b style="font-size:18px">More news</b>
					<?php foreach ( get_posts( array( 'numberposts' => 4, 'post__not_in' => array( get_the_ID() ) ) ) as $p ) : ?>
						<a href="<?php echo esc_url( get_permalink( $p ) ); ?>" style="font-weight:600;text-decoration:none"><?php echo esc_html( get_the_title( $p ) ); ?></a>
					<?php endforeach; ?>
				</div>
				<div class="hs-card hs-card--pad" style="display:flex;flex-direction:column;gap:12px">
					<b style="font-size:18px">Related</b>
					<a href="<?php echo esc_url( function_exists( 'hs_results_page_url' ) ? hs_results_page_url() : home_url( '/results/' ) ); ?>" style="font-weight:600;text-decoration:none">Results &amp; league tables</a>
					<a href="<?php echo esc_url( get_post_type_archive_link( 'hs_fixture' ) ?: home_url( '/fixtures/' ) ); ?>" style="font-weight:600;text-decoration:none">Upcoming fixtures</a>
					<a href="<?php echo esc_url( home_url( '/volunteers/' ) ); ?>" style="font-weight:600;text-decoration:none">Contact a team manager</a>
				</div>
			</aside>
		</div>
	</div>
	<?php
endwhile;
get_footer();
