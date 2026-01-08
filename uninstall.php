<?php
/**
 * Plugin Uninstall Handler
 *
 * Cleans up plugin data when the plugin is deleted from WordPress.
 * This file is called automatically by WordPress when user deletes the plugin.
 *
 * IMPORTANT: By default, this file DOES NOT delete participant order data
 * to comply with legal/business record-keeping requirements. Users can enable
 * data deletion in plugin settings if desired.
 *
 * @package Gym_Multi_Participant_Booking
 * @since 1.0.0
 */

// If uninstall not called from WordPress, exit immediately
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete plugin options and settings
 *
 * Removes all plugin configuration data from wp_options table.
 *
 * @since 1.0.0
 */
function gmpb_delete_options() {
	// Delete main settings
	delete_option( 'gmpb_settings' );
	delete_option( 'gmpb_version' );
	delete_option( 'gmpb_db_version' );
	delete_option( 'gmpb_activation_date' );

	// Delete data retention setting
	delete_option( 'gmpb_delete_data_on_uninstall' );

	// Delete any cached settings
	delete_option( 'gmpb_cache_enabled_products' );

	// Delete any transients
	delete_transient( 'gmpb_enabled_products' );
	delete_transient( 'gmpb_admin_notices' );
	delete_transient( 'gmpb_email_queue' );

	// Delete site transients (for multisite)
	delete_site_transient( 'gmpb_enabled_products' );

	// Log deletion if debug enabled
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'GMPB: Plugin options deleted' );
	}
}

/**
 * Delete product meta data
 *
 * Removes participant booking settings from all products.
 * This is safe to delete as it only removes the "enable participant booking" flag.
 *
 * @since 1.0.0
 */
function gmpb_delete_product_meta() {
	global $wpdb;

	// Delete all participant booking enable/disable meta
	$deleted = $wpdb->query(
		"DELETE FROM {$wpdb->postmeta}
		 WHERE meta_key = '_enable_participant_booking'"
	);

	// Also delete any cached product data
	$wpdb->query(
		"DELETE FROM {$wpdb->postmeta}
		 WHERE meta_key LIKE '_gmpb_%'"
	);

	// Log deletion if debug enabled
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( "GMPB: Deleted {$deleted} product meta entries" );
	}
}

/**
 * Delete order meta data (participant information)
 *
 * CRITICAL: This function deletes customer participant data from orders.
 * By default, this is NOT executed to comply with:
 * - Legal record-keeping requirements
 * - Business documentation needs
 * - Customer service requirements
 * - Dispute resolution capabilities
 * - GDPR lawful basis for processing
 *
 * This will ONLY run if explicitly enabled in plugin settings.
 *
 * @since 1.0.0
 */
function gmpb_delete_order_meta() {
	global $wpdb;

	// Get the user's preference from settings
	$delete_order_data = get_option( 'gmpb_delete_data_on_uninstall', false );

	// Only proceed if explicitly enabled
	if ( $delete_order_data ) {
		// Delete participant data from order items
		$deleted = $wpdb->query(
			"DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta
			 WHERE meta_key = '_gmpb_participants'"
		);

		// Delete email sent status
		$wpdb->query(
			"DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta
			 WHERE meta_key = '_gmpb_email_sent'"
		);

		// Delete email sent date
		$wpdb->query(
			"DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta
			 WHERE meta_key = '_gmpb_email_sent_date'"
		);

		// Log deletion if debug enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "GMPB: Deleted {$deleted} order meta entries (user opted in to data deletion)" );
		}
	} else {
		// Log that data was preserved
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'GMPB: Order participant data preserved (data retention enabled)' );
		}
	}
}

/**
 * Delete custom database tables (if any)
 *
 * Removes any custom tables created by the plugin.
 * Currently, this plugin doesn't create custom tables, but this function
 * is here for future extensibility.
 *
 * @since 1.0.0
 */
function gmpb_delete_custom_tables() {
	global $wpdb;

	// Example: Email log table (if implemented in future)
	// $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}gmpb_email_log" );

	// Example: Participant history table (if implemented in future)
	// $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}gmpb_participant_history" );

	// Log if debug enabled
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'GMPB: No custom tables to delete' );
	}
}

/**
 * Remove scheduled cron jobs
 *
 * Clears all scheduled events/cron jobs registered by the plugin.
 *
 * @since 1.0.0
 */
function gmpb_clear_scheduled_events() {
	// Clear any scheduled hooks
	wp_clear_scheduled_hook( 'gmpb_daily_cleanup' );
	wp_clear_scheduled_hook( 'gmpb_send_reminder_emails' );
	wp_clear_scheduled_hook( 'gmpb_weekly_report' );
	wp_clear_scheduled_hook( 'gmpb_monthly_cleanup' );

	// Log if debug enabled
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'GMPB: Cleared all scheduled events' );
	}
}

/**
 * Clear any cached data
 *
 * Flushes all plugin-related caches and transients.
 *
 * @since 1.0.0
 */
function gmpb_clear_caches() {
	// Delete specific cache keys
	wp_cache_delete( 'gmpb_enabled_products', 'gmpb' );
	wp_cache_delete( 'gmpb_settings', 'gmpb' );
	wp_cache_delete( 'gmpb_product_list', 'gmpb' );

	// Clear object cache group
	wp_cache_flush_group( 'gmpb' );

	// Flush entire cache (optional - commented out to avoid affecting other plugins)
	// wp_cache_flush();

	// Log if debug enabled
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'GMPB: Cleared all caches' );
	}
}

/**
 * Delete user meta data
 *
 * Removes any plugin-specific user meta fields.
 *
 * @since 1.0.0
 */
function gmpb_delete_user_meta() {
	global $wpdb;

	// Delete user preferences (e.g., dismissed notices)
	$wpdb->query(
		"DELETE FROM {$wpdb->usermeta}
		 WHERE meta_key LIKE 'gmpb_%'"
	);

	// Log if debug enabled
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'GMPB: Deleted user meta data' );
	}
}

/**
 * Clean up uploaded files (if any)
 *
 * Removes any files uploaded or created by the plugin.
 *
 * @since 1.0.0
 */
function gmpb_delete_uploaded_files() {
	$upload_dir = wp_upload_dir();
	$plugin_upload_dir = $upload_dir['basedir'] . '/gmpb';

	// Only delete if directory exists
	if ( is_dir( $plugin_upload_dir ) ) {
		// Recursively delete directory and contents
		gmpb_recursive_delete_directory( $plugin_upload_dir );

		// Log if debug enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'GMPB: Deleted upload directory' );
		}
	}
}

/**
 * Recursively delete directory and all its contents
 *
 * @since 1.0.0
 * @param string $dir Directory path to delete.
 * @return bool True on success, false on failure.
 */
function gmpb_recursive_delete_directory( $dir ) {
	if ( ! is_dir( $dir ) ) {
		return false;
	}

	$files = array_diff( scandir( $dir ), array( '.', '..' ) );

	foreach ( $files as $file ) {
		$path = $dir . '/' . $file;

		if ( is_dir( $path ) ) {
			gmpb_recursive_delete_directory( $path );
		} else {
			unlink( $path );
		}
	}

	return rmdir( $dir );
}

/**
 * Main uninstall function for single site
 *
 * Executes all cleanup functions in the correct order.
 *
 * @since 1.0.0
 */
function gmpb_run_uninstall() {
	// Delete options and settings
	gmpb_delete_options();

	// Delete product meta (safe - just removes enable flags)
	gmpb_delete_product_meta();

	// Delete order meta (ONLY if user opted in - preserves data by default)
	gmpb_delete_order_meta();

	// Delete user meta
	gmpb_delete_user_meta();

	// Delete custom tables (if any)
	gmpb_delete_custom_tables();

	// Clear scheduled events
	gmpb_clear_scheduled_events();

	// Clear caches
	gmpb_clear_caches();

	// Delete uploaded files
	gmpb_delete_uploaded_files();

	// Log completion if debug enabled
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'GMPB: Uninstall completed for site ID ' . get_current_blog_id() );
	}
}

/**
 * Multisite: Clean up for all sites
 *
 * If this is a multisite installation, clean up data for all sites.
 * If single site, just run the normal cleanup.
 *
 * @since 1.0.0
 */
function gmpb_multisite_uninstall() {
	global $wpdb;

	if ( is_multisite() ) {
		// Get all blog IDs
		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
		$original_blog_id = get_current_blog_id();

		// Loop through each site
		foreach ( $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id );
			gmpb_run_uninstall();
		}

		// Switch back to original blog
		switch_to_blog( $original_blog_id );

		// Delete network-wide options (if any)
		delete_site_option( 'gmpb_network_settings' );
		delete_site_option( 'gmpb_network_version' );

		// Log if debug enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'GMPB: Multisite uninstall completed for ' . count( $blog_ids ) . ' sites' );
		}
	} else {
		// Single site installation
		gmpb_run_uninstall();
	}
}

/**
 * Check if we should proceed with uninstall
 *
 * Additional safety check to ensure we're really uninstalling.
 *
 * @since 1.0.0
 * @return bool True if safe to proceed, false otherwise.
 */
function gmpb_should_uninstall() {
	// Double-check the uninstall constant
	if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
		return false;
	}

	// Verify WooCommerce exists (to avoid database errors)
	if ( ! function_exists( 'WC' ) ) {
		// WooCommerce not active, but still safe to clean up options
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'GMPB: WooCommerce not active during uninstall, skipping order cleanup' );
		}
	}

	return true;
}

// =============================================================================
// EXECUTE UNINSTALL
// =============================================================================

// Safety check before proceeding
if ( ! gmpb_should_uninstall() ) {
	exit;
}

// Log uninstall start
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	error_log( '========================================' );
	error_log( 'GMPB: Uninstall started at ' . current_time( 'mysql' ) );
	error_log( 'GMPB: WordPress version: ' . get_bloginfo( 'version' ) );
	error_log( 'GMPB: PHP version: ' . phpversion() );
	error_log( 'GMPB: Multisite: ' . ( is_multisite() ? 'Yes' : 'No' ) );
	error_log( '========================================' );
}

// Execute the uninstall process
gmpb_multisite_uninstall();

// Log uninstall completion
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	error_log( '========================================' );
	error_log( 'GMPB: Gym Multi-Participant Booking plugin uninstalled successfully' );
	error_log( 'GMPB: Uninstall completed at ' . current_time( 'mysql' ) );
	error_log( '========================================' );
}

// Clear any remaining references
unset( $wpdb, $blog_ids, $original_blog_id, $deleted, $upload_dir, $plugin_upload_dir );
