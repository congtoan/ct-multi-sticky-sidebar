<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	add_options_page(
		__( 'CT Multi Sticky Sidebar', 'ct-multi-sticky-sidebar' ),
		__( 'CT Multi Sticky Sidebar', 'ct-multi-sticky-sidebar' ),
		'manage_options',
		'ct-multi-sticky-sidebar',
		'lss_render_page'
	);
} );

add_filter( 'plugin_action_links_' . plugin_basename( LSS_DIR . 'ct-multi-sticky-sidebar.php' ), function ( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=ct-multi-sticky-sidebar' ) ) . '">' . esc_html__( 'Settings', 'ct-multi-sticky-sidebar' ) . '</a>' );
	return $links;
} );

function lss_settings_url( $args = array() ) {
	return add_query_arg( array_merge( array( 'page' => 'ct-multi-sticky-sidebar' ), $args ), admin_url( 'options-general.php' ) );
}

/* ---------- Save settings ---------- */
add_action( 'admin_post_lss_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do this.', 'ct-multi-sticky-sidebar' ), 403 );
	}
	check_admin_referer( 'lss_save' );

	$input = isset( $_POST['lss'] ) ? wp_unslash( $_POST['lss'] ) : array(); // phpcs:ignore -- sanitized in lss_sanitize_settings()
	$clean = lss_sanitize_settings( $input );
	lss_save_settings( $clean );

	$res = lss_generate( $clean );
	if ( is_wp_error( $res ) ) {
		wp_safe_redirect( lss_settings_url( array( 'lss_msg' => 'failed', 'lss_code' => $res->get_error_code() ) ) );
	} else {
		wp_safe_redirect( lss_settings_url( array( 'lss_msg' => 'saved' ) ) );
	}
	exit;
} );

/* ---------- Manual regenerate ---------- */
add_action( 'admin_post_lss_regenerate', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do this.', 'ct-multi-sticky-sidebar' ), 403 );
	}
	check_admin_referer( 'lss_regenerate' );

	$res = lss_generate();
	if ( is_wp_error( $res ) ) {
		wp_safe_redirect( lss_settings_url( array( 'lss_msg' => 'failed', 'lss_code' => $res->get_error_code() ) ) );
	} else {
		wp_safe_redirect( lss_settings_url( array( 'lss_msg' => 'regenerated' ) ) );
	}
	exit;
} );

/* ---------- Settings page ---------- */
function lss_group_row( $i, $g ) {
	$n = 'lss[groups][' . $i . ']';
	?>
	<tr class="lss-row">
		<td><input type="text" class="large-text code" name="<?php echo esc_attr( $n ); ?>[sidebar]" value="<?php echo esc_attr( $g['sidebar'] ); ?>" placeholder="#sidebar, .sidebar-2"></td>
		<td><input type="text" class="regular-text code" name="<?php echo esc_attr( $n ); ?>[container]" value="<?php echo esc_attr( $g['container'] ); ?>" placeholder="#content"></td>
		<td><input type="number" min="0" max="1000" style="width:80px" name="<?php echo esc_attr( $n ); ?>[marginTop]" value="<?php echo esc_attr( $g['marginTop'] ); ?>" placeholder="<?php esc_attr_e( 'global', 'ct-multi-sticky-sidebar' ); ?>"></td>
		<td><input type="number" min="0" max="1000" style="width:80px" name="<?php echo esc_attr( $n ); ?>[marginBottom]" value="<?php echo esc_attr( $g['marginBottom'] ); ?>" placeholder="<?php esc_attr_e( 'global', 'ct-multi-sticky-sidebar' ); ?>"></td>
		<td><button type="button" class="button lss-remove" aria-label="<?php esc_attr_e( 'Remove', 'ct-multi-sticky-sidebar' ); ?>">&times;</button></td>
	</tr>
	<?php
}

function lss_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s      = lss_get_settings();
	$p      = lss_paths();
	$msg    = isset( $_GET['lss_msg'] ) ? sanitize_key( $_GET['lss_msg'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$code   = isset( $_GET['lss_code'] ) ? sanitize_key( $_GET['lss_code'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$exists = file_exists( $p['file'] );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'CT Multi Sticky Sidebar', 'ct-multi-sticky-sidebar' ); ?></h1>

		<?php if ( 'saved' === $msg ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved and the JS file regenerated.', 'ct-multi-sticky-sidebar' ); ?></p></div>
		<?php elseif ( 'regenerated' === $msg ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'The JS file was regenerated from the current settings.', 'ct-multi-sticky-sidebar' ); ?></p></div>
		<?php elseif ( 'failed' === $msg ) : ?>
			<div class="notice notice-error"><p>
				<?php
				/* translators: 1: error code, 2: directory path */
				printf( esc_html__( 'Could not generate the JS file (error code: %1$s). Check that %2$s is writable.', 'ct-multi-sticky-sidebar' ), esc_html( $code ), '<code>' . esc_html( $p['dir'] ) . '</code>' );
				?>
			</p></div>
		<?php endif; ?>
		<?php if ( false === get_option( LSS_RUNTIME_OPTION, false ) ) : ?>
			<div class="notice notice-warning"><p><?php echo wp_kses( __( 'Version 3.x adds "Load the JS only where a sidebar is rendered" and "Do not load on". Review them and click <strong>Save</strong> once to apply. Until then the plugin loads the JS on every page, like 2.x.', 'ct-multi-sticky-sidebar' ), array( 'strong' => array() ) ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="lss_save">
			<?php wp_nonce_field( 'lss_save' ); ?>

			<h2><?php esc_html_e( 'Sidebars', 'ct-multi-sticky-sidebar' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Each row is one group: the sidebar CSS selector (several selectors can be separated by commas) and the CSS selector of the container it should stay inside. Leave a container empty to use the sidebar\'s parent element, and leave the margins empty to use the global values below.', 'ct-multi-sticky-sidebar' ); ?></p>
			<table class="widefat striped" id="lss-groups" style="max-width:1100px">
				<thead><tr>
					<th><?php esc_html_e( 'Sidebar CSS selector', 'ct-multi-sticky-sidebar' ); ?></th>
					<th><?php esc_html_e( 'Container CSS selector', 'ct-multi-sticky-sidebar' ); ?></th>
					<th><?php esc_html_e( 'Top margin', 'ct-multi-sticky-sidebar' ); ?></th>
					<th><?php esc_html_e( 'Bottom margin', 'ct-multi-sticky-sidebar' ); ?></th>
					<th></th>
				</tr></thead>
				<tbody>
					<?php foreach ( $s['groups'] as $i => $g ) { lss_group_row( $i, $g ); } ?>
				</tbody>
			</table>
			<p><button type="button" class="button" id="lss-add"><?php esc_html_e( '+ Add sidebar', 'ct-multi-sticky-sidebar' ); ?></button></p>

			<h2><?php esc_html_e( 'Global settings', 'ct-multi-sticky-sidebar' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr><th><label for="lss-mw"><?php esc_html_e( 'Minimum width (px)', 'ct-multi-sticky-sidebar' ); ?></label></th>
					<td><input id="lss-mw" type="number" min="0" max="5000" name="lss[minWidth]" value="<?php echo esc_attr( $s['minWidth'] ); ?>"> <span class="description"><?php esc_html_e( 'Below this viewport width the sidebar behaves normally.', 'ct-multi-sticky-sidebar' ); ?></span></td></tr>
				<tr><th><label for="lss-mt"><?php esc_html_e( 'Top margin (px)', 'ct-multi-sticky-sidebar' ); ?></label></th>
					<td><input id="lss-mt" type="number" min="0" max="1000" name="lss[marginTop]" value="<?php echo esc_attr( $s['marginTop'] ); ?>"></td></tr>
				<tr><th><label for="lss-mb"><?php esc_html_e( 'Bottom margin (px)', 'ct-multi-sticky-sidebar' ); ?></label></th>
					<td><input id="lss-mb" type="number" min="0" max="1000" name="lss[marginBottom]" value="<?php echo esc_attr( $s['marginBottom'] ); ?>"></td></tr>
				<tr><th><label for="lss-sb"><?php esc_html_e( 'Sidebar behavior', 'ct-multi-sticky-sidebar' ); ?></label></th>
					<td><select id="lss-sb" name="lss[sidebarBehavior]">
						<?php foreach ( array( 'modern' => __( 'modern (recommended)', 'ct-multi-sticky-sidebar' ), 'stick-to-top' => 'stick-to-top', 'stick-to-bottom' => 'stick-to-bottom' ) as $v => $label ) : ?>
							<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $s['sidebarBehavior'], $v ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'modern: a sidebar taller than the screen sticks to the bottom while scrolling down and to the top while scrolling up, so every part of it can be read.', 'ct-multi-sticky-sidebar' ); ?></p></td></tr>
				<tr><th><?php esc_html_e( 'Update sidebar height', 'ct-multi-sticky-sidebar' ); ?></th>
					<td><input type="hidden" name="lss[updateSidebarHeight]" value="0"><label><input type="checkbox" name="lss[updateSidebarHeight]" value="1" <?php checked( $s['updateSidebarHeight'] ); ?>> <?php esc_html_e( 'Enable (updateSidebarHeight)', 'ct-multi-sticky-sidebar' ); ?></label>
						<p class="description"><?php esc_html_e( 'Stretch the sidebar to the container height (useful when the sidebar background or border looks cut off).', 'ct-multi-sticky-sidebar' ); ?></p></td></tr>
				<tr><th><?php esc_html_e( 'Disable on responsive layouts', 'ct-multi-sticky-sidebar' ); ?></th>
					<td><input type="hidden" name="lss[disableOnResponsiveLayouts]" value="0"><label><input type="checkbox" name="lss[disableOnResponsiveLayouts]" value="1" <?php checked( $s['disableOnResponsiveLayouts'] ); ?>> <?php esc_html_e( 'Enable (disableOnResponsiveLayouts)', 'ct-multi-sticky-sidebar' ); ?></label>
						<p class="description"><?php esc_html_e( 'Turn the sticky effect off when the sidebar drops below the content (sidebar almost as wide as its container).', 'ct-multi-sticky-sidebar' ); ?></p></td></tr>
			</table>

			<h2><?php esc_html_e( 'Load the JS only where a sidebar is rendered', 'ct-multi-sticky-sidebar' ); ?></h2>
			<p class="description" style="max-width:800px"><?php echo wp_kses( __( 'Select the widget areas your sidebars use. The JS file is then loaded only when the template actually renders (<code>dynamic_sidebar()</code>) one of these areas and the area has widgets, so pages without a sidebar do not download it. <strong>No area selected = always load the JS.</strong>', 'ct-multi-sticky-sidebar' ), array( 'code' => array(), 'strong' => array() ) ); ?></p>
			<?php
			global $wp_registered_sidebars;
			$reg = is_array( $wp_registered_sidebars ) ? $wp_registered_sidebars : array();
			// Keep areas that are selected but no longer registered (theme switched...).
			foreach ( $s['areas'] as $a ) {
				if ( ! isset( $reg[ $a ] ) ) {
					$reg[ $a ] = array( 'name' => __( '(no longer registered)', 'ct-multi-sticky-sidebar' ) );
				}
			}
			?>
			<fieldset style="columns:2;max-width:800px;margin:8px 0 0">
				<?php foreach ( $reg as $id => $sb ) : ?>
					<label style="display:block;margin:0 0 6px"><input type="checkbox" name="lss[areas][]" value="<?php echo esc_attr( $id ); ?>" <?php checked( in_array( (string) $id, $s['areas'], true ) ); ?>> <?php echo esc_html( isset( $sb['name'] ) ? $sb['name'] : $id ); ?> <code><?php echo esc_html( $id ); ?></code></label>
				<?php endforeach; ?>
			</fieldset>

			<h2><?php esc_html_e( 'Do not load on', 'ct-multi-sticky-sidebar' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'Page types', 'ct-multi-sticky-sidebar' ); ?></th>
					<td>
						<?php
						$types = array(
							'home'     => __( 'Home page / blog page (is_home, is_front_page)', 'ct-multi-sticky-sidebar' ),
							'category' => __( 'Category archives (is_category)', 'ct-multi-sticky-sidebar' ),
							'pages'    => __( 'Pages (is_page)', 'ct-multi-sticky-sidebar' ),
							'posts'    => __( 'Single posts (is_single)', 'ct-multi-sticky-sidebar' ),
						);
						foreach ( $types as $k => $label ) :
							?>
							<label style="display:block;margin:0 0 6px"><input type="checkbox" name="lss[disableOn][<?php echo esc_attr( $k ); ?>]" value="1" <?php checked( ! empty( $s['disableOn'][ $k ] ) ); ?>> <?php echo esc_html( $label ); ?></label>
						<?php endforeach; ?>
					</td></tr>
			</table>

			<?php submit_button( __( 'Save and regenerate the JS file', 'ct-multi-sticky-sidebar' ) ); ?>
		</form>

		<hr>
		<h2><?php esc_html_e( 'Generated JS file', 'ct-multi-sticky-sidebar' ); ?></h2>
		<p>
			<?php if ( $exists ) : ?>
				<code><?php echo esc_html( $p['url'] ); ?></code><br>
				<?php
				/* translators: 1: file size, 2: date and time */
				printf( esc_html__( '%1$s, last generated %2$s', 'ct-multi-sticky-sidebar' ), esc_html( size_format( filesize( $p['file'] ), 1 ) ), esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), filemtime( $p['file'] ) ) ) );
				?>
			<?php else : ?>
				<strong><?php esc_html_e( 'No file yet.', 'ct-multi-sticky-sidebar' ); ?></strong> <?php esc_html_e( 'Click the button below to create it.', 'ct-multi-sticky-sidebar' ); ?>
			<?php endif; ?>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="lss_regenerate">
			<?php wp_nonce_field( 'lss_regenerate' ); ?>
			<?php submit_button( __( 'Regenerate the JS file', 'ct-multi-sticky-sidebar' ), 'secondary', 'submit', false ); ?>
		</form>

		<p class="description" style="max-width:800px;margin-top:1.5em">
			<?php echo wp_kses( __( 'The plugin does not load anything on mobile devices (detected with <code>wp_is_mobile()</code>, tablets included). If your site uses a page cache, enable separate caches for mobile and desktop; otherwise the mobile HTML may be served to desktop visitors (no sticky sidebar) or the other way round.', 'ct-multi-sticky-sidebar' ), array( 'code' => array() ) ); ?>
			<?php echo wp_kses( __( 'After changing the settings, clear your page cache so pages pick up the new <code>?ver=</code>.', 'ct-multi-sticky-sidebar' ), array( 'code' => array() ) ); ?>
		</p>
	</div>

	<template id="lss-row-tpl">
		<?php lss_group_row( '__I__', array( 'sidebar' => '', 'container' => '', 'marginTop' => '', 'marginBottom' => '' ) ); ?>
	</template>
	<script>
	(function () {
		var body = document.querySelector('#lss-groups tbody');
		var tpl = document.getElementById('lss-row-tpl');
		var next = body.querySelectorAll('.lss-row').length + 100;
		document.getElementById('lss-add').addEventListener('click', function () {
			var tr = tpl.content.firstElementChild.cloneNode(true);
			tr.querySelectorAll('input').forEach(function (i) { i.name = i.name.replace('__I__', next); });
			next++;
			body.appendChild(tr);
			tr.querySelector('input').focus();
		});
		body.addEventListener('click', function (e) {
			if (e.target.classList.contains('lss-remove')) e.target.closest('tr').remove();
		});
	})();
	</script>
	<?php
}
