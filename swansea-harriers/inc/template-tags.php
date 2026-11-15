<?php
/**
 * Small output helpers shared by templates.
 */

defined( 'ABSPATH' ) || exit;

/**
 * HTML the theme's own building blocks may contain: normal post HTML plus the
 * form controls, tab attributes and inline SVG icons used in page banners.
 * Everything the theme prints that isn't plain text goes through this filter.
 */
function hs_allowed_html() {
	static $allowed = null;
	if ( null !== $allowed ) {
		return $allowed;
	}
	$allowed = wp_kses_allowed_html( 'post' );
	$ui = array( 'class' => true, 'id' => true, 'style' => true, 'role' => true, 'hidden' => true, 'tabindex' => true, 'aria-label' => true, 'aria-hidden' => true, 'aria-selected' => true, 'aria-controls' => true, 'aria-labelledby' => true, 'aria-pressed' => true, 'aria-expanded' => true, 'aria-current' => true, 'data-filter' => true, 'data-group' => true );
	foreach ( array( 'div', 'span', 'a', 'button', 'nav', 'ul', 'li' ) as $tag ) {
		$allowed[ $tag ] = array_merge( $allowed[ $tag ] ?? array(), $ui, array( 'type' => true, 'href' => true, 'target' => true, 'rel' => true ) );
	}
	$allowed['form']   = array( 'action' => true, 'method' => true, 'role' => true, 'target' => true, 'class' => true );
	$allowed['input']  = array( 'type' => true, 'name' => true, 'id' => true, 'value' => true, 'placeholder' => true, 'class' => true, 'style' => true, 'aria-label' => true );
	$allowed['label']  = array( 'for' => true, 'class' => true );
	$allowed['select'] = array( 'name' => true, 'id' => true, 'class' => true );
	$allowed['option'] = array( 'value' => true, 'selected' => true );
	$allowed['svg']    = array( 'width' => true, 'height' => true, 'viewbox' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'aria-hidden' => true, 'focusable' => true, 'role' => true, 'aria-label' => true );
	$allowed['path']   = array( 'd' => true );
	return $allowed;
}

/** Print an icon (see inc/icons.php). */
function hs_the_icon( $name, $size = 24, $label = '' ) {
	echo wp_kses( hs_icon( $name, $size, $label ), hs_allowed_html() );
}

/** Print the coloured category tag for a news post. */
function hs_the_post_tag( $post = null ) {
	echo wp_kses_post( hs_post_tag( $post ) );
}

function hs_logo_url() {
	$id = get_theme_mod( 'custom_logo' );
	if ( $id ) {
		$src = wp_get_attachment_image_url( $id, 'medium' );
		if ( $src ) {
			return $src;
		}
	}
	return get_template_directory_uri() . '/assets/images/crest.png';
}

/** Inner-page banner. Uses the page excerpt as the big title when set. */
function hs_page_hero( $eyebrow, $title, $intro = '', $after = '', $has_tabs = false ) {
	echo '<section class="hs-page-hero on-dark' . ( $has_tabs ? ' has-tabs' : '' ) . '"><div class="hs-wrap">';
	if ( $eyebrow ) {
		echo '<p class="hs-eyebrow">' . esc_html( $eyebrow ) . '</p>';
	}
	echo '<h1>' . esc_html( $title ) . '</h1>';
	if ( $intro ) {
		echo '<p>' . esc_html( $intro ) . '</p>';
	}
	echo wp_kses( $after, hs_allowed_html() );
	echo '</div></section>';
}

/** The news category as a coloured tag. */
function hs_post_tag( $post = null ) {
	$cats = get_the_category( $post ? $post->ID : 0 );
	if ( ! $cats ) {
		return '';
	}
	$c = $cats[0];
	return '<span class="hs-tag hs-tag--' . esc_attr( $c->slug ) . '">' . esc_html( $c->name ) . '</span>';
}

/** Render one fixture as a row card. */
function hs_fixture_row( $f, $show_type = true ) {
	$btn  = $f['external'] ? 'hs-btn hs-btn--sm' : 'hs-btn hs-btn--ghost hs-btn--sm';
	?>
	<div class="hs-card hs-row-card hs-fixture" data-discipline="<?php echo esc_attr( $f['discipline'] ); ?>" data-type="<?php echo esc_attr( $f['type_slug'] ); ?>">
		<span class="hs-datebox"><b><?php echo esc_html( $f['day'] ); ?></b><span><?php echo esc_html( $f['dow'] ); ?></span></span>
		<div class="hs-row-card__body">
			<span class="hs-row-card__title"><?php echo esc_html( $f['title'] ); ?></span>
			<span class="hs-row-card__meta">
				<?php if ( $show_type && $f['type'] ) : ?><span class="hs-tag hs-tag--<?php echo esc_attr( $f['type_slug'] ); ?>"><?php echo esc_html( $f['type'] ); ?></span><?php endif; ?>
				<span><?php echo esc_html( $f['meta'] ); ?></span>
			</span>
		</div>
		<a class="<?php echo esc_attr( $btn ); ?>" href="<?php echo esc_url( $f['cta_url'] ); ?>"<?php echo $f['external'] ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html( $f['cta'] ); ?></a>
	</div>
	<?php
}

/** A news card (grid or row style). */
function hs_news_card( $post, $row = false ) {
	$thumb = get_the_post_thumbnail( $post, 'hs-card', array( 'alt' => '' ) );
	?>
	<a class="hs-card hs-news-card<?php echo $row ? ' hs-news-row' : ''; ?>" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
		<?php if ( $thumb || ! $row ) : ?>
			<div class="hs-news-card__img"><?php echo wp_kses_post( $thumb ); ?></div>
		<?php endif; ?>
		<div class="hs-news-card__body">
			<div class="hs-news-card__meta"><?php hs_the_post_tag( $post ); ?><span><?php echo esc_html( get_the_date( 'j M Y', $post ) ); ?></span></div>
			<span class="hs-news-card__title"><?php echo esc_html( get_the_title( $post ) ); ?></span>
			<span class="hs-news-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $post ), 28 ) ); ?></span>
		</div>
	</a>
	<?php
}

/** Fallback list of tool links when no "Athlete tools" menu is set. */
function hs_default_tools() {
	return array(
		'Power of 10'           => 'https://www.thepowerof10.info',
		'OpenTrack'             => 'https://opentrack.run',
		'Fabian4'               => 'https://www.fabian4.co.uk',
		'Welsh Athletics'       => 'https://www.welshathletics.org',
		'Race Timing Solutions' => 'https://www.racetimingsolutions.co.uk',
		'ResultsBase'           => 'https://www.resultsbase.net',
	);
}

function hs_platform_blurbs() {
	return array(
		'Power of 10'           => 'Rankings, PBs and athlete profiles',
		'OpenTrack'             => 'Track & field entries and live results',
		'Fabian4'               => 'Race entries and results',
		'Welsh Athletics'       => 'National fixtures and championships',
		'Race Timing Solutions' => 'Chip-timed road and XC results',
		'ResultsBase'           => 'Event entries and results archive',
	);
}
