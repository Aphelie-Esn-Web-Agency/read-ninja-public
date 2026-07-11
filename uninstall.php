<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'readninja_settings' );
delete_option( 'readninja_pro_settings' );
delete_option( 'readninja_pro_threshold' );
delete_option( 'readninja_pro_sequential' );
delete_option( 'readninja_pro_gradient' );
delete_option( 'readninja_pro_percent_display' ); // legacy, in case Pro was previously installed

global $wpdb;
// Table name is built from $wpdb->prefix and a hardcoded string — no user input.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( 'DROP TABLE IF EXISTS `' . $wpdb->prefix . 'readninja_analytics`' );
