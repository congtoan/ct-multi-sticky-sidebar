<?php
defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'lss_enqueue', 20 );

define( 'LSS_HANDLE', 'ct-multi-sticky-sidebar' );

/** Page types excluded in the settings ("Do not load on"). */
function lss_is_disabled_page( $disable_on ) {
	return ( ! empty( $disable_on['home'] ) && ( is_home() || is_front_page() ) )
		|| ( ! empty( $disable_on['category'] ) && is_category() )
		|| ( ! empty( $disable_on['pages'] ) && is_page() )
		|| ( ! empty( $disable_on['posts'] ) && is_single() );
}

function lss_enqueue() {
	if ( is_admin() || is_feed() || is_embed() || is_customize_preview() ) {
		return;
	}

	// Mobile: skip entirely, not a single byte is sent. Change with the filter (e.g. to allow tablets).
	if ( apply_filters( 'lss_is_mobile', wp_is_mobile() ) ) {
		return;
	}

	// Only the tiny autoloaded option (already in memory), never the full settings.
	$rt = lss_get_runtime();
	if ( lss_is_disabled_page( $rt['disableOn'] ) ) {
		return;
	}

	$p = lss_paths();

	// Regenerate only when the file is missing (site cloned, uploads cleaned...).
	if ( ! file_exists( $p['file'] ) ) {
		if ( get_transient( 'lss_gen_failed' ) ) {
			return;
		}
		if ( is_wp_error( lss_generate() ) ) {
			set_transient( 'lss_gen_failed', 1, 10 * MINUTE_IN_SECONDS );
			return;
		}
	}

	wp_register_script(
		LSS_HANDLE,
		$p['url'],
		array(), // plain JavaScript, no jQuery
		(string) filemtime( $p['file'] ), // file changes => ?ver= changes => cache busting
		array( 'in_footer' => true )
	);

	$areas = apply_filters( 'lss_watch_areas', $rt['areas'] );
	if ( empty( $areas ) ) {
		// No widget area selected: always load (2.x behaviour).
		wp_enqueue_script( LSS_HANDLE );
		return;
	}

	// Load only when the template actually renders a watched widget area that has widgets.
	// The <script> tag is still printed in the footer (wp_footer), because this decision is made
	// while the page is being rendered.
	$GLOBALS['lss_watch_areas'] = array_map( 'strval', (array) $areas );
	add_action( 'dynamic_sidebar_before', 'lss_on_dynamic_sidebar', 10, 2 );
}

function lss_on_dynamic_sidebar( $index, $has_widgets = true ) {
	if ( ! $has_widgets || empty( $GLOBALS['lss_watch_areas'] ) ) {
		return;
	}
	if ( in_array( (string) $index, $GLOBALS['lss_watch_areas'], true ) ) {
		wp_enqueue_script( LSS_HANDLE );
		remove_action( 'dynamic_sidebar_before', 'lss_on_dynamic_sidebar', 10 );
	}
}
