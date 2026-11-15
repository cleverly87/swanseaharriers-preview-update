<?php
/**
 * Template Name: Results
 *
 * Leagues come from Leagues & results. Tabs: Winter / Summer (current season)
 * and Archive (leagues with "Current season" unticked). Each league shows its
 * standings panel, a button per round, and the selected round's table.
 * The most recent round with results opens by default.
 */

defined( 'ABSPATH' ) || exit;
get_header();

$seasons = array(
	'winter'  => array( 'Winter · Cross country', 'Cross country team results for every age group, filled in automatically after each fixture. Tap a round to see it.' ),
	'summer'  => array( 'Summer · Track & field', 'Track and field leagues for juniors and seniors: divisions, match dates and venues, with links to the full tables.' ),
	'archive' => array( 'Archive', 'Past seasons, grouped by league.' ),
);
// Open the tab for the season we're in: October–March = winter.
$month   = (int) wp_date( 'n' );
$default = ( $month >= 10 || $month <= 3 ) ? 'winter' : 'summer';

ob_start();
?>
<div class="hs-page-hero__row">
	<div class="hs-tabs" role="tablist" aria-label="Season">
		<?php foreach ( $seasons as $key => $s ) : ?>
			<button type="button" class="hs-tab" role="tab" id="tab-<?php echo esc_attr( $key ); ?>" aria-controls="panel-<?php echo esc_attr( $key ); ?>" aria-selected="<?php echo $key === $default ? 'true' : 'false'; ?>"><?php echo esc_html( $s[0] ); ?></button>
		<?php endforeach; ?>
	</div>
	<form action="https://www.thepowerof10.info/athletes/athleteslookup.aspx" method="get" target="_blank">
		<label for="po10">Find an athlete on Power of 10</label>
		<input class="hs-input" id="po10" name="surname" type="text" placeholder="Surname" style="width:160px">
		<input type="hidden" name="club" value="Swansea">
		<button class="hs-btn hs-btn--bright hs-btn--sm" type="submit">Search</button>
	</form>
</div>
<?php
$after = ob_get_clean();
hs_page_hero( 'Results & league tables', get_the_excerpt() ?: 'Every team league in one place', '', $after, true );
?>

<div class="hs-wrap hs-section--tight hs-section--last">
	<?php foreach ( $seasons as $key => $s ) : ?>
		<?php
		if ( ! function_exists( 'hs_get_leagues' ) ) {
			echo '<p>Please activate the Harriers Core plugin.</p>';
			break;
		}
		$leagues = 'archive' === $key ? hs_get_leagues( '', false ) : hs_get_leagues( $key, true );
		?>
		<div role="tabpanel" id="panel-<?php echo esc_attr( $key ); ?>" aria-labelledby="tab-<?php echo esc_attr( $key ); ?>" <?php echo $key === $default ? '' : 'hidden'; ?> style="display:flex;flex-direction:column;gap:24px">
			<p class="hs-muted" style="margin:0;font-size:16px;max-width:760px"><?php echo esc_html( $s[1] ); ?></p>
			<?php if ( ! $leagues ) : ?>
				<div class="hs-empty"><b>Nothing here yet</b><span>Leagues appear here once they're added under Leagues &amp; results.</span></div>
			<?php endif; ?>

			<?php
			foreach ( $leagues as $league ) :
				$lid       = $league->ID;
				$rounds    = hs_get_rounds( $lid );
				$views     = array();
				$next_seen = false;
				$open      = 0;
				foreach ( $rounds as $i => $r ) {
					$date    = get_post_meta( $r->ID, 'hs_date', true );
					$is_next = ! $next_seen && $date >= hs_today();
					if ( $is_next ) {
						$next_seen = true;
					}
					$v = hs_round_view( $r, $is_next );
					$views[] = $v;
					if ( $v['has_rows'] ) {
						$open = $i;
					}
				}
				$standings = hs_parse_lines( get_post_meta( $lid, 'hs_standings', true ), 3 );
				$name      = get_post_meta( $lid, 'hs_display_name', true ) ?: $league->post_title;
				$official  = get_post_meta( $lid, 'hs_official_url', true );
				$src       = get_post_meta( $lid, 'hs_source', true );
				$score_lbl = get_post_meta( $lid, 'hs_score_label', true ) ?: 'Score';
				$foot      = get_post_meta( $lid, 'hs_footnote', true );
				$domain    = $official ? preg_replace( '/^www\./', '', (string) wp_parse_url( $official, PHP_URL_HOST ) ) : '';
				?>
				<article class="hs-card hs-league" data-league>
					<div class="hs-league__head">
						<div>
							<span class="hs-muted" style="font-size:13px;font-weight:600"><?php echo esc_html( get_post_meta( $lid, 'hs_squad', true ) ); ?><?php echo 'archive' === $key ? ' · ' . esc_html( get_post_meta( $lid, 'hs_season_label', true ) ) : ''; ?></span>
							<h2><?php echo esc_html( $name ); ?></h2>
							<span class="hs-muted" style="font-size:14px"><?php echo esc_html( get_post_meta( $lid, 'hs_full_name', true ) ); ?></span>
						</div>
						<div class="hs-actions">
							<?php if ( $official ) : ?><a class="hs-btn hs-btn--sm" href="<?php echo esc_url( $official ); ?>" target="_blank" rel="noopener">Official league results</a><?php endif; ?>
							<a class="hs-btn hs-btn--ghost hs-btn--sm" href="<?php echo esc_url( get_category_link( get_cat_ID( 'Results' ) ) ?: home_url( '/news/' ) ); ?>">Club write-ups</a>
						</div>
					</div>

					<?php if ( $standings ) : ?>
						<div class="hs-standings">
							<?php foreach ( $standings as $st ) : ?>
								<div class="hs-standing"><span><?php echo esc_html( $st[0] ); ?></span><b><?php echo esc_html( $st[1] ); ?></b><span><?php echo esc_html( $st[2] ); ?></span></div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<?php if ( $views ) : ?>
						<div class="hs-rounds">
							<?php
							foreach ( $views as $i => $v ) :
								$cls = array( 'Results in' => 'is-in', 'Completed' => 'is-done', 'Next up' => 'is-next', 'Cancelled' => 'is-cancelled' )[ $v['status'] ] ?? '';
								?>
								<button type="button" class="hs-round-btn" data-round="<?php echo (int) $v['id']; ?>" aria-pressed="<?php echo $i === $open ? 'true' : 'false'; ?>" aria-controls="round-<?php echo (int) $v['id']; ?>">
									<span class="hs-round-btn__status <?php echo esc_attr( $cls ); ?>"><?php echo esc_html( $v['status'] ); ?></span>
									<b><?php echo esc_html( $v['label'] ); ?></b>
									<span><?php echo esc_html( $v['detail'] ); ?></span>
								</button>
							<?php endforeach; ?>
						</div>

						<?php foreach ( $views as $i => $v ) : ?>
							<div class="hs-round-panel" id="round-<?php echo (int) $v['id']; ?>" <?php echo $i === $open ? '' : 'hidden'; ?>>
								<div class="hs-round-panel__head">
									<b><?php echo esc_html( $v['label'] . ( $v['detail'] ? ' · ' . $v['detail'] : '' ) ); ?></b>
									<?php if ( $v['has_rows'] && $domain ) : ?>
										<span class="hs-sync"><?php hs_the_icon( 'refresh', 14 ); ?>
											<?php
											echo esc_html(
												( in_array( $src, array( 'gwent', 'westglam' ), true ) ? 'Synced from ' . $domain : 'Source: ' . $domain )
												. ( $v['final'] ? ' · final results' : ' · provisional' )
											);
											?>
										</span>
									<?php endif; ?>
								</div>
								<?php if ( $v['has_rows'] ) : ?>
									<div class="hs-table-wrap">
										<table class="hs-table">
											<thead><tr><th scope="col">Race</th><th scope="col">Team position</th><th scope="col"><?php echo esc_html( $score_lbl ); ?></th><th scope="col">First Harrier home</th></tr></thead>
											<tbody>
												<?php foreach ( $v['rows'] as $row ) : ?>
													<tr>
														<th scope="row"><?php echo esc_html( $row['race'] ); ?></th>
														<td><span class="hs-pos hs-pos--<?php echo esc_attr( $row['medal'] ); ?>"><?php echo esc_html( $row['pos'] ); ?></span><span class="hs-of"><?php echo esc_html( $row['of'] ); ?></span></td>
														<td class="hs-muted"><?php echo esc_html( $row['score'] ); ?></td>
														<td><?php echo esc_html( $row['first'] ); ?></td>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									</div>
									<?php if ( $foot ) : ?><p class="hs-muted" style="font-size:13px;margin:10px 0 0"><?php echo esc_html( $foot ); ?></p><?php endif; ?>
								<?php elseif ( $v['cancelled'] ) : ?>
									<div class="hs-empty"><b>Match cancelled</b><span>This fixture did not take place.</span></div>
								<?php elseif ( $v['past'] ) : ?>
									<div class="hs-empty"><b>Match complete</b><span><?php echo in_array( $src, array( 'gwent', 'westglam' ), true ) ? 'Results will appear here as soon as the league publishes them.' : 'Full results are on the league\'s website, linked above.'; ?></span></div>
								<?php else : ?>
									<div class="hs-empty"><b>Results will appear here automatically</b><span><?php echo in_array( $src, array( 'gwent', 'westglam' ), true ) ? 'The site checks the league\'s results page after the race and fills this table as soon as they\'re published.' : 'Results will be added after the match.'; ?></span></div>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endforeach; ?>

	<section class="hs-section">
		<div class="hs-panel-dark hs-open-results on-dark">
			<div>
				<h2>Open race results</h2>
				<p style="color:var(--hs-on-dark);margin:8px 0 0">Road races, opens and championships are timed by outside providers. Start here.</p>
			</div>
			<div class="hs-actions">
				<?php foreach ( hs_default_tools() as $label => $url ) : ?>
					<a class="hs-btn <?php echo 'Power of 10' === $label ? 'hs-btn--bright' : 'hs-btn--on-dark'; ?> hs-btn--sm" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
</div>

<?php
get_footer();
