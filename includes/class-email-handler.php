<?php
/**
 * Email Handler Class
 *
 * Handles sending confirmation emails to participants with product-specific checks.
 *
 * @package Gym_Multi_Participant_Booking
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GMPB_Email_Handler Class.
 *
 * Manages email notifications for participants.
 * IMPORTANT: Only sends emails for products where participant booking is enabled.
 *
 * Email Deliverability Notes:
 * - Recommend using an SMTP plugin for better email delivery
 * - Suggested plugins: WP Mail SMTP, Post SMTP, Easy WP SMTP
 * - Shared hosting may have email sending limits
 * - Consider transactional email services: SendGrid, Mailgun, Amazon SES
 * - Test emails before going live
 *
 * @since 1.0.0
 */
class GMPB_Email_Handler {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init_hooks() {
		// Send emails when order is completed (priority 20 runs after WooCommerce default emails).
		add_action( 'woocommerce_order_status_completed', array( $this, 'send_participant_emails' ), 20, 1 );

		// Optional: Send emails when order is processing (useful for testing).
		add_action( 'woocommerce_order_status_processing', array( $this, 'send_participant_emails' ), 20, 1 );

		// Set email content type to HTML.
		add_filter( 'wp_mail_content_type', array( $this, 'set_email_content_type' ) );
	}

	/**
	 * Send confirmation emails to all participants in an order.
	 *
	 * @since 1.0.0
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function send_participant_emails( $order_id ) {
		// Check license validity first.
		if ( GMPB_License_Manager::is_license_expired() ) {
			return; // Don't send emails if license expired.
		}

		// Get order object.
		$order = wc_get_order( $order_id );

		// If order not found, return early.
		if ( ! $order ) {
			error_log( 'GMPB Email Error: Order not found - ID: ' . $order_id );
			return;
		}

		// Check if emails are enabled globally.
		if ( 'yes' !== get_option( 'gmpb_enable_emails', 'yes' ) ) {
			return;
		}

		// Check if emails already sent for this order.
		$emails_sent = $order->get_meta( '_gmpb_emails_sent', true );
		if ( 'yes' === $emails_sent ) {
			return;
		}

		// Fire action before sending emails.
		do_action( 'gmpb_before_send_participant_emails', $order_id );

		$sent_count = 0;
		$failed_count = 0;

		// Loop through order items.
		foreach ( $order->get_items() as $item_id => $item ) {
			// Get product ID.
			$product_id = $item->get_product_id();

			// IMPORTANT CHECK: Only send emails if participant booking is enabled for this product.
			if ( ! GMPB_Product_Settings::is_participant_booking_enabled( $product_id ) ) {
				continue;
			}

			// Get participants from order item meta.
			$participants = $item->get_meta( '_gmpb_participants', true );

			// If no participants or empty array, skip this item.
			if ( empty( $participants ) || ! is_array( $participants ) ) {
				continue;
			}

			// Loop through each participant.
			foreach ( $participants as $index => $participant ) {
				// Check if email already sent to this participant.
				if ( ! empty( $participant['email_sent'] ) ) {
					continue;
				}

				// Send email.
				$sent = $this->send_single_participant_email( $participant, $order, $item );

				if ( $sent ) {
					$sent_count++;

					// Update email_sent status.
					GMPB_Participant_Manager::update_email_sent_status( $item_id, $index, true );
				} else {
					$failed_count++;
					error_log( 'GMPB Email Error: Failed to send email to ' . $participant['email'] . ' for order #' . $order_id );
				}
			}
		}

		// Add order note if emails were sent.
		if ( $sent_count > 0 ) {
			$order->add_order_note(
				sprintf(
					/* translators: 1: number of emails sent, 2: number of emails failed */
					__( 'Participant confirmation emails sent: %1$d successful, %2$d failed.', 'gym-multi-participant-booking' ),
					$sent_count,
					$failed_count
				),
				false
			);

			// Mark emails as sent for this order.
			$order->update_meta_data( '_gmpb_emails_sent', 'yes' );
			$order->update_meta_data( '_gmpb_emails_sent_date', current_time( 'mysql' ) );
			$order->save();
		}

		// Fire action after sending emails.
		do_action( 'gmpb_after_send_participant_emails', $order_id, $sent_count );
	}

	/**
	 * Send confirmation email to a single participant.
	 *
	 * @since 1.0.0
	 * @param array                 $participant Participant data.
	 * @param WC_Order              $order       Order object.
	 * @param WC_Order_Item_Product $item        Order item.
	 * @return bool True on success, false on failure.
	 */
	public function send_single_participant_email( $participant, $order, $item ) {
		// Validate email.
		$to = isset( $participant['email'] ) ? $participant['email'] : '';

		if ( empty( $to ) || ! is_email( $to ) ) {
			error_log( 'GMPB Email Error: Invalid email address - ' . $to );
			return false;
		}

		// Apply filter to email recipient.
		$to = apply_filters( 'gmpb_email_to', $to, $participant );

		// Fire action before sending email.
		do_action( 'gmpb_before_send_single_email', $participant, $order, $item );

		// Prepare email components.
		$subject = $this->get_email_subject( $order, $item );
		$message = $this->get_email_content( $participant, $order, $item );
		$headers = $this->get_email_headers();

		// Send email.
		try {
			$sent = wp_mail( $to, $subject, $message, $headers );

			// Log activity.
			$this->log_email_activity( $order->get_id(), $to, $sent );

			// Fire action after sending email.
			do_action( 'gmpb_after_send_single_email', $participant, $order, $item, $sent );

			if ( ! $sent ) {
				// Fire action on email send failure.
				do_action( 'gmpb_email_send_failed', $participant, $order, $item, 'wp_mail returned false' );
			}

			return $sent;
		} catch ( Exception $e ) {
			error_log( 'GMPB Email Exception: ' . $e->getMessage() );
			do_action( 'gmpb_email_send_failed', $participant, $order, $item, $e->getMessage() );
			return false;
		}
	}

	/**
	 * Generate email subject.
	 *
	 * @since 1.0.0
	 * @param WC_Order              $order Order object.
	 * @param WC_Order_Item_Product $item  Order item.
	 * @return string Email subject.
	 */
	private function get_email_subject( $order, $item ) {
		// Get custom subject from settings or use default.
		$subject = $this->get_setting( 'email_subject', __( 'Your Booking Confirmation - {product_name} - Order #{order_number}', 'gym-multi-participant-booking' ) );

		// Prepare replacement variables.
		$replacements = array(
			'{product_name}'  => $item->get_name(),
			'{order_id}'      => $order->get_id(),
			'{order_number}'  => $order->get_order_number(),
			'{order_date}'    => $order->get_date_created()->format( 'F j, Y' ),
			'{gym_name}'      => $this->get_setting( 'gym_name', get_bloginfo( 'name' ) ),
		);

		// Replace placeholders.
		$subject = $this->format_email_template( $subject, $replacements );

		// Apply filter.
		$subject = apply_filters( 'gmpb_email_subject', $subject, $order, $item );

		return sanitize_text_field( $subject );
	}

	/**
	 * Generate email HTML content.
	 *
	 * @since 1.0.0
	 * @param array                 $participant Participant data.
	 * @param WC_Order              $order       Order object.
	 * @param WC_Order_Item_Product $item        Order item.
	 * @return string Email HTML content.
	 */
	private function get_email_content( $participant, $order, $item ) {
		// Prepare template variables.
		$template_vars = array(
			'participant_name'    => ! empty( $participant['name'] ) ? $participant['name'] : __( 'Valued Customer', 'gym-multi-participant-booking' ),
			'participant_email'   => $participant['email'],
			'order_id'            => $order->get_id(),
			'order_number'        => $order->get_order_number(),
			'order_date'          => $order->get_date_created()->format( 'F j, Y' ),
			'product_name'        => $item->get_name(),
			'product_quantity'    => $item->get_quantity(),
			'gym_name'            => $this->get_setting( 'gym_name', get_bloginfo( 'name' ) ),
			'gym_address'         => $this->get_setting( 'gym_address', '' ),
			'gym_contact_email'   => $this->get_setting( 'gym_contact_email', get_option( 'admin_email' ) ),
			'gym_contact_phone'   => $this->get_setting( 'gym_contact_phone', '' ),
			'booking_date'        => $this->get_booking_date( $item ),
			'booking_time'        => $this->get_booking_time( $item ),
			'additional_notes'    => $this->get_setting( 'email_additional_notes', '' ),
			'order'               => $order,
			'item'                => $item,
			'participant'         => $participant,
		);

		// Load template file using output buffering.
		$template_path = GMPB_PLUGIN_DIR . 'templates/email/participant-confirmation.php';

		if ( file_exists( $template_path ) ) {
			ob_start();
			extract( $template_vars, EXTR_SKIP );
			include $template_path;
			$message = ob_get_clean();
		} else {
			// Fallback to simple email if template not found.
			$message = $this->get_fallback_email_content( $template_vars );
			error_log( 'GMPB Email Warning: Email template not found at ' . $template_path );
		}

		// Apply filter.
		$message = apply_filters( 'gmpb_email_content', $message, $participant, $order, $item );

		return $message;
	}

	/**
	 * Get fallback email content (simple HTML).
	 *
	 * @since 1.0.0
	 * @param array $vars Template variables.
	 * @return string HTML email content.
	 */
	private function get_fallback_email_content( $vars ) {
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title><?php esc_html_e( 'Booking Confirmation', 'gym-multi-participant-booking' ); ?></title>
		</head>
		<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
			<div style="background-color: #f9f9f9; padding: 30px; border-radius: 8px;">
				<h2 style="color: #0073aa; margin-top: 0;">
					<?php esc_html_e( 'Booking Confirmation', 'gym-multi-participant-booking' ); ?>
				</h2>

				<p><?php printf( esc_html__( 'Dear %s,', 'gym-multi-participant-booking' ), esc_html( $vars['participant_name'] ) ); ?></p>

				<p><?php esc_html_e( 'Your gym booking has been confirmed! We are excited to see you.', 'gym-multi-participant-booking' ); ?></p>

				<div style="background-color: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #0073aa;">
					<h3 style="margin-top: 0; color: #0073aa;"><?php esc_html_e( 'Booking Details', 'gym-multi-participant-booking' ); ?></h3>
					<table style="width: 100%;">
						<tr>
							<td style="padding: 8px 0; font-weight: bold;"><?php esc_html_e( 'Order Number:', 'gym-multi-participant-booking' ); ?></td>
							<td style="padding: 8px 0;">#<?php echo esc_html( $vars['order_number'] ); ?></td>
						</tr>
						<tr>
							<td style="padding: 8px 0; font-weight: bold;"><?php esc_html_e( 'Product:', 'gym-multi-participant-booking' ); ?></td>
							<td style="padding: 8px 0;"><?php echo esc_html( $vars['product_name'] ); ?></td>
						</tr>
						<tr>
							<td style="padding: 8px 0; font-weight: bold;"><?php esc_html_e( 'Participant:', 'gym-multi-participant-booking' ); ?></td>
							<td style="padding: 8px 0;"><?php echo esc_html( $vars['participant_name'] ); ?></td>
						</tr>
						<tr>
							<td style="padding: 8px 0; font-weight: bold;"><?php esc_html_e( 'Order Date:', 'gym-multi-participant-booking' ); ?></td>
							<td style="padding: 8px 0;"><?php echo esc_html( $vars['order_date'] ); ?></td>
						</tr>
					</table>
				</div>

				<?php if ( ! empty( $vars['additional_notes'] ) ) : ?>
					<p><?php echo wp_kses_post( $vars['additional_notes'] ); ?></p>
				<?php endif; ?>

				<p style="margin-top: 30px;">
					<?php esc_html_e( 'Best regards,', 'gym-multi-participant-booking' ); ?><br>
					<strong><?php echo esc_html( $vars['gym_name'] ); ?></strong>
				</p>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get email headers.
	 *
	 * @since 1.0.0
	 * @return array Email headers.
	 */
	private function get_email_headers() {
		// Get from name and email from settings.
		$from_name  = $this->get_setting( 'email_from_name', get_bloginfo( 'name' ) );
		$from_email = $this->get_setting( 'email_from_email', get_option( 'admin_email' ) );

		// Validate from email.
		if ( ! is_email( $from_email ) ) {
			$from_email = get_option( 'admin_email' );
		}

		// Build headers array.
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $from_name . ' <' . $from_email . '>',
		);

		// Apply filter.
		$headers = apply_filters( 'gmpb_email_headers', $headers );

		return $headers;
	}

	/**
	 * Resend participant email manually.
	 *
	 * @since 1.0.0
	 * @param int $order_id          Order ID.
	 * @param int $item_id           Order item ID.
	 * @param int $participant_index Participant array index.
	 * @return array Array with 'success' boolean and 'message' string.
	 */
	public function resend_participant_email( $order_id, $item_id, $participant_index ) {
		// Check license validity first.
		if ( GMPB_License_Manager::is_license_expired() ) {
			return array(
				'success' => false,
				'message' => __( 'License has expired.', 'gym-multi-participant-booking' ),
			);
		}

		// Get order.
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return array(
				'success' => false,
				'message' => __( 'Order not found.', 'gym-multi-participant-booking' ),
			);
		}

		// Get order item.
		$item = $order->get_item( $item_id );
		if ( ! $item ) {
			return array(
				'success' => false,
				'message' => __( 'Order item not found.', 'gym-multi-participant-booking' ),
			);
		}

		// Get product ID and check if booking enabled.
		$product_id = $item->get_product_id();
		if ( ! GMPB_Product_Settings::is_participant_booking_enabled( $product_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'Participant booking not enabled for this product.', 'gym-multi-participant-booking' ),
			);
		}

		// Get participants array.
		$participants = $item->get_meta( '_gmpb_participants', true );

		if ( ! is_array( $participants ) || ! isset( $participants[ $participant_index ] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Participant not found.', 'gym-multi-participant-booking' ),
			);
		}

		// Get specific participant.
		$participant = $participants[ $participant_index ];

		// Send email.
		$sent = $this->send_single_participant_email( $participant, $order, $item );

		if ( $sent ) {
			// Update email_sent status.
			GMPB_Participant_Manager::update_email_sent_status( $item_id, $participant_index, true );

			// Add order note.
			$order->add_order_note(
				sprintf(
					/* translators: %s: participant email */
					__( 'Participant confirmation email resent to %s', 'gym-multi-participant-booking' ),
					$participant['email']
				),
				false
			);

			return array(
				'success' => true,
				'message' => __( 'Email sent successfully.', 'gym-multi-participant-booking' ),
			);
		} else {
			return array(
				'success' => false,
				'message' => __( 'Failed to send email.', 'gym-multi-participant-booking' ),
			);
		}
	}

	/**
	 * Get plugin setting.
	 *
	 * @since 1.0.0
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed Setting value.
	 */
	private function get_setting( $key, $default = '' ) {
		// Try to get from dedicated email settings option.
		$value = get_option( 'gmpb_' . $key, null );

		if ( null !== $value ) {
			return $value;
		}

		// Fallback to general settings.
		$settings = get_option( 'gmpb_settings', array() );

		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Get booking date from item meta (if available).
	 *
	 * @since 1.0.0
	 * @param WC_Order_Item_Product $item Order item.
	 * @return string Formatted booking date or empty string.
	 */
	private function get_booking_date( $item ) {
		// Try to get from item meta (if using WooCommerce Bookings or similar).
		$booking_date = $item->get_meta( '_booking_date', true );

		if ( ! empty( $booking_date ) ) {
			// Format date if it's a timestamp.
			if ( is_numeric( $booking_date ) ) {
				return date_i18n( get_option( 'date_format' ), $booking_date );
			}

			return $booking_date;
		}

		// Try to get from product meta.
		$product = $item->get_product();
		if ( $product ) {
			$product_date = get_post_meta( $product->get_id(), '_gmpb_booking_date', true );
			if ( ! empty( $product_date ) ) {
				return $product_date;
			}
		}

		return '';
	}

	/**
	 * Get booking time from item meta (if available).
	 *
	 * @since 1.0.0
	 * @param WC_Order_Item_Product $item Order item.
	 * @return string Formatted booking time or empty string.
	 */
	private function get_booking_time( $item ) {
		// Try to get from item meta.
		$booking_time = $item->get_meta( '_booking_time', true );

		if ( ! empty( $booking_time ) ) {
			return $booking_time;
		}

		// Try to get from product meta.
		$product = $item->get_product();
		if ( $product ) {
			$product_time = get_post_meta( $product->get_id(), '_gmpb_booking_time', true );
			if ( ! empty( $product_time ) ) {
				return $product_time;
			}
		}

		return '';
	}

	/**
	 * Log email activity.
	 *
	 * @since 1.0.0
	 * @param int    $order_id          Order ID.
	 * @param string $participant_email Participant email address.
	 * @param bool   $status            Email sent status.
	 * @return void
	 */
	private function log_email_activity( $order_id, $participant_email, $status ) {
		// Create log message.
		$message = sprintf(
			/* translators: 1: status text, 2: participant email */
			__( 'Participant email %1$s to %2$s', 'gym-multi-participant-booking' ),
			$status ? __( 'sent successfully', 'gym-multi-participant-booking' ) : __( 'failed', 'gym-multi-participant-booking' ),
			$participant_email
		);

		// Get order.
		$order = wc_get_order( $order_id );

		if ( $order ) {
			// Add order note (private note, not visible to customer).
			$order->add_order_note( $message, false );
		}

		// Log to WordPress debug log if enabled.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'GMPB Email Log [Order #' . $order_id . ']: ' . $message );
		}

		// Optional: Could add to custom database table for audit trail.
		do_action( 'gmpb_log_email_activity', $order_id, $participant_email, $status );
	}

	/**
	 * Format email template by replacing placeholders.
	 *
	 * @since 1.0.0
	 * @param string $template     Template string with {placeholders}.
	 * @param array  $replacements Associative array of replacements.
	 * @return string Formatted template.
	 */
	private function format_email_template( $template, $replacements ) {
		if ( empty( $template ) || ! is_array( $replacements ) ) {
			return $template;
		}

		// Replace each placeholder.
		foreach ( $replacements as $key => $value ) {
			$template = str_replace( $key, $value, $template );
		}

		return $template;
	}

	/**
	 * Set email content type to HTML.
	 *
	 * @since 1.0.0
	 * @param string $content_type Content type.
	 * @return string Modified content type.
	 */
	public function set_email_content_type( $content_type ) {
		// Only change content type for our emails.
		if ( doing_action( 'woocommerce_order_status_completed' ) || doing_action( 'woocommerce_order_status_processing' ) ) {
			return 'text/html';
		}

		return $content_type;
	}
}
