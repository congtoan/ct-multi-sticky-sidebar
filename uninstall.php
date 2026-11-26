<?php
// Runs when the plugin is deleted: remove the options and the generated JS file of every site.
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

function lss_uninstall_current_site() {
	delete_option( 'lss_settings' );
	delete_option( 'lss_runtime' );
	delete_transient( 'lss_gen_failed' );
	$u   = wp_upload_dir( null, false );
	$dir = trailingslashit( $u['basedir'] ) . 'lite-sticky-sidebar';
	if ( is_dir( $dir ) ) {
		foreach ( (array) glob( $dir . '/*' ) as $f ) {
			if ( is_file( $f ) ) { // includes leftover .tmp files
				@unlink( $f );
			}
		}
		@rmdir( $dir );
	}
}

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $blog_id ) {
		switch_to_blog( $blog_id );
		lss_uninstall_current_site();
		restore_current_blog();
	}
} else {
	lss_uninstall_current_site();
}
