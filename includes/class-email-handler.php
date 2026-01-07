<?php
/**
 * Email Handler Class
 *
 * Handles sending confirmation emails to participants.
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
		// Send emails when order is completed or processing.
		add_action( 'woocommerce_order_status_completed', array( $this, 'send_participant_emails' ) );
		add_action( 'woocommerce_order_status_processing', array( $this, 'send_participant_emails' ) );

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
		// Check if emails are enabled.
		if ( 'yes' !== get_option( 'gmpb_enable_emails', 'yes' ) ) {
			return;
		}

		// Check if emails already sent.
		$emails_sent = get_post_meta( $order_id, '_gmpb_emails_sent', true );
		if ( 'yes' === $emails_sent ) {
			return;
		}

		// Get order.
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		// Loop through order items.
		foreach ( $order->get_items() as $item_id => $item ) {
			$participants = $item->get_meta( '_gmpb_participants' );

			if ( empty( $participants ) || ! is_array( $participants ) ) {
				continue;
			}

			// Send email to each participant.
			foreach ( $participants as $participant ) {
				$this->send_confirmation_email( $participant, $item, $order );
			}
		}

		// Mark emails as sent.
		update_post_meta( $order_id, '_gmpb_emails_sent', 'yes' );
		update_post_meta( $order_id, '_gmpb_emails_sent_date', current_time( 'mysql' ) );
	}

	/**
	 * Send confirmation email to a single participant.
	 *
	 * @since 1.0.0
	 * @param array                 $participant Participant data.
	 * @param WC_Order_Item_Product $item        Order item.
	 * @param WC_Order              $order       Order object.
	 * @return bool
	 */
	private function send_confirmation_email( $participant, $item, $order ) {
		$email = $participant['email'];

		if ( ! is_email( $email ) ) {
			return false;
		}

		// Get product.
		$product = $item->get_product();

		if ( ! $product ) {
			return false;
		}

		// Prepare email data.
		$from_name    = get_option( 'gmpb_email_from_name', get_bloginfo( 'name' ) );
		$from_address = get_option( 'gmpb_email_from_address', get_option( 'admin_email' ) );

		$to      = $email;
		$subject = sprintf(
			/* translators: 1: site name, 2: product name */
			__( '[%1$s] Booking Confirmation - %2$s', 'gym-multi-participant-booking' ),
			$from_name,
			$product->get_name()
		);

		// Get email template.
		$message = $this->get_email_template( $participant, $item, $order, $product );

		// Set headers.
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $from_name . ' <' . $from_address . '>',
		);

		// Send email.
		$sent = wp_mail( $to, $subject, $message, $headers );

		// Log result.
		do_action( 'gmpb_email_sent', $sent, $email, $order->get_id(), $item->get_id() );

		return $sent;
	}

	/**
	 * Get email template HTML.
	 *
	 * @since 1.0.0
	 * @param array                 $participant Participant data.
	 * @param WC_Order_Item_Product $item        Order item.
	 * @param WC_Order              $order       Order object.
	 * @param WC_Product            $product     Product object.
	 * @return string
	 */
	private function get_email_template( $participant, $item, $order, $product ) {
		$name  = ! empty( $participant['name'] ) ? $participant['name'] : __( 'Valued Customer', 'gym-multi-participant-booking' );
		$email = $participant['email'];

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title><?php esc_html_e( 'Booking Confirmation', 'gym-multi-participant-booking' ); ?></title>
		</head>
		<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f7f7f7;">
			<div style="background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
				<!-- Header -->
				<div style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #0073aa;">
					<h1 style="color: #0073aa; margin: 0; font-size: 28px;">
						<?php esc_html_e( 'Booking Confirmed!', 'gym-multi-participant-booking' ); ?>
					</h1>
				</div>

				<!-- Greeting -->
				<p style="font-size: 16px; margin-bottom: 20px;">
					<?php
					printf(
						/* translators: %s: participant name */
						esc_html__( 'Dear %s,', 'gym-multi-participant-booking' ),
						'<strong>' . esc_html( $name ) . '</strong>'
					);
					?>
				</p>

				<p style="font-size: 16px; margin-bottom: 25px;">
					<?php esc_html_e( 'Your gym booking has been confirmed! We are excited to see you.', 'gym-multi-participant-booking' ); ?>
				</p>

				<!-- Booking Details -->
				<div style="background-color: #f9f9f9; padding: 20px; border-left: 4px solid #0073aa; margin: 25px 0; border-radius: 4px;">
					<h2 style="color: #0073aa; margin-top: 0; font-size: 20px;">
						<?php esc_html_e( 'Booking Details', 'gym-multi-participant-booking' ); ?>
					</h2>

					<table style="width: 100%; border-collapse: collapse;">
						<tr>
							<td style="padding: 10px 0; font-weight: bold; width: 40%; color: #555;">
								<?php esc_html_e( 'Order Number:', 'gym-multi-participant-booking' ); ?>
							</td>
							<td style="padding: 10px 0; color: #333;">
								#<?php echo esc_html( $order->get_order_number() ); ?>
							</td>
						</tr>
						<tr>
							<td style="padding: 10px 0; font-weight: bold; color: #555;">
								<?php esc_html_e( 'Product:', 'gym-multi-participant-booking' ); ?>
							</td>
							<td style="padding: 10px 0; color: #333;">
								<?php echo esc_html( $product->get_name() ); ?>
							</td>
						</tr>
						<tr>
							<td style="padding: 10px 0; font-weight: bold; color: #555;">
								<?php esc_html_e( 'Participant:', 'gym-multi-participant-booking' ); ?>
							</td>
							<td style="padding: 10px 0; color: #333;">
								<?php echo esc_html( $name ); ?>
							</td>
						</tr>
						<tr>
							<td style="padding: 10px 0; font-weight: bold; color: #555;">
								<?php esc_html_e( 'Email:', 'gym-multi-participant-booking' ); ?>
							</td>
							<td style="padding: 10px 0; color: #333;">
								<?php echo esc_html( $email ); ?>
							</td>
						</tr>
						<tr>
							<td style="padding: 10px 0; font-weight: bold; color: #555;">
								<?php esc_html_e( 'Order Date:', 'gym-multi-participant-booking' ); ?>
							</td>
							<td style="padding: 10px 0; color: #333;">
								<?php echo esc_html( $order->get_date_created()->date_i18n( wc_date_format() ) ); ?>
							</td>
						</tr>
					</table>
				</div>

				<!-- Instructions -->
				<div style="background-color: #fff8e5; padding: 15px; border-radius: 4px; margin: 25px 0; border-left: 4px solid #ffb900;">
					<p style="margin: 0; color: #856404; font-size: 14px;">
						<strong><?php esc_html_e( 'Important:', 'gym-multi-participant-booking' ); ?></strong>
						<?php esc_html_e( 'Please bring this confirmation email with you or note your order number.', 'gym-multi-participant-booking' ); ?>
					</p>
				</div>

				<!-- Additional Info -->
				<p style="font-size: 15px; margin-top: 25px; color: #666;">
					<?php esc_html_e( 'If you have any questions or need to make changes to your booking, please don\'t hesitate to contact us.', 'gym-multi-participant-booking' ); ?>
				</p>

				<!-- View Order Button -->
				<div style="text-align: center; margin: 30px 0;">
					<a href="<?php echo esc_url( $order->get_view_order_url() ); ?>"
					   style="display: inline-block; padding: 12px 30px; background-color: #0073aa; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 16px;">
						<?php esc_html_e( 'View Order Details', 'gym-multi-participant-booking' ); ?>
					</a>
				</div>

				<!-- Footer -->
				<div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center;">
					<p style="margin: 10px 0; color: #666; font-size: 14px;">
						<?php esc_html_e( 'Thank you for choosing us!', 'gym-multi-participant-booking' ); ?>
					</p>
					<p style="margin: 5px 0; color: #999; font-size: 13px;">
						<strong><?php echo esc_html( get_bloginfo( 'name' ) ); ?></strong>
					</p>
					<p style="margin: 5px 0; color: #999; font-size: 12px;">
						<?php
						printf(
							/* translators: %s: site URL */
							esc_html__( 'This email was sent from %s', 'gym-multi-participant-booking' ),
							'<a href="' . esc_url( home_url() ) . '" style="color: #0073aa;">' . esc_html( home_url() ) . '</a>'
						);
						?>
					</p>
				</div>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Set email content type to HTML.
	 *
	 * @since 1.0.0
	 * @param string $content_type Content type.
	 * @return string
	 */
	public function set_email_content_type( $content_type ) {
		// Only for our emails.
		if ( doing_action( 'woocommerce_order_status_completed' ) || doing_action( 'woocommerce_order_status_processing' ) ) {
			return 'text/html';
		}

		return $content_type;
	}
}
