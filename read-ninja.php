<?php
/**
 * Plugin Name:       Read Ninja
 * Plugin URI:        https://read-ninja.com
 * Description:       Displays a reading progress bar on single posts.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Wilfrid BILLIOUW
 * Author URI:        https://read-ninja.com
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       read-ninja
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RPB_VERSION', '1.1.0' );
define( 'RPB_PATH', plugin_dir_path( __FILE__ ) );
define( 'RPB_URL', plugin_dir_url( __FILE__ ) );
define( 'RPB_BASENAME', plugin_basename( __FILE__ ) );

if ( ! defined( 'RPB_PRO_URL' ) ) {
	define( 'RPB_PRO_URL', 'https://read-ninja.com/pro' );
}

/**
 * Whether the Pro version is active.
 *
 * The free plugin always returns false. A separate Pro plugin (distributed
 * outside WordPress.org) can hook into the `rpb_is_pro` filter to flip this
 * to true once its own license check passes. The `RPB_DEV_PRO` constant is
 * a developer-only override useful for local testing.
 */
function rpb_is_pro(): bool {
	if ( defined( 'RPB_DEV_PRO' ) && RPB_DEV_PRO ) {
		return true;
	}
	return (bool) apply_filters( 'rpb_is_pro', false );
}

// --- Core classes ------------------------------------------------------------
require_once RPB_PATH . 'includes/class-settings.php';
require_once RPB_PATH . 'includes/class-post-meta.php';
require_once RPB_PATH . 'includes/class-enqueue.php';

( new RPB_Settings() )->init();
( new RPB_Post_Meta() )->init();
( new RPB_Enqueue() )->init();

// --- Pro upsell (admin only, hidden when Pro is active) ----------------------
if ( is_admin() && ! rpb_is_pro() ) {
	require_once RPB_PATH . 'includes/class-rpb-upgrade.php';
	( new RPB_Upgrade() )->init();
}

// --- Lifecycle hooks ---------------------------------------------------------
register_activation_hook( __FILE__, function () {
	if ( ! get_option( 'rpb_activated_at' ) ) {
		update_option( 'rpb_activated_at', time() );
	}
} );
register_deactivation_hook( __FILE__, function () {} );
