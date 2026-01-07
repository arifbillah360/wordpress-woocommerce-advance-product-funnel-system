<?php
/**
 * Participant Confirmation Email Template
 *
 * This template is used to send booking confirmation emails to participants.
 * Variables available in this template are extracted from the email handler.
 *
 * @package Gym_Multi_Participant_Booking
 * @since 1.0.0
 *
 * Available variables:
 * @var string $participant_name    Participant's name
 * @var string $participant_email   Participant's email address
 * @var int    $order_id            Order ID
 * @var string $order_number        Order number
 * @var string $product_name        Product name
 * @var string $gym_name            Gym name from settings
 * @var string $gym_address         Gym address from settings
 * @var string $booking_date        Booking date (if available)
 * @var string $booking_time        Booking time (if available)
 * @var WC_Order $order             Full order object
 * @var WC_Order_Item_Product $item Order item object
 * @var array  $participant         Full participant data array
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( $gym_name ); ?> - Booking Confirmation</title>
	<style type="text/css">
		body {
			margin: 0;
			padding: 0;
			font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
			background-color: #f4f4f4;
			color: #333333;
			line-height: 1.6;
		}
		.email-container {
			max-width: 600px;
			margin: 0 auto;
			background-color: #ffffff;
		}
		.email-header {
			background-color: #0073aa;
			color: #ffffff;
			padding: 30px;
			text-align: center;
		}
		.email-header h1 {
			margin: 0;
			font-size: 28px;
			font-weight: bold;
		}
		.email-body {
			padding: 40px 30px;
		}
		.email-body h2 {
			color: #0073aa;
			font-size: 22px;
			margin-top: 0;
			margin-bottom: 20px;
		}
		.email-body p {
			margin: 15px 0;
			font-size: 16px;
		}
		.booking-details {
			background-color: #f9f9f9;
			border-left: 4px solid #0073aa;
			padding: 20px;
			margin: 25px 0;
		}
		.booking-details h3 {
			margin-top: 0;
			color: #0073aa;
			font-size: 18px;
		}
		.detail-row {
			display: table;
			width: 100%;
			margin: 10px 0;
		}
		.detail-label {
			display: table-cell;
			width: 40%;
			font-weight: bold;
			color: #555555;
			padding: 8px 0;
		}
		.detail-value {
			display: table-cell;
			width: 60%;
			color: #333333;
			padding: 8px 0;
		}
		.cta-button {
			display: inline-block;
			background-color: #0073aa;
			color: #ffffff;
			text-decoration: none;
			padding: 15px 30px;
			border-radius: 5px;
			font-weight: bold;
			margin: 20px 0;
		}
		.email-footer {
			background-color: #f4f4f4;
			padding: 30px;
			text-align: center;
			font-size: 14px;
			color: #666666;
		}
		.email-footer p {
			margin: 10px 0;
		}
		.divider {
			border-top: 1px solid #e5e5e5;
			margin: 30px 0;
		}
		@media only screen and (max-width: 600px) {
			.email-body {
				padding: 20px 15px;
			}
			.booking-details {
				padding: 15px;
			}
			.detail-label,
			.detail-value {
				display: block;
				width: 100%;
			}
		}
	</style>
</head>
<body>
	<div class="email-container">
		<!-- Email Header -->
		<div class="email-header">
			<h1><?php echo esc_html( $gym_name ); ?></h1>
		</div>

		<!-- Email Body -->
		<div class="email-body">
			<h2>Booking Confirmation</h2>

			<p>Dear <?php echo esc_html( $participant_name ); ?>,</p>

			<p>Thank you for your booking! This email confirms your participation in <strong><?php echo esc_html( $product_name ); ?></strong>.</p>

			<!-- Booking Details Section -->
			<div class="booking-details">
				<h3>Booking Details</h3>

				<div class="detail-row">
					<div class="detail-label">Order Number:</div>
					<div class="detail-value">#<?php echo esc_html( $order_number ); ?></div>
				</div>

				<div class="detail-row">
					<div class="detail-label">Product:</div>
					<div class="detail-value"><?php echo esc_html( $product_name ); ?></div>
				</div>

				<div class="detail-row">
					<div class="detail-label">Participant Name:</div>
					<div class="detail-value"><?php echo esc_html( $participant_name ); ?></div>
				</div>

				<div class="detail-row">
					<div class="detail-label">Email:</div>
					<div class="detail-value"><?php echo esc_html( $participant_email ); ?></div>
				</div>

				<?php if ( ! empty( $booking_date ) ) : ?>
				<div class="detail-row">
					<div class="detail-label">Date:</div>
					<div class="detail-value"><?php echo esc_html( $booking_date ); ?></div>
				</div>
				<?php endif; ?>

				<?php if ( ! empty( $booking_time ) ) : ?>
				<div class="detail-row">
					<div class="detail-label">Time:</div>
					<div class="detail-value"><?php echo esc_html( $booking_time ); ?></div>
				</div>
				<?php endif; ?>

				<div class="detail-row">
					<div class="detail-label">Order Date:</div>
					<div class="detail-value"><?php echo esc_html( $order->get_date_created()->date_i18n( 'F j, Y g:i A' ) ); ?></div>
				</div>
			</div>

			<?php if ( ! empty( $gym_address ) ) : ?>
			<p><strong>Location:</strong><br>
			<?php echo nl2br( esc_html( $gym_address ) ); ?></p>
			<?php endif; ?>

			<div class="divider"></div>

			<p><strong>What to Bring:</strong></p>
			<ul>
				<li>This confirmation email (printed or on your mobile device)</li>
				<li>Valid photo ID</li>
				<li>Appropriate workout attire</li>
				<li>Water bottle</li>
			</ul>

			<p><strong>Important Notes:</strong></p>
			<ul>
				<li>Please arrive 15 minutes before your scheduled time</li>
				<li>Cancellations must be made at least 24 hours in advance</li>
				<li>Contact us if you need to reschedule</li>
			</ul>

			<div class="divider"></div>

			<p>If you have any questions or need to make changes to your booking, please don't hesitate to contact us.</p>

			<p>We look forward to seeing you!</p>

			<p>
				Best regards,<br>
				<strong><?php echo esc_html( $gym_name ); ?></strong>
			</p>
		</div>

		<!-- Email Footer -->
		<div class="email-footer">
			<p><strong><?php echo esc_html( $gym_name ); ?></strong></p>
			<?php if ( ! empty( $gym_address ) ) : ?>
			<p><?php echo esc_html( str_replace( "\n", ', ', $gym_address ) ); ?></p>
			<?php endif; ?>
			<p>This is an automated confirmation email. Please do not reply directly to this message.</p>
		</div>
	</div>
</body>
</html>
<?php
/**
 * Developers can override this template by copying it to:
 * your-theme/gym-multi-participant-booking/email/participant-confirmation.php
 *
 * You can also use the 'gmpb_email_content' filter to modify the email content:
 * add_filter( 'gmpb_email_content', 'your_custom_email_content', 10, 4 );
 */
?>
