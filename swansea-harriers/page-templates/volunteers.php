<?php
/**
 * Template Name: Volunteers
 *
 * Tabs: Coaches (with event-group filter), Team managers (split by list),
 * Officials, Officers & trustees. People come from Volunteers.
 */

defined( 'ABSPATH' ) || exit;
get_header();

$sections = array(
	'coaches'             => array( 'Coaches', 'UK Athletics qualified coaches at club nights. Filter by event group to find who coaches what.' ),
	'team-managers'       => array( 'Team managers', 'Who to contact about selection, travel and kit for each team.' ),
	'officials'           => array( 'Officials', 'Officials make every league match possible. Our coordinator can get you onto a Level 1 course.' ),
	'officers-trustees'   => array( 'Officers & trustees', 'Swansea Harriers is a registered charity run by a board of volunteer trustees.' ),
);
$people = array();
foreach ( $sections as $slug => $s ) {
	$people[ $slug ] = function_exists( 'hs_get_volunteers' ) ? hs_get_volunteers( $slug ) : array();
}

ob_start();
echo '<div class="hs-tabs" role="tablist" aria-label="Volunteer roles">';
$first = true;
foreach ( $sections as $slug => $s ) {
	printf( '<button type="button" class="hs-tab" role="tab" id="tab-%1$s" aria-controls="panel-%1$s" aria-selected="%2$s">%3$s · %4$d</button>', esc_attr( $slug ), $first ? 'true' : 'false', esc_html( $s[0] ), count( $people[ $slug ] ) );
	$first = false;
}
echo '</div>';
$tabs_html = ob_get_clean();

hs_page_hero( 'Volunteers', get_the_excerpt() ?: 'The people who make it happen', 'Every coach, team manager, official and trustee at Swansea Harriers is a volunteer.', $tabs_html, true );

$person = function ( $p ) {
	$role   = get_post_meta( $p->ID, 'hs_role', true );
	$detail = get_post_meta( $p->ID, 'hs_detail', true );
	$groups = wp_get_post_terms( $p->ID, 'hs_event_group', array( 'fields' => 'slugs' ) );
	$vacant = 0 === stripos( $p->post_title, 'vacant' );
	$photo  = get_the_post_thumbnail( $p, 'thumbnail', array( 'alt' => '' ) );
	?>
	<div class="hs-card hs-person" data-groups="<?php echo esc_attr( implode( ' ', is_wp_error( $groups ) ? array() : $groups ) ); ?>">
		<span class="hs-avatar<?php echo $vacant ? ' is-vacant' : ''; ?>" aria-hidden="true"><?php echo $photo ? wp_kses_post( $photo ) : esc_html( hs_initials( $p->post_title ) ); ?></span>
		<span style="min-width:0">
			<b><?php echo esc_html( $p->post_title ); ?></b>
			<?php if ( $role ) : ?><span class="hs-role"><?php echo esc_html( $role ); ?></span><?php endif; ?>
			<?php if ( $detail ) : ?><span class="hs-detail"><?php echo esc_html( $detail ); ?></span><?php endif; ?>
		</span>
	</div>
	<?php
};
?>
<div class="hs-wrap hs-section--tight">
	<?php $first = true; foreach ( $sections as $slug => $s ) : ?>
		<div role="tabpanel" id="panel-<?php echo esc_attr( $slug ); ?>" aria-labelledby="tab-<?php echo esc_attr( $slug ); ?>" <?php echo $first ? '' : 'hidden'; ?> style="display:flex;flex-direction:column;gap:16px" data-people>
			<p class="hs-muted" style="font-size:16px;max-width:760px;margin:0"><?php echo esc_html( $s[1] ); ?></p>

			<?php if ( 'coaches' === $slug ) : $groups = get_terms( array( 'taxonomy' => 'hs_event_group', 'hide_empty' => true, 'object_ids' => wp_list_pluck( $people[ $slug ], 'ID' ) ) ); ?>
				<div class="hs-chips" role="group" aria-label="Filter coaches by event group">
					<button type="button" class="hs-chip" data-group="all" aria-pressed="true">All coaches · <?php echo count( $people[ $slug ] ); ?></button>
					<?php foreach ( is_wp_error( $groups ) ? array() : $groups as $g ) : ?>
						<button type="button" class="hs-chip" data-group="<?php echo esc_attr( $g->slug ); ?>" aria-pressed="false"><?php echo esc_html( $g->name ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php
			if ( 'team-managers' === $slug ) {
				foreach ( array( 'xc' => 'Road & cross country', 'tf' => 'Track & field' ) as $list => $label ) {
					$set = array_filter( $people[ $slug ], fn( $p ) => get_post_meta( $p->ID, 'hs_list', true ) === $list );
					if ( ! $set ) {
						continue;
					}
					echo '<h2 class="hs-subhead">' . esc_html( $label ) . '</h2><div class="hs-grid hs-grid--people">';
					foreach ( $set as $p ) {
						$person( $p );
					}
					echo '</div>';
				}
			} else {
				echo '<div class="hs-grid hs-grid--people">';
				foreach ( $people[ $slug ] as $p ) {
					$person( $p );
				}
				echo '</div>';
			}
			?>
		</div>
	<?php $first = false; endforeach; ?>
</div>

<div class="hs-wrap hs-section hs-section--last">
	<div class="hs-banner-cta">
		<div><h2>Lend a hand</h2><p>Every league match needs officials and team helpers. Level 1 courses are available in Timekeeper, Field Judge, Track Judge and Start/Marksman.</p></div>
		<a class="hs-btn hs-btn--dark" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Volunteer with us</a>
	</div>
</div>
<?php
get_footer();
