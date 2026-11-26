<?php
/**
 * Plugin Name:       CT Multi Sticky Sidebar
 * Plugin URI:        https://github.com/congtoan/ct-multi-sticky-sidebar
 * Description:       Lightweight sticky sidebars for any number of sidebars per page. No jQuery, one static JS file per site, zero extra database queries on the frontend, nothing loaded on mobile or on pages without a sidebar. Powered by the Theia Sticky Sidebar v2 engine (MIT).
 * Version:           3.1.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            Cong Toan
 * Author URI:        https://github.com/congtoan
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ct-multi-sticky-sidebar
 * Domain Path:       /languages
 * Update URI:        https://github.com/congtoan/ct-multi-sticky-sidebar
 *
 * Activate per site on Multisite (no Network Activate): every site has its own settings and JS file.
 */

defined( 'ABSPATH' ) || exit;

define( 'LSS_VERSION', '3.1.0' );
define( 'LSS_DIR', plugin_dir_path( __FILE__ ) );
// Internal prefix "lss_" (from the plugin's first name, "Lite Sticky Sidebar") is kept for
// backward compatibility with saved settings and generated files.
define( 'LSS_OPTION', 'lss_settings' );          // full settings, autoload = no (admin only)
define( 'LSS_RUNTIME_OPTION', 'lss_runtime' );   // tiny frontend subset (areas + disableOn), autoload = yes

require_once LSS_DIR . 'includes/functions.php';
require_once LSS_DIR . 'includes/frontend.php';

if ( is_admin() ) {
	require_once LSS_DIR . 'includes/admin.php';
}

add_action( 'init', 'lss_load_textdomain' );
function lss_load_textdomain() {
	load_plugin_textdomain( 'ct-multi-sticky-sidebar', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

register_activation_hook( __FILE__, 'lss_on_activate' );
