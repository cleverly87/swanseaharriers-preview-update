<?php
/**
 * Ordinary pages (Join, Club, Safeguarding, Contact, Support…).
 * The green banner shows the page title as a small heading and the page's
 * excerpt (Page → Excerpt in the editor sidebar) as the big headline.
 * Everything below is normal block content, so editors can change it freely.
 */

defined( 'ABSPATH' ) || exit;
get_header();

while ( have_posts() ) :
	the_post();
	hs_page_hero( get_the_title(), has_excerpt() ? get_the_excerpt() : get_the_title() );
	?>
	<div class="hs-wrap hs-section--tight hs-section--last">
		<div class="hs-prose"><?php the_content(); ?></div>
	</div>
	<?php
endwhile;
get_footer();
