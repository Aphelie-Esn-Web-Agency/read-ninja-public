<?php
/**
 * Plugin Name:       Read Ninja
 * Plugin URI:        https://read-ninja.com
 * Description:       Displays a reading progress bar on single posts.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Wilfrid BILLIOUW
 * Author URI:        https://read-ninja.com/about
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       read-ninja
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'READNINJA_VERSION', '1.1.0' );
define( 'READNINJA_PATH', plugin_dir_path( __FILE__ ) );
define( 'READNINJA_URL', plugin_dir_url( __FILE__ ) );
define( 'READNINJA_BASENAME', plugin_basename( __FILE__ ) );

if ( ! defined( 'READNINJA_PRO_URL' ) ) {
	define( 'READNINJA_PRO_URL', 'https://read-ninja.com/pro' );
}

/**
 * Whether the Pro version is active.
 *
 * The free plugin always returns false. A separate Pro plugin (distributed
 * outside WordPress.org) can hook into the `readninja_is_pro` filter to flip this
 * to true once its own license check passes. The `READNINJA_DEV_PRO` constant is
 * a developer-only override useful for local testing.
 */
function readninja_is_pro(): bool {
	if ( defined( 'READNINJA_DEV_PRO' ) && READNINJA_DEV_PRO ) {
		return true;
	}
	return (bool) apply_filters( 'readninja_is_pro', false );
}

// --- Core classes ------------------------------------------------------------
require_once READNINJA_PATH . 'includes/class-settings.php';
require_once READNINJA_PATH . 'includes/class-post-meta.php';
require_once READNINJA_PATH . 'includes/class-enqueue.php';

( new READNINJA_Settings() )->init();
( new READNINJA_Post_Meta() )->init();
( new READNINJA_Enqueue() )->init();

// --- Pro upsell (admin only, hidden when Pro is active) ----------------------
if ( is_admin() && ! readninja_is_pro() ) {
	require_once READNINJA_PATH . 'includes/class-readninja-upgrade.php';
	( new READNINJA_Upgrade() )->init();
}

// --- Lifecycle hooks ---------------------------------------------------------
register_activation_hook( __FILE__, function () {
	if ( ! get_option( 'readninja_activated_at' ) ) {
		update_option( 'readninja_activated_at', time() );
	}
} );
register_deactivation_hook( __FILE__, function () {} );
