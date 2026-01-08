<?php
/**
 * Participant Confirmation Email Template
 *
 * This template is used to send booking confirmation emails to participants.
 * Uses table-based layout with inline CSS for maximum email client compatibility.
 *
 * @package Gym_Multi_Participant_Booking
 * @since 1.0.0
 *
 * Available variables (extracted from email handler):
 * @var string $participant_name     Participant's name
 * @var string $participant_email    Participant's email address
 * @var string $participant_phone    Participant's phone (if provided)
 * @var int    $order_id             Order ID
 * @var string $order_number         Order number
 * @var string $order_date           Order date formatted
 * @var string $product_name         Product/service name
 * @var int    $product_quantity     Quantity booked
 * @var string $gym_name             Gym/business name from settings
 * @var string $gym_address          Gym address from settings
 * @var string $gym_contact_email    Gym contact email from settings
 * @var string $gym_contact_phone    Gym contact phone from settings
 * @var string $booking_date         Booking date (if available)
 * @var string $booking_time         Booking time (if available)
 * @var string $additional_notes     Additional notes from settings
 * @var WC_Order $order              Full order object
 * @var WC_Order_Item_Product $item  Order item object
 * @var array  $participant          Full participant data array
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current year for copyright.
$current_year = gmdate( 'Y' );

// Format phone number if exists.
$participant_phone_display = ! empty( $participant_phone ) ? $participant_phone : '';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="format-detection" content="telephone=no" />
	<title><?php echo esc_html( $gym_name ); ?> - Booking Confirmation</title>
	<style type="text/css">
		/* Client-specific Styles */
		body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
		table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
		img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }

		/* iOS Blue Links */
		a[x-apple-data-detectors] {
			color: inherit !important;
			text-decoration: none !important;
			font-size: inherit !important;
			font-family: inherit !important;
			font-weight: inherit !important;
			line-height: inherit !important;
		}

		/* Gmail Blue Links */
		u + #body a {
			color: inherit;
			text-decoration: none;
			font-size: inherit;
			font-family: inherit;
			font-weight: inherit;
			line-height: inherit;
		}

		/* Media Queries */
		@media only screen and (max-width: 600px) {
			.email-container {
				width: 100% !important;
			}
			.mobile-padding {
				padding: 15px !important;
			}
			.mobile-font-size {
				font-size: 16px !important;
			}
		}
	</style>
</head>
<body style="margin: 0; padding: 0; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f4f4; color: #333333; line-height: 1.6;">

	<!-- Preheader Text (hidden, but shows in email preview) -->
	<div style="display: none; max-height: 0px; overflow: hidden;">
		Your booking confirmation for <?php echo esc_html( $product_name ); ?> - Order #<?php echo esc_html( $order_number ); ?>
	</div>

	<!-- Email Wrapper -->
	<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f4f4; padding: 20px 0;">
		<tr>
			<td align="center">

				<!-- Main Email Container -->
				<table class="email-container" width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; max-width: 600px; margin: 0 auto; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">

					<!-- Header Section -->
					<tr>
						<td style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); background-color: #2c3e50; padding: 40px 20px; text-align: center;">
							<table width="100%" cellpadding="0" cellspacing="0" border="0">
								<tr>
									<td align="center">
										<h1 style="color: #ffffff; margin: 0; font-size: 32px; font-weight: bold; letter-spacing: -0.5px;">
											<?php echo esc_html( $gym_name ); ?>
										</h1>
										<p style="color: #ecf0f1; margin: 10px 0 0; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">
											Booking Confirmation
										</p>
									</td>
								</tr>
							</table>
						</td>
					</tr>

					<!-- Greeting Section -->
					<tr>
						<td class="mobile-padding" style="padding: 35px 30px 20px;">
							<table width="100%" cellpadding="0" cellspacing="0" border="0">
								<tr>
									<td>
										<h2 style="color: #2c3e50; margin: 0 0 15px; font-size: 26px; font-weight: 600; line-height: 1.3;">
											Hello <?php echo esc_html( $participant_name ); ?>! 👋
										</h2>
										<p style="color: #555555; font-size: 16px; line-height: 1.7; margin: 0;">
											Great news! Your gym booking has been <strong style="color: #27ae60;">successfully confirmed</strong>.
											We're excited to see you soon!
										</p>
									</td>
								</tr>
							</table>
						</td>
					</tr>

					<!-- Booking Details Box -->
					<tr>
						<td class="mobile-padding" style="padding: 0 30px 25px;">
							<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f8f9fa; border-left: 4px solid #3498db; border-radius: 4px;">
								<tr>
									<td style="padding: 25px;">
										<table width="100%" cellpadding="0" cellspacing="0" border="0">
											<tr>
												<td>
													<h3 style="color: #2c3e50; margin: 0 0 20px; font-size: 20px; font-weight: 600;">
														📅 Booking Details
													</h3>
												</td>
											</tr>

											<!-- Service/Product -->
											<tr>
												<td style="padding: 10px 0; border-bottom: 1px solid #e0e0e0;">
													<table width="100%" cellpadding="0" cellspacing="0" border="0">
														<tr>
															<td width="40%" style="color: #7f8c8d; font-size: 14px; font-weight: 600; padding-right: 10px;">
																Service:
															</td>
															<td style="color: #2c3e50; font-size: 14px; font-weight: 500;">
																<?php echo esc_html( $product_name ); ?>
															</td>
														</tr>
													</table>
												</td>
											</tr>

											<!-- Booking Date (if available) -->
											<?php if ( ! empty( $booking_date ) ) : ?>
											<tr>
												<td style="padding: 10px 0; border-bottom: 1px solid #e0e0e0;">
													<table width="100%" cellpadding="0" cellspacing="0" border="0">
														<tr>
															<td width="40%" style="color: #7f8c8d; font-size: 14px; font-weight: 600; padding-right: 10px;">
																Date:
															</td>
															<td style="color: #2c3e50; font-size: 14px; font-weight: 500;">
																<?php echo esc_html( $booking_date ); ?>
															</td>
														</tr>
													</table>
												</td>
											</tr>
											<?php endif; ?>

											<!-- Booking Time (if available) -->
											<?php if ( ! empty( $booking_time ) ) : ?>
											<tr>
												<td style="padding: 10px 0; border-bottom: 1px solid #e0e0e0;">
													<table width="100%" cellpadding="0" cellspacing="0" border="0">
														<tr>
															<td width="40%" style="color: #7f8c8d; font-size: 14px; font-weight: 600; padding-right: 10px;">
																Time:
															</td>
															<td style="color: #2c3e50; font-size: 14px; font-weight: 500;">
																<?php echo esc_html( $booking_time ); ?>
															</td>
														</tr>
													</table>
												</td>
											</tr>
											<?php endif; ?>

											<!-- Participant Name -->
											<tr>
												<td style="padding: 10px 0; border-bottom: 1px solid #e0e0e0;">
													<table width="100%" cellpadding="0" cellspacing="0" border="0">
														<tr>
															<td width="40%" style="color: #7f8c8d; font-size: 14px; font-weight: 600; padding-right: 10px;">
																Participant:
															</td>
															<td style="color: #2c3e50; font-size: 14px; font-weight: 500;">
																<?php echo esc_html( $participant_name ); ?>
															</td>
														</tr>
													</table>
												</td>
											</tr>

											<!-- Participant Email -->
											<tr>
												<td style="padding: 10px 0; border-bottom: 1px solid #e0e0e0;">
													<table width="100%" cellpadding="0" cellspacing="0" border="0">
														<tr>
															<td width="40%" style="color: #7f8c8d; font-size: 14px; font-weight: 600; padding-right: 10px;">
																Email:
															</td>
															<td style="color: #2c3e50; font-size: 14px; font-weight: 500;">
																<?php echo esc_html( $participant_email ); ?>
															</td>
														</tr>
													</table>
												</td>
											</tr>

											<!-- Participant Phone (if provided) -->
											<?php if ( ! empty( $participant_phone_display ) ) : ?>
											<tr>
												<td style="padding: 10px 0; border-bottom: 1px solid #e0e0e0;">
													<table width="100%" cellpadding="0" cellspacing="0" border="0">
														<tr>
															<td width="40%" style="color: #7f8c8d; font-size: 14px; font-weight: 600; padding-right: 10px;">
																Phone:
															</td>
															<td style="color: #2c3e50; font-size: 14px; font-weight: 500;">
																<?php echo esc_html( $participant_phone_display ); ?>
															</td>
														</tr>
													</table>
												</td>
											</tr>
											<?php endif; ?>

											<!-- Order Date -->
											<tr>
												<td style="padding: 10px 0; border-bottom: 1px solid #e0e0e0;">
													<table width="100%" cellpadding="0" cellspacing="0" border="0">
														<tr>
															<td width="40%" style="color: #7f8c8d; font-size: 14px; font-weight: 600; padding-right: 10px;">
																Booked On:
															</td>
															<td style="color: #2c3e50; font-size: 14px; font-weight: 500;">
																<?php echo esc_html( $order_date ); ?>
															</td>
														</tr>
													</table>
												</td>
											</tr>

											<!-- Order Reference -->
											<tr>
												<td style="padding: 10px 0;">
													<table width="100%" cellpadding="0" cellspacing="0" border="0">
														<tr>
															<td width="40%" style="color: #7f8c8d; font-size: 14px; font-weight: 600; padding-right: 10px;">
																Reference:
															</td>
															<td style="color: #2c3e50; font-size: 14px; font-weight: 500;">
																<strong>#<?php echo esc_html( $order_number ); ?></strong>
															</td>
														</tr>
													</table>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>

					<!-- Location Section (if address available) -->
					<?php if ( ! empty( $gym_address ) ) : ?>
					<tr>
						<td class="mobile-padding" style="padding: 0 30px 25px;">
							<table width="100%" cellpadding="0" cellspacing="0" border="0">
								<tr>
									<td>
										<h3 style="color: #2c3e50; margin: 0 0 10px; font-size: 18px; font-weight: 600;">
											📍 Location
										</h3>
										<p style="color: #555555; font-size: 14px; line-height: 1.7; margin: 0;">
											<?php echo nl2br( esc_html( $gym_address ) ); ?>
										</p>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<?php endif; ?>

					<!-- What to Bring Section -->
					<tr>
						<td class="mobile-padding" style="padding: 0 30px 25px;">
							<table width="100%" cellpadding="0" cellspacing="0" border="0">
								<tr>
									<td>
										<h3 style="color: #2c3e50; margin: 0 0 15px; font-size: 18px; font-weight: 600;">
											🎒 What to Bring
										</h3>
										<table width="100%" cellpadding="0" cellspacing="0" border="0">
											<tr>
												<td style="color: #555555; font-size: 14px; line-height: 2;">
													✓ This confirmation email (printed or on mobile)<br>
													✓ Valid photo ID<br>
													✓ Comfortable workout clothes<br>
													✓ Water bottle<br>
													✓ Towel
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>

					<!-- Important Notes Section -->
					<tr>
						<td class="mobile-padding" style="padding: 0 30px 25px;">
							<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #fffbf0; border-left: 4px solid #f39c12; border-radius: 4px;">
								<tr>
									<td style="padding: 20px;">
										<table width="100%" cellpadding="0" cellspacing="0" border="0">
											<tr>
												<td>
													<h3 style="color: #e67e22; margin: 0 0 10px; font-size: 16px; font-weight: 600;">
														⚠️ Important Reminders
													</h3>
													<p style="color: #856404; font-size: 14px; line-height: 1.8; margin: 0;">
														• Please arrive <strong>15 minutes early</strong><br>
														• Cancellations require <strong>24 hours notice</strong><br>
														• Contact us for rescheduling or questions
													</p>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>

					<!-- Additional Notes (if provided) -->
					<?php if ( ! empty( $additional_notes ) ) : ?>
					<tr>
						<td class="mobile-padding" style="padding: 0 30px 25px;">
							<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #e8f4f8; border-left: 4px solid #3498db; border-radius: 4px;">
								<tr>
									<td style="padding: 20px;">
										<table width="100%" cellpadding="0" cellspacing="0" border="0">
											<tr>
												<td>
													<h3 style="color: #2980b9; margin: 0 0 10px; font-size: 16px; font-weight: 600;">
														📌 Additional Information
													</h3>
													<p style="color: #31708f; font-size: 14px; line-height: 1.7; margin: 0;">
														<?php echo wp_kses_post( nl2br( $additional_notes ) ); ?>
													</p>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<?php endif; ?>

					<!-- Contact Section -->
					<tr>
						<td style="padding: 30px; background-color: #f8f9fa; border-top: 1px solid #e0e0e0;">
							<table width="100%" cellpadding="0" cellspacing="0" border="0">
								<tr>
									<td>
										<h3 style="color: #2c3e50; margin: 0 0 15px; font-size: 18px; font-weight: 600;">
											💬 Need Help?
										</h3>
										<p style="color: #555555; font-size: 14px; line-height: 1.7; margin: 0 0 15px;">
											If you have any questions or need to make changes to your booking, we're here to help:
										</p>

										<table width="100%" cellpadding="0" cellspacing="0" border="0">
											<!-- Contact Email -->
											<?php if ( ! empty( $gym_contact_email ) ) : ?>
											<tr>
												<td style="padding: 8px 0;">
													<table cellpadding="0" cellspacing="0" border="0">
														<tr>
															<td style="color: #7f8c8d; font-size: 14px; padding-right: 10px; vertical-align: top;">
																📧
															</td>
															<td>
																<a href="mailto:<?php echo esc_attr( $gym_contact_email ); ?>" style="color: #3498db; text-decoration: none; font-size: 14px;">
																	<?php echo esc_html( $gym_contact_email ); ?>
																</a>
															</td>
														</tr>
													</table>
												</td>
											</tr>
											<?php endif; ?>

											<!-- Contact Phone -->
											<?php if ( ! empty( $gym_contact_phone ) ) : ?>
											<tr>
												<td style="padding: 8px 0;">
													<table cellpadding="0" cellspacing="0" border="0">
														<tr>
															<td style="color: #7f8c8d; font-size: 14px; padding-right: 10px; vertical-align: top;">
																📞
															</td>
															<td>
																<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $gym_contact_phone ) ); ?>" style="color: #3498db; text-decoration: none; font-size: 14px;">
																	<?php echo esc_html( $gym_contact_phone ); ?>
																</a>
															</td>
														</tr>
													</table>
												</td>
											</tr>
											<?php endif; ?>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>

					<!-- Closing Message -->
					<tr>
						<td class="mobile-padding" style="padding: 30px;">
							<table width="100%" cellpadding="0" cellspacing="0" border="0">
								<tr>
									<td style="text-align: center;">
										<p style="color: #555555; font-size: 16px; line-height: 1.7; margin: 0 0 10px;">
											We look forward to seeing you at the gym!
										</p>
										<p style="color: #7f8c8d; font-size: 14px; margin: 0;">
											Best regards,<br>
											<strong style="color: #2c3e50;"><?php echo esc_html( $gym_name ); ?> Team</strong>
										</p>
									</td>
								</tr>
							</table>
						</td>
					</tr>

					<!-- Footer -->
					<tr>
						<td style="padding: 25px 30px; text-align: center; background-color: #2c3e50; color: #ffffff; font-size: 13px; line-height: 1.6;">
							<table width="100%" cellpadding="0" cellspacing="0" border="0">
								<tr>
									<td align="center">
										<p style="margin: 0 0 10px; color: #ecf0f1; font-weight: 600;">
											<?php echo esc_html( $gym_name ); ?>
										</p>
										<?php if ( ! empty( $gym_address ) ) : ?>
										<p style="margin: 0 0 10px; color: #bdc3c7; font-size: 12px;">
											<?php echo esc_html( str_replace( "\n", ', ', $gym_address ) ); ?>
										</p>
										<?php endif; ?>
										<p style="margin: 0; color: #95a5a6; font-size: 11px;">
											© <?php echo esc_html( $current_year ); ?> <?php echo esc_html( $gym_name ); ?>. All rights reserved.
										</p>
										<p style="margin: 10px 0 0; color: #95a5a6; font-size: 11px;">
											This is an automated confirmation email. Please do not reply directly.
										</p>
									</td>
								</tr>
							</table>
						</td>
					</tr>

				</table>
				<!-- End Main Email Container -->

			</td>
		</tr>
	</table>
	<!-- End Email Wrapper -->

</body>
</html>
<?php
/**
 * Template Customization
 *
 * Developers can override this template by copying it to:
 * your-theme/gym-multi-participant-booking/email/participant-confirmation.php
 *
 * You can also use filters to modify the email:
 *
 * @example Customize email HTML
 * add_filter( 'gmpb_email_content', 'custom_email_html', 10, 4 );
 * function custom_email_html( $html, $participant, $order, $item ) {
 *     // Modify $html
 *     return $html;
 * }
 *
 * @example Modify template variables before rendering
 * add_filter( 'gmpb_email_template_vars', 'custom_email_vars', 10, 3 );
 * function custom_email_vars( $vars, $order, $item ) {
 *     $vars['custom_field'] = 'Custom Value';
 *     return $vars;
 * }
 */
?>
