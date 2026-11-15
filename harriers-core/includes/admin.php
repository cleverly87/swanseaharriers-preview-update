<?php
/**
 * Admin screen: Leagues & results → Import results.
 * Lists this season's rounds with their status, has "Import now" buttons,
 * shows the import log, and offers a one-off "Load starter content".
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=hs_league', 'Import results', 'Import results', 'edit_others_posts', 'hs-import', 'hs_render_import_page' );
} );

add_action( 'admin_post_hs_import_round', function () {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'hs_import' );
	$id = isset( $_POST['round'] ) ? absint( $_POST['round'] ) : 0;
	if ( $id ) {
		$msg = HS_Importer::run_round( $id );
	} else {
		HS_Importer::run_due();
		$msg = 'Checked all rounds that are due.';
	}
	hs_admin_redirect_with_message( $msg );
} );

add_action( 'wp_ajax_hs_seed_step', function () {
	if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'hs_seed', '_wpnonce', false ) ) {
		wp_send_json_error( 'Not allowed.', 403 );
	}
	$step = isset( $_POST['step'] ) ? sanitize_key( wp_unslash( $_POST['step'] ) ) : '';
	if ( ! array_key_exists( $step, hs_seed_steps() ) ) {
		wp_send_json_error( 'Unknown step.', 400 );
	}
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 120 );
	}
	$msg = hs_seed_starter_content( $step );
	wp_send_json_success( preg_replace( '/^Starter content loaded: (\d+) new items.*$/', '$1 added', $msg ) );
} );

/**
 * Show the result on the next screen. The message is kept server-side for this
 * user only, so it can't be spoofed by someone sending an admin a crafted link.
 */
function hs_admin_redirect_with_message( $msg ) {
	set_transient( 'hs_notice_' . get_current_user_id(), (string) $msg, 120 );
	wp_safe_redirect( add_query_arg( array( 'page' => 'hs-import', 'post_type' => 'hs_league' ), admin_url( 'edit.php' ) ) );
	exit;
}

add_action( 'admin_post_hs_seed', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'hs_seed' );
	$msg = hs_seed_starter_content();
	hs_admin_redirect_with_message( $msg );
} );

function hs_render_import_page() {
	echo '<div class="wrap"><h1>Import results</h1>';
	$notice = get_transient( 'hs_notice_' . get_current_user_id() );
	if ( $notice ) {
		delete_transient( 'hs_notice_' . get_current_user_id() );
		echo '<div class="notice notice-success" role="status"><p>' . esc_html( $notice ) . '</p></div>';
	}
	$next = wp_next_scheduled( HS_Importer::HOOK );
	echo '<p>Results for the Gwent and West Glamorgan leagues are fetched automatically every 6 hours for three weeks after each round, until they are marked final. ';
	echo $next ? 'Next automatic check: <strong>' . esc_html( wp_date( 'D j M, H:i', $next ) ) . '</strong>.' : 'Automatic checks are not scheduled.';
	echo '</p>';

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:12px 0 20px">';
	wp_nonce_field( 'hs_import' );
	echo '<input type="hidden" name="action" value="hs_import_round"><input type="hidden" name="round" value="0">';
	submit_button( 'Check all due rounds now', 'primary', 'submit', false );
	echo '</form>';

	echo '<table class="widefat striped"><thead><tr><th>League</th><th>Round</th><th>Date</th><th>Results</th><th>Last checked</th><th></th></tr></thead><tbody>';
	foreach ( hs_get_leagues( '', true ) as $league ) {
		$src = get_post_meta( $league->ID, 'hs_source', true );
		foreach ( hs_get_rounds( $league->ID ) as $r ) {
			$rows = hs_parse_lines( get_post_meta( $r->ID, 'hs_rows', true ) );
			$date = get_post_meta( $r->ID, 'hs_date', true );
			echo '<tr><td>' . esc_html( $league->post_title ) . '</td>';
			echo '<td><a href="' . esc_url( get_edit_post_link( $r->ID ) ) . '">' . esc_html( get_the_title( $r ) ) . '</a></td>';
			echo '<td>' . esc_html( $date ? wp_date( 'j M Y', strtotime( $date ) ) : '—' ) . '</td>';
			echo '<td>' . ( $rows ? esc_html( count( $rows ) . ' races · ' . ( get_post_meta( $r->ID, 'hs_status', true ) ?: 'provisional' ) ) : '—' ) . '</td>';
			echo '<td>' . esc_html( get_post_meta( $r->ID, 'hs_last_import', true ) ?: '—' ) . '</td><td>';
			if ( in_array( $src, array( 'gwent', 'westglam' ), true ) && $date && $date <= hs_today() ) {
				echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
				wp_nonce_field( 'hs_import' );
				echo '<input type="hidden" name="action" value="hs_import_round"><input type="hidden" name="round" value="' . (int) $r->ID . '">';
				submit_button( 'Import now', 'secondary small', 'submit', false );
				echo '</form>';
			}
			echo '</td></tr>';
		}
	}
	echo '</tbody></table>';

	$log = get_option( HS_Importer::LOG, array() );
	echo '<h2 style="margin-top:28px">Recent activity</h2>';
	if ( ! $log ) {
		echo '<p>Nothing yet.</p>';
	} else {
		echo '<table class="widefat striped"><tbody>';
		foreach ( array_slice( $log, 0, 20 ) as $l ) {
			echo '<tr><td style="width:110px">' . esc_html( $l['time'] ) . '</td><td>' . esc_html( $l['round'] ) . '</td><td>' . esc_html( $l['msg'] ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}

	if ( current_user_can( 'manage_options' ) ) {
		echo '<h2 style="margin-top:28px">Starter content</h2><p>Loads the club\'s current fixtures, leagues and results, training groups, volunteers, records, pages and menus from the website design. Safe to run more than once; anything that already exists is left alone.</p>';
		echo '<form id="hs-seed" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'hs_seed' );
		echo '<input type="hidden" name="action" value="hs_seed">';
		submit_button( 'Load starter content', 'secondary', 'submit', false );
		echo '</form><ol id="hs-seed-progress" style="margin-top:12px" aria-live="polite"></ol>';
		$steps = wp_json_encode( hs_seed_steps(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
		$nonce = wp_create_nonce( 'hs_seed' );
		$ajax  = esc_url_raw( admin_url( 'admin-ajax.php' ) );
		?>
		<script>
		(function () {
			const form = document.getElementById('hs-seed');
			const list = document.getElementById('hs-seed-progress');
			const steps = <?php echo $steps; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded with HEX flags; safe inside a script. ?>;
			form.addEventListener('submit', async function (e) {
				e.preventDefault();
				if (!confirm('Load starter content?')) return;
				const btn = form.querySelector('[type=submit]');
				btn.disabled = true;
				list.innerHTML = '';
				for (const [key, label] of Object.entries(steps)) {
					const li = document.createElement('li');
					li.textContent = label + '\u2026';
					list.appendChild(li);
					let ok = false, msg = '';
					for (let attempt = 0; attempt < 3 && !ok; attempt++) {
						try {
							const body = new URLSearchParams({ action: 'hs_seed_step', step: key, _wpnonce: '<?php echo esc_js( $nonce ); ?>' });
							const r = await fetch('<?php echo esc_js( $ajax ); ?>', { method: 'POST', body, credentials: 'same-origin' });
							const j = await r.json();
							ok = !!(j && j.success);
							msg = j && j.data ? j.data : '';
						} catch (err) { msg = 'retrying'; }
					}
					li.textContent = label + (ok ? ' \u2713 ' + msg : ' \u2717 failed. Click the button again to carry on; finished steps are skipped.');
					if (!ok) { btn.disabled = false; return; }
				}
				const done = document.createElement('li');
				done.innerHTML = '<strong>All done.</strong> <a href="<?php echo esc_url( home_url( '/' ) ); ?>">View the site</a>';
				list.appendChild(done);
				btn.disabled = false;
			});
		})();
		</script>
		<?php
	}
	echo '</div>';
}

/* Friendlier "Enter title here" placeholders. */
add_filter( 'enter_title_here', function ( $t, $post ) {
	$map = array(
		'hs_fixture'   => 'Race name, e.g. Gwent League XC · Match 3 of 5',
		'hs_league'    => 'League and season, e.g. Gwent League 2026/27',
		'hs_round'     => 'Round name, e.g. Match 3',
		'hs_training'  => 'Group name, e.g. Faster endurance group',
		'hs_volunteer' => 'Full name (or Vacant)',
		'hs_record'    => 'Athlete name (or relay team)',
	);
	return $map[ $post->post_type ] ?? $t;
}, 10, 2 );
