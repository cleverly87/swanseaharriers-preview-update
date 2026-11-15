<?php
/**
 * Template Name: Training groups
 *
 * One tab per "Who it's for" term (ordered by the term's order); groups come
 * from Training groups, ordered by their Order number.
 */

defined( 'ABSPATH' ) || exit;
get_header();

$tabs = get_terms( array( 'taxonomy' => 'hs_training_tab', 'hide_empty' => true, 'orderby' => 'meta_value_num', 'meta_key' => 'hs_order' ) );
if ( is_wp_error( $tabs ) || ! $tabs ) {
	$tabs = get_terms( array( 'taxonomy' => 'hs_training_tab', 'hide_empty' => true ) );
}
$tabs = is_wp_error( $tabs ) ? array() : $tabs;
$default = 0;
foreach ( $tabs as $i => $t ) {
	if ( false !== stripos( $t->name, 'junior' ) ) {
		$default = $i;
	}
}

ob_start();
echo '<div class="hs-tabs" role="tablist" aria-label="Who is training">';
foreach ( $tabs as $i => $t ) {
	printf( '<button type="button" class="hs-tab" role="tab" id="tab-%1$s" aria-controls="panel-%1$s" aria-selected="%2$s">%3$s</button>', 'group-' . (int) $t->term_id, $i === $default ? 'true' : 'false', esc_html( $t->name ) );
}
echo '</div>';
$tabs_html = ob_get_clean();

hs_page_hero( 'Training', get_the_excerpt() ?: 'Find your training group', 'Around 50 sessions a week, seven days a week, at Swansea University Athletics Centre and beyond. Pick who\'s training to see the right sessions.', $tabs_html, true );
?>
<div class="hs-wrap hs-section--tight">
	<?php foreach ( $tabs as $i => $t ) : ?>
		<div role="tabpanel" id="panel-group-<?php echo (int) $t->term_id; ?>" aria-labelledby="tab-group-<?php echo (int) $t->term_id; ?>" <?php echo $i === $default ? '' : 'hidden'; ?>>
			<h2 class="screen-reader-text"><?php echo esc_html( $t->name ); ?></h2>
			<?php if ( $t->description ) : ?><p class="hs-muted" style="font-size:16px;max-width:760px;margin:0 0 18px"><?php echo esc_html( $t->description ); ?></p><?php endif; ?>
			<div class="hs-grid hs-grid--groups">
				<?php
				$groups = get_posts( array( 'post_type' => 'hs_training', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC', 'tax_query' => array( array( 'taxonomy' => 'hs_training_tab', 'terms' => $t->term_id ) ) ) );
				foreach ( $groups as $g ) :
					$m = fn( $k ) => get_post_meta( $g->ID, $k, true );
					?>
					<div class="hs-card hs-group">
						<div><h3><?php echo esc_html( $g->post_title ); ?></h3><span class="hs-muted" style="font-size:14px"><?php echo esc_html( $m( 'hs_ages' ) ); ?></span></div>
						<dl>
							<div><dt>When</dt><dd><?php echo esc_html( $m( 'hs_when' ) ); ?></dd></div>
							<div><dt>Cost</dt><dd><?php echo esc_html( $m( 'hs_cost' ) ); ?></dd></div>
							<div class="is-wide"><dt>Where</dt><dd><?php echo esc_html( $m( 'hs_where' ) ); ?></dd></div>
							<?php if ( $m( 'hs_coaches' ) ) : ?><div class="is-wide"><dt>Coaches</dt><dd><?php echo esc_html( $m( 'hs_coaches' ) ); ?></dd></div><?php endif; ?>
						</dl>
						<?php if ( $m( 'hs_note' ) ) : ?><p><?php echo esc_html( $m( 'hs_note' ) ); ?></p><?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>

<div class="hs-wrap hs-section hs-section--last">
	<div class="hs-banner-cta">
		<div><h2>Not sure which group?</h2><p>Our club coaching coordinator can point you to the right session for your age, event and ability.</p></div>
		<div class="hs-actions">
			<a class="hs-btn hs-btn--dark" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Ask a coach</a>
			<a class="hs-btn hs-btn--ghost" href="<?php echo esc_url( home_url( '/volunteers/' ) ); ?>">Meet the coaches</a>
		</div>
	</div>
</div>
<?php
get_footer();
