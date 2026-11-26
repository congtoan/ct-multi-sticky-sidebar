<?php
defined( 'ABSPATH' ) || exit;

/**
 * Default settings for a fresh install.
 * One group that matches the sidebar of most themes: #secondary (WordPress default themes,
 * Astra, Kadence, OceanWP...) and #right-sidebar / #left-sidebar (GeneratePress).
 * An empty container means "the sidebar's parent element".
 */
function lss_defaults() {
	return array(
		'minWidth'                   => 901,
		'marginTop'                  => 20,
		'marginBottom'               => 20,
		'sidebarBehavior'            => 'modern',
		'updateSidebarHeight'        => false,
		'disableOnResponsiveLayouts' => true,
		// Widget areas (register_sidebar ids) to watch. Empty = always load the JS.
		'areas'                      => array(),
		// Never load the JS on these page types.
		'disableOn'                  => array(
			'home'     => false,
			'category' => false,
			'pages'    => false,
			'posts'    => false,
		),
		'groups'                     => array(
			array(
				'sidebar'      => '#secondary, #right-sidebar, #left-sidebar',
				'container'    => '',
				'marginTop'    => '',
				'marginBottom' => '',
			),
		),
	);
}

/** Allow only characters that can appear in a CSS selector; strip everything else (no code injection). */
function lss_sanitize_selector( $value ) {
	$value = wp_strip_all_tags( (string) $value );
	$value = preg_replace( '/[^A-Za-z0-9_\-\.\#\,\s\>\+\~\[\]\=\:\(\)\*\"\'\^\$\|]/', '', $value );
	$value = trim( preg_replace( '/\s+/', ' ', $value ) );
	return substr( $value, 0, 500 );
}

function lss_clamp_int( $value, $min, $max, $default ) {
	if ( ! is_numeric( $value ) ) {
		return $default;
	}
	return max( $min, min( $max, (int) $value ) );
}

/** Sanitize settings (used for both form input and data read back from the database). */
function lss_sanitize_settings( $in ) {
	$d   = lss_defaults();
	$in  = is_array( $in ) ? $in : array();
	$out = array(
		'minWidth'                   => lss_clamp_int( isset( $in['minWidth'] ) ? $in['minWidth'] : null, 0, 5000, $d['minWidth'] ),
		'marginTop'                  => lss_clamp_int( isset( $in['marginTop'] ) ? $in['marginTop'] : null, 0, 1000, $d['marginTop'] ),
		'marginBottom'               => lss_clamp_int( isset( $in['marginBottom'] ) ? $in['marginBottom'] : null, 0, 1000, $d['marginBottom'] ),
		'sidebarBehavior'            => ( isset( $in['sidebarBehavior'] ) && in_array( $in['sidebarBehavior'], array( 'modern', 'stick-to-top', 'stick-to-bottom' ), true ) ) ? $in['sidebarBehavior'] : 'modern',
		'updateSidebarHeight'        => ! empty( $in['updateSidebarHeight'] ),
		'disableOnResponsiveLayouts' => ! empty( $in['disableOnResponsiveLayouts'] ),
		'areas'                      => array(),
		'disableOn'                  => array(),
		'groups'                     => array(),
	);

	$areas = ( isset( $in['areas'] ) && is_array( $in['areas'] ) ) ? $in['areas'] : array();
	foreach ( array_slice( array_values( $areas ), 0, 50 ) as $a ) {
		$a = sanitize_key( (string) $a );
		if ( '' !== $a && ! in_array( $a, $out['areas'], true ) ) {
			$out['areas'][] = $a;
		}
	}

	$dis = ( isset( $in['disableOn'] ) && is_array( $in['disableOn'] ) ) ? $in['disableOn'] : array();
	foreach ( array_keys( $d['disableOn'] ) as $k ) {
		$out['disableOn'][ $k ] = ! empty( $dis[ $k ] );
	}

	$groups = ( isset( $in['groups'] ) && is_array( $in['groups'] ) ) ? array_values( $in['groups'] ) : array();
	foreach ( array_slice( $groups, 0, 20 ) as $g ) {
		if ( ! is_array( $g ) ) {
			continue;
		}
		$sidebar = lss_sanitize_selector( isset( $g['sidebar'] ) ? $g['sidebar'] : '' );
		if ( '' === $sidebar ) {
			continue;
		}
		$out['groups'][] = array(
			'sidebar'      => $sidebar,
			'container'    => lss_sanitize_selector( isset( $g['container'] ) ? $g['container'] : '' ),
			'marginTop'    => ( isset( $g['marginTop'] ) && '' !== trim( (string) $g['marginTop'] ) ) ? lss_clamp_int( $g['marginTop'], 0, 1000, '' ) : '',
			'marginBottom' => ( isset( $g['marginBottom'] ) && '' !== trim( (string) $g['marginBottom'] ) ) ? lss_clamp_int( $g['marginBottom'], 0, 1000, '' ) : '',
		);
	}
	return $out;
}

/**
 * Read the full settings from the database. ONLY called in wp-admin or while generating the
 * JS file; the frontend never calls this.
 * Stored as JSON with autoload = no. Arrays are accepted too (e.g. written with WP-CLI).
 */
function lss_get_settings() {
	$raw  = get_option( LSS_OPTION, '' );
	$data = is_array( $raw ) ? $raw : ( ( is_string( $raw ) && '' !== $raw ) ? json_decode( $raw, true ) : null );
	if ( ! is_array( $data ) ) {
		return lss_defaults();
	}
	// Settings saved by an older version may lack newer keys: fall back to the defaults for those.
	$data += lss_defaults();
	return lss_sanitize_settings( $data );
}

/**
 * The tiny part of the settings the frontend needs to decide whether to load the JS.
 * Stored separately in LSS_RUNTIME_OPTION with autoload = yes. WordPress loads every autoloaded
 * option in a single query on each request anyway (alloptions, served from the object cache
 * when there is one), so reading it on the frontend costs no extra query.
 */
function lss_runtime_from_settings( $s ) {
	return array(
		'areas'     => array_values( $s['areas'] ),
		'disableOn' => $s['disableOn'],
	);
}

function lss_get_runtime() {
	$r = get_option( LSS_RUNTIME_OPTION, null );
	if ( ! is_array( $r ) ) {
		// Settings never saved with 3.x yet: always load, exclude no page type (2.x behaviour).
		$r = array( 'areas' => array(), 'disableOn' => array() );
	}
	$r['areas']     = isset( $r['areas'] ) && is_array( $r['areas'] ) ? $r['areas'] : array();
	$r['disableOn'] = isset( $r['disableOn'] ) && is_array( $r['disableOn'] ) ? $r['disableOn'] : array();
	return $r;
}

function lss_save_settings( $clean ) {
	// Third argument false: not autoloaded.
	$ok = update_option( LSS_OPTION, wp_json_encode( $clean ), false );
	update_option( LSS_RUNTIME_OPTION, lss_runtime_from_settings( $clean ), true );
	return $ok;
}

/** JS file of the current site: uploads/(sites/N/)lite-sticky-sidebar/lss-{blog_id}.js */
function lss_paths() {
	$u    = wp_upload_dir( null, false ); // false: do not create the year/month folder
	$name = 'lss-' . get_current_blog_id() . '.js';
	$dir  = trailingslashit( $u['basedir'] ) . 'lite-sticky-sidebar';
	return array(
		'dir'  => $dir,
		'file' => $dir . '/' . $name,
		'url'  => set_url_scheme( trailingslashit( $u['baseurl'] ) . 'lite-sticky-sidebar/' . $name ),
	);
}

/**
 * Build the JS file = Theia v2 engine (plain JS) + init code with the settings baked in.
 * Written to a uniquely named temp file and then renamed, so visitors never get a half-written file.
 *
 * @return true|WP_Error
 */
function lss_generate( $settings = null ) {
	$settings = is_array( $settings ) ? $settings : lss_get_settings();
	$settings = apply_filters( 'lss_config', $settings, get_current_blog_id() );
	$p        = lss_paths();

	if ( ! wp_mkdir_p( $p['dir'] ) ) {
		/* translators: %s: directory path */
		return new WP_Error( 'mkdir', sprintf( __( 'Could not create the directory %s', 'ct-multi-sticky-sidebar' ), $p['dir'] ) );
	}
	if ( ! file_exists( $p['dir'] . '/index.php' ) ) {
		@file_put_contents( $p['dir'] . '/index.php', "<?php\n// Silence is golden.\n" );
	}

	$core = @file_get_contents( LSS_DIR . 'assets/theia-sticky-sidebar.js' );
	if ( false === $core ) {
		return new WP_Error( 'read', __( 'Could not read assets/theia-sticky-sidebar.js', 'ct-multi-sticky-sidebar' ) );
	}
	$init = @file_get_contents( LSS_DIR . 'assets/init.js' );
	if ( false === $init ) {
		return new WP_Error( 'read', __( 'Could not read assets/init.js', 'ct-multi-sticky-sidebar' ) );
	}
	$placeholder = '/*__LSS_CONFIG__*/{}';
	if ( false === strpos( $init, $placeholder ) ) {
		/* translators: %s: placeholder text */
		return new WP_Error( 'placeholder', sprintf( __( 'assets/init.js no longer contains the settings placeholder %s', 'ct-multi-sticky-sidebar' ), $placeholder ) );
	}
	// The frontend JS only needs the sticky settings, not areas/disableOn.
	$js_conf = $settings;
	unset( $js_conf['areas'], $js_conf['disableOn'] );
	$init = str_replace( $placeholder, wp_json_encode( $js_conf ), $init );

	$head = sprintf( "/*! CT Multi Sticky Sidebar %s | blog %d | generated %s UTC | Theia Sticky Sidebar v2.0.0 (MIT) (c) WeCodePixels */\n", LSS_VERSION, get_current_blog_id(), gmdate( 'Y-m-d H:i:s' ) );
	$out  = $head . $core . "\n;\n" . $init;

	$old_mtime = file_exists( $p['file'] ) ? filemtime( $p['file'] ) : 0;
	$old_hash  = $old_mtime ? md5_file( $p['file'] ) : '';

	$tmp = $p['file'] . '.' . wp_generate_password( 8, false, false ) . '.tmp';
	if ( false === @file_put_contents( $tmp, $out, LOCK_EX ) ) {
		return new WP_Error( 'write', __( 'Could not write the file (check the permissions of the uploads directory).', 'ct-multi-sticky-sidebar' ) );
	}
	if ( ! @rename( $tmp, $p['file'] ) ) {
		// Some systems (Windows) cannot rename over an existing file.
		if ( ! @copy( $tmp, $p['file'] ) ) {
			@unlink( $tmp );
			return new WP_Error( 'write', __( 'Could not write the target file.', 'ct-multi-sticky-sidebar' ) );
		}
		@unlink( $tmp );
	}

	// ?ver= is the file mtime (seconds). If the content changed within the same second, bump the
	// mtime by one second so browsers and CDNs do not keep the old copy.
	clearstatcache( true, $p['file'] );
	if ( $old_mtime && filemtime( $p['file'] ) <= $old_mtime && md5( $out ) !== $old_hash ) {
		@touch( $p['file'], $old_mtime + 1 );
		clearstatcache( true, $p['file'] );
	}

	delete_transient( 'lss_gen_failed' );
	return true;
}

/** Activation on one site: create the options (if missing) and generate the JS file. */
function lss_on_activate() {
	if ( false === get_option( LSS_OPTION, false ) ) {
		add_option( LSS_OPTION, wp_json_encode( lss_defaults() ), '', 'no' );
	}
	if ( false === get_option( LSS_RUNTIME_OPTION, false ) ) {
		add_option( LSS_RUNTIME_OPTION, lss_runtime_from_settings( lss_get_settings() ), '', 'yes' );
	}
	lss_generate();
}
