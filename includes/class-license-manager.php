<?php
/**
 * License Manager
 *
 * Handles time-based licensing for the plugin.
 *
 * @package Gym_Multi_Participant_Booking
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * License Manager Class
 *
 * Simple time-based license validation without UI.
 * Plugin expires on January 31, 2026.
 *
 * @since 1.0.0
 */
class GMPB_License_Manager {

	/**
	 * License expiration date (Y-m-d format).
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const EXPIRATION_DATE = '2026-01-31';

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 * @var GMPB_License_Manager
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @since 1.0.0
	 * @return GMPB_License_Manager
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		// Private constructor for singleton pattern.
	}

	/**
	 * Check if license is valid (not expired).
	 *
	 * @since 1.0.0
	 * @return bool True if license is valid, false if expired.
	 */
	public static function is_license_valid() {
		// Get current date in UTC timezone.
		$current_date = current_time( 'Y-m-d', true );

		// Compare with expiration date.
		$is_valid = strtotime( $current_date ) <= strtotime( self::EXPIRATION_DATE );

		/**
		 * Filter license validity status.
		 *
		 * Allows developers to override license check for testing.
		 *
		 * @since 1.0.0
		 * @param bool $is_valid Whether license is valid.
		 */
		return apply_filters( 'gmpb_license_valid', $is_valid );
	}

	/**
	 * Check if license has expired.
	 *
	 * @since 1.0.0
	 * @return bool True if license expired, false if still valid.
	 */
	public static function is_license_expired() {
		return ! self::is_license_valid();
	}

	/**
	 * Get license expiration date.
	 *
	 * @since 1.0.0
	 * @param string $format Date format (default: 'F j, Y').
	 * @return string Formatted expiration date.
	 */
	public static function get_expiration_date( $format = 'F j, Y' ) {
		return date_i18n( $format, strtotime( self::EXPIRATION_DATE ) );
	}

	/**
	 * Get days remaining until expiration.
	 *
	 * @since 1.0.0
	 * @return int Days remaining (negative if expired).
	 */
	public static function get_days_remaining() {
		$current_date = current_time( 'Y-m-d', true );
		$current_time = strtotime( $current_date );
		$expiration_time = strtotime( self::EXPIRATION_DATE );

		$diff = $expiration_time - $current_time;
		return (int) floor( $diff / DAY_IN_SECONDS );
	}

	/**
	 * Display admin notice if license is expired or expiring soon.
	 *
	 * @since 1.0.0
	 */
	public static function maybe_show_expiration_notice() {
		// Only show to administrators.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$days_remaining = self::get_days_remaining();

		// Show error if expired.
		if ( self::is_license_expired() ) {
			?>
			<div class="notice notice-error">
				<p>
					<strong><?php esc_html_e( 'Gym Multi-Participant Booking:', 'gym-multi-participant-booking' ); ?></strong>
					<?php
					printf(
						/* translators: %s: Expiration date */
						esc_html__( 'Your license expired on %s. The plugin has been disabled.', 'gym-multi-participant-booking' ),
						esc_html( self::get_expiration_date() )
					);
					?>
				</p>
			</div>
			<?php
			return;
		}

		// Show warning if expiring within 7 days.
		if ( $days_remaining <= 7 && $days_remaining > 0 ) {
			?>
			<div class="notice notice-warning">
				<p>
					<strong><?php esc_html_e( 'Gym Multi-Participant Booking:', 'gym-multi-participant-booking' ); ?></strong>
					<?php
					printf(
						/* translators: 1: Days remaining, 2: Expiration date */
						esc_html( _n(
							'Your license expires in %1$d day (%2$s).',
							'Your license expires in %1$d days (%2$s).',
							$days_remaining,
							'gym-multi-participant-booking'
						) ),
						absint( $days_remaining ),
						esc_html( self::get_expiration_date() )
					);
					?>
				</p>
			</div>
			<?php
		}
	}
}
