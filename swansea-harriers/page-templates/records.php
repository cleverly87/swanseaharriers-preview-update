<?php
/**
 * Template Name: Club records
 *
 * Records grouped by event group, with chips to switch group and a dropdown
 * to narrow by age group.
 */

defined( 'ABSPATH' ) || exit;
get_header();

hs_page_hero( 'Club records', get_the_excerpt() ?: 'The marks to beat', 'Every club record from U13 to senior, split by event group instead of one very long table.' );

$groups = get_terms( array( 'taxonomy' => 'hs_event_group', 'hide_empty' => false ) );
$groups = is_wp_error( $groups ) ? array() : array_values( array_filter( $groups, function ( $g ) {
	return (bool) get_posts( array( 'post_type' => 'hs_record', 'numberposts' => 1, 'fields' => 'ids', 'tax_query' => array( array( 'taxonomy' => 'hs_event_group', 'terms' => $g->term_id ) ) ) );
} ) );
$records = get_posts( array( 'post_type' => 'hs_record', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
$ages = array_unique( array_filter( array_map( fn( $r ) => get_post_meta( $r->ID, 'hs_age_group', true ), $records ) ) );
sort( $ages );
?>
<div class="hs-wrap hs-section--tight hs-section--last" style="display:flex;flex-direction:column;gap:18px" data-records>
	<div style="display:flex;flex-wrap:wrap;gap:12px 24px;align-items:center;justify-content:space-between">
		<div class="hs-chips" role="group" aria-label="Event group">
			<?php foreach ( $groups as $i => $g ) : ?>
				<button type="button" class="hs-chip" data-group="<?php echo esc_attr( $g->slug ); ?>" aria-pressed="<?php echo 0 === $i ? 'true' : 'false'; ?>"><?php echo esc_html( $g->name ); ?></button>
			<?php endforeach; ?>
		</div>
		<div style="display:flex;gap:8px;align-items:center">
			<label for="hs-age" style="font-size:14px;font-weight:600">Age group</label>
			<select class="hs-select" id="hs-age"><option value="">All age groups</option><?php foreach ( $ages as $a ) : ?><option><?php echo esc_html( $a ); ?></option><?php endforeach; ?></select>
		</div>
	</div>

	<div class="hs-table-wrap" style="background:#fff">
		<table class="hs-table hs-table--dark" style="font-size:15px;min-width:720px">
			<thead><tr><th scope="col">Event</th><th scope="col">Age group</th><th scope="col">Athlete</th><th scope="col">Mark</th><th scope="col">Year</th></tr></thead>
			<tbody>
				<?php
				foreach ( $records as $r ) :
					$g = wp_get_post_terms( $r->ID, 'hs_event_group', array( 'fields' => 'slugs' ) );
					$m = fn( $k ) => get_post_meta( $r->ID, $k, true );
					?>
					<tr data-group="<?php echo esc_attr( is_wp_error( $g ) ? '' : implode( ' ', $g ) ); ?>" data-age="<?php echo esc_attr( $m( 'hs_age_group' ) ); ?>">
						<th scope="row" style="font-weight:700"><?php echo esc_html( $m( 'hs_event' ) ); ?></th>
						<td class="hs-muted"><?php echo esc_html( $m( 'hs_age_group' ) ); ?></td>
						<td style="font-weight:600"><?php echo esc_html( $r->post_title ); ?></td>
						<td><span class="hs-mark"><?php echo esc_html( $m( 'hs_mark' ) ); ?></span></td>
						<td class="hs-muted"><?php echo esc_html( $m( 'hs_year' ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<div class="hs-banner-cta" style="padding:22px">
		<p style="margin:0;font-weight:700;font-size:16px">Think you've broken a record? Send your result to the records officer with a Power of 10 link.</p>
		<a class="hs-btn hs-btn--dark" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Submit a record</a>
	</div>
</div>
<?php
get_footer();
