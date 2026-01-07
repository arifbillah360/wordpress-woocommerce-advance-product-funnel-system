<?php
/**
 * Participant Manager Class
 *
 * Handles all participant-related functionality with product-specific checks.
 *
 * @package Gym_Multi_Participant_Booking
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GMPB_Participant_Manager Class.
 *
 * Manages participant data collection, validation, and storage.
 * IMPORTANT: All functionality only works on products where participant booking is enabled.
 *
 * @since 1.0.0
 */
class GMPB_Participant_Manager {

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
		// Display participant fields on product page.
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'add_participant_fields_to_product' ), 10 );

		// Validate participant data before adding to cart.
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_participant_data' ), 10, 4 );

		// Add participant data to cart item.
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'save_participant_data_to_cart' ), 10, 3 );

		// Display participant data in cart.
		add_filter( 'woocommerce_get_item_data', array( $this, 'display_participant_data_in_cart' ), 10, 2 );

		// Save participant data to order items.
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'save_participant_data_to_order' ), 10, 4 );

		// Display participant data in admin order items.
		add_action( 'woocommerce_admin_order_item_headers', array( $this, 'display_participant_data_in_admin' ), 10 );
		add_action( 'woocommerce_admin_order_item_values', array( $this, 'display_participant_values_in_admin' ), 10, 3 );

		// Display participant data in order details (customer view).
		add_action( 'woocommerce_order_item_meta_end', array( $this, 'display_order_item_meta' ), 10, 4 );

		// AJAX handler for resending participant emails.
		add_action( 'wp_ajax_gmpb_resend_participant_email', array( $this, 'handle_ajax_resend_email' ) );
	}

	/**
	 * Display participant form on product page.
	 *
	 * IMPORTANT: Only displays if participant booking is enabled for this product.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_participant_fields_to_product() {
		global $product;

		// Check if product exists.
		if ( ! $product ) {
			return;
		}

		$product_id = $product->get_id();

		// IMPORTANT CHECK: Only proceed if participant booking is enabled.
		if ( ! GMPB_Product_Settings::is_participant_booking_enabled( $product_id ) ) {
			return;
		}

		// Get product settings.
		$settings = GMPB_Product_Settings::get_product_settings( $product_id );

		// Prepare template variables.
		$max_participants  = $settings['max'];
		$require_names     = $settings['require_names'];
		$custom_label      = ! empty( $settings['custom_label'] ) ? $settings['custom_label'] : __( 'Participant Information', 'gym-multi-participant-booking' );
		$min_participants  = $settings['min'];

		// Load template.
		$template_path = GMPB_PLUGIN_DIR . 'templates/participant-fields.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			// Log error for debugging.
			error_log( 'GMPB Error: Participant fields template not found at ' . $template_path );
		}
	}

	/**
	 * Validate participant data before adding to cart.
	 *
	 * @since 1.0.0
	 * @param bool $passed       Validation status.
	 * @param int  $product_id   Product ID.
	 * @param int  $quantity     Quantity.
	 * @param int  $variation_id Variation ID (optional).
	 * @return bool Validation status.
	 */
	public function validate_participant_data( $passed, $product_id, $quantity, $variation_id = 0 ) {
		// IMPORTANT CHECK: Only validate if participant booking is enabled.
		if ( ! GMPB_Product_Settings::is_participant_booking_enabled( $product_id ) ) {
			return $passed;
		}

		// Check if participant data was submitted.
		if ( ! isset( $_POST['gmpb_participants'] ) || ! is_array( $_POST['gmpb_participants'] ) ) {
			wc_add_notice( __( 'Please add participant information.', 'gym-multi-participant-booking' ), 'error' );
			return false;
		}

		// Get product settings.
		$settings         = GMPB_Product_Settings::get_product_settings( $product_id );
		$min_participants = $settings['min'];
		$max_participants = $settings['max'];
		$require_names    = $settings['require_names'];

		// Sanitize participants data.
		$participants = array_map( 'wp_unslash', $_POST['gmpb_participants'] );

		// Count valid participants.
		$valid_count = 0;
		$emails      = array();

		foreach ( $participants as $index => $participant ) {
			$name  = isset( $participant['name'] ) ? sanitize_text_field( $participant['name'] ) : '';
			$email = isset( $participant['email'] ) ? sanitize_email( $participant['email'] ) : '';

			// Skip if email is empty.
			if ( empty( $email ) ) {
				continue;
			}

			// Validate email format.
			if ( ! is_email( $email ) ) {
				wc_add_notice(
					sprintf(
						/* translators: %d: participant number */
						__( 'Invalid email address for participant #%d.', 'gym-multi-participant-booking' ),
						$index + 1
					),
					'error'
				);
				return false;
			}

			// Check if name is required and provided.
			if ( $require_names && empty( $name ) ) {
				wc_add_notice(
					sprintf(
						/* translators: %d: participant number */
						__( 'Name is required for participant #%d.', 'gym-multi-participant-booking' ),
						$index + 1
					),
					'error'
				);
				return false;
			}

			$valid_count++;
			$emails[] = strtolower( $email ); // For duplicate check.
		}

		// Validate minimum participants.
		if ( $valid_count < $min_participants ) {
			wc_add_notice(
				sprintf(
					/* translators: %d: minimum participants */
					_n(
						'Please add at least %d participant.',
						'Please add at least %d participants.',
						$min_participants,
						'gym-multi-participant-booking'
					),
					$min_participants
				),
				'error'
			);
			return false;
		}

		// Validate maximum participants.
		if ( $valid_count > $max_participants ) {
			wc_add_notice(
				sprintf(
					/* translators: %d: maximum participants */
					_n(
						'You can add a maximum of %d participant.',
						'You can add a maximum of %d participants.',
						$max_participants,
						'gym-multi-participant-booking'
					),
					$max_participants
				),
				'error'
			);
			return false;
		}

		// Check for duplicate emails (optional feature).
		if ( apply_filters( 'gmpb_check_duplicate_emails', false ) ) {
			if ( count( $emails ) !== count( array_unique( $emails ) ) ) {
				wc_add_notice( __( 'Duplicate email addresses are not allowed.', 'gym-multi-participant-booking' ), 'error' );
				return false;
			}
		}

		return $passed;
	}

	/**
	 * Save participant data to cart item.
	 *
	 * @since 1.0.0
	 * @param array $cart_item_data Cart item data.
	 * @param int   $product_id     Product ID.
	 * @param int   $variation_id   Variation ID.
	 * @return array Modified cart item data.
	 */
	public function save_participant_data_to_cart( $cart_item_data, $product_id, $variation_id ) {
		// IMPORTANT CHECK: Only save if participant booking is enabled.
		if ( ! GMPB_Product_Settings::is_participant_booking_enabled( $product_id ) ) {
			return $cart_item_data;
		}

		// Check if participants data exists.
		if ( ! isset( $_POST['gmpb_participants'] ) || ! is_array( $_POST['gmpb_participants'] ) ) {
			return $cart_item_data;
		}

		// Sanitize and collect participant data.
		$participants        = array();
		$posted_participants = array_map( 'wp_unslash', $_POST['gmpb_participants'] );

		foreach ( $posted_participants as $participant ) {
			$name  = isset( $participant['name'] ) ? sanitize_text_field( $participant['name'] ) : '';
			$email = isset( $participant['email'] ) ? sanitize_email( $participant['email'] ) : '';

			// Only add participants with valid email.
			if ( ! empty( $email ) && is_email( $email ) ) {
				$participants[] = array(
					'name'       => $name,
					'email'      => $email,
					'email_sent' => false, // Track email status.
				);
			}
		}

		// Add to cart item data if we have participants.
		if ( ! empty( $participants ) ) {
			$cart_item_data['gmpb_participants'] = $participants;
		}

		return $cart_item_data;
	}

	/**
	 * Display participant data in cart.
	 *
	 * @since 1.0.0
	 * @param array $item_data Item data.
	 * @param array $cart_item Cart item.
	 * @return array Modified item data.
	 */
	public function display_participant_data_in_cart( $item_data, $cart_item ) {
		// Check if participant data exists.
		if ( ! isset( $cart_item['gmpb_participants'] ) || ! is_array( $cart_item['gmpb_participants'] ) ) {
			return $item_data;
		}

		// Format participants for display.
		$display_value = $this->format_participants_for_display( $cart_item['gmpb_participants'] );

		$item_data[] = array(
			'key'     => __( 'Participants', 'gym-multi-participant-booking' ),
			'value'   => $display_value,
			'display' => $display_value,
		);

		return $item_data;
	}

	/**
	 * Format participants for display.
	 *
	 * @since 1.0.0
	 * @param array $participants Array of participant data.
	 * @return string Formatted HTML string.
	 */
	private function format_participants_for_display( $participants ) {
		if ( empty( $participants ) || ! is_array( $participants ) ) {
			return '';
		}

		$html = '<ul style="margin: 5px 0; padding-left: 20px;">';

		$display_count = min( 3, count( $participants ) );

		for ( $i = 0; $i < $display_count; $i++ ) {
			$name  = ! empty( $participants[ $i ]['name'] ) ? esc_html( $participants[ $i ]['name'] ) : __( 'N/A', 'gym-multi-participant-booking' );
			$email = esc_html( $participants[ $i ]['email'] );

			$html .= sprintf( '<li>%s - %s</li>', $name, $email );
		}

		// Show "and X others" if more than 3.
		if ( count( $participants ) > 3 ) {
			$remaining = count( $participants ) - 3;
			$html     .= sprintf(
				'<li><em>%s</em></li>',
				sprintf(
					/* translators: %d: number of additional participants */
					_n( 'and %d other', 'and %d others', $remaining, 'gym-multi-participant-booking' ),
					$remaining
				)
			);
		}

		$html .= '</ul>';

		return $html;
	}

	/**
	 * Save participant data to order item.
	 *
	 * @since 1.0.0
	 * @param WC_Order_Item_Product $item          Order item.
	 * @param string                $cart_item_key Cart item key.
	 * @param array                 $values        Cart item values.
	 * @param WC_Order              $order         Order object.
	 * @return void
	 */
	public function save_participant_data_to_order( $item, $cart_item_key, $values, $order ) {
		// Check if participant data exists.
		if ( ! isset( $values['gmpb_participants'] ) || ! is_array( $values['gmpb_participants'] ) ) {
			return;
		}

		// Get product ID from item.
		$product_id = $item->get_product_id();

		// IMPORTANT CHECK: Only save if participant booking is enabled.
		if ( ! GMPB_Product_Settings::is_participant_booking_enabled( $product_id ) ) {
			return;
		}

		// Add participant data to order item meta (hidden from customer).
		$item->add_meta_data( '_gmpb_participants', $values['gmpb_participants'], true );
	}

	/**
	 * Display participant data in order details (customer view).
	 *
	 * @since 1.0.0
	 * @param int           $item_id    Order item ID.
	 * @param WC_Order_Item $item       Order item.
	 * @param WC_Order      $order      Order object.
	 * @param bool          $plain_text Whether it's plain text email.
	 * @return void
	 */
	public function display_order_item_meta( $item_id, $item, $order, $plain_text = false ) {
		$participants = $item->get_meta( '_gmpb_participants' );

		if ( empty( $participants ) || ! is_array( $participants ) ) {
			return;
		}

		if ( $plain_text ) {
			echo "\n" . __( 'Participants:', 'gym-multi-participant-booking' ) . "\n";
			foreach ( $participants as $participant ) {
				$name  = ! empty( $participant['name'] ) ? $participant['name'] : __( 'N/A', 'gym-multi-participant-booking' );
				$email = $participant['email'];
				echo '- ' . esc_html( $name ) . ' (' . esc_html( $email ) . ")\n";
			}
		} else {
			echo '<div class="gmpb-order-participants" style="margin-top: 10px;">';
			echo '<strong>' . esc_html__( 'Participants:', 'gym-multi-participant-booking' ) . '</strong>';
			echo '<ul style="margin: 5px 0; padding-left: 20px;">';
			foreach ( $participants as $participant ) {
				$name  = ! empty( $participant['name'] ) ? esc_html( $participant['name'] ) : __( 'N/A', 'gym-multi-participant-booking' );
				$email = esc_html( $participant['email'] );
				echo '<li>' . $name . ' - ' . $email . '</li>';
			}
			echo '</ul>';
			echo '</div>';
		}
	}

	/**
	 * Add participant column header in admin order items table.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function display_participant_data_in_admin() {
		echo '<th class="gmpb-participants">' . esc_html__( 'Participants', 'gym-multi-participant-booking' ) . '</th>';
	}

	/**
	 * Display participant values in admin order items table.
	 *
	 * @since 1.0.0
	 * @param WC_Product|null $product Product object.
	 * @param WC_Order_Item   $item    Order item.
	 * @param int             $item_id Order item ID.
	 * @return void
	 */
	public function display_participant_values_in_admin( $product, $item, $item_id ) {
		// Get participants.
		$participants = $item->get_meta( '_gmpb_participants', true );

		// If no participants, show dash.
		if ( empty( $participants ) || ! is_array( $participants ) ) {
			echo '<td class="gmpb-participants">-</td>';
			return;
		}

		// Get product ID.
		$product_id = $item->get_product_id();

		// IMPORTANT CHECK: Only display if participant booking is enabled.
		if ( ! GMPB_Product_Settings::is_participant_booking_enabled( $product_id ) ) {
			echo '<td class="gmpb-participants">-</td>';
			return;
		}

		// Get order ID.
		$order = $item->get_order();
		$order_id = $order ? $order->get_id() : 0;

		// Build HTML table.
		?>
		<td class="gmpb-participants">
			<table class="gmpb-participants-table" style="width: 100%; border-collapse: collapse;">
				<thead>
					<tr style="background-color: #f0f0f1;">
						<th style="padding: 5px; text-align: left; border: 1px solid #ddd;">#</th>
						<th style="padding: 5px; text-align: left; border: 1px solid #ddd;"><?php esc_html_e( 'Name', 'gym-multi-participant-booking' ); ?></th>
						<th style="padding: 5px; text-align: left; border: 1px solid #ddd;"><?php esc_html_e( 'Email', 'gym-multi-participant-booking' ); ?></th>
						<th style="padding: 5px; text-align: center; border: 1px solid #ddd;"><?php esc_html_e( 'Status', 'gym-multi-participant-booking' ); ?></th>
						<th style="padding: 5px; text-align: center; border: 1px solid #ddd;"><?php esc_html_e( 'Action', 'gym-multi-participant-booking' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $participants as $index => $participant ) : ?>
					<tr>
						<td style="padding: 5px; border: 1px solid #ddd;"><?php echo esc_html( $index + 1 ); ?></td>
						<td style="padding: 5px; border: 1px solid #ddd;">
							<?php echo esc_html( ! empty( $participant['name'] ) ? $participant['name'] : __( 'N/A', 'gym-multi-participant-booking' ) ); ?>
						</td>
						<td style="padding: 5px; border: 1px solid #ddd;">
							<?php echo esc_html( $participant['email'] ); ?>
						</td>
						<td style="padding: 5px; text-align: center; border: 1px solid #ddd;">
							<?php if ( ! empty( $participant['email_sent'] ) ) : ?>
								<span class="gmpb-email-sent" style="color: #46b450;">✓ <?php esc_html_e( 'Sent', 'gym-multi-participant-booking' ); ?></span>
							<?php else : ?>
								<span class="gmpb-email-not-sent" style="color: #dc3232;">✗ <?php esc_html_e( 'Not Sent', 'gym-multi-participant-booking' ); ?></span>
							<?php endif; ?>
						</td>
						<td style="padding: 5px; text-align: center; border: 1px solid #ddd;">
							<button type="button"
							        class="button button-small gmpb-resend-email"
							        data-order-id="<?php echo esc_attr( $order_id ); ?>"
							        data-item-id="<?php echo esc_attr( $item_id ); ?>"
							        data-participant-index="<?php echo esc_attr( $index ); ?>"
							        data-nonce="<?php echo esc_attr( wp_create_nonce( 'gmpb_resend_email' ) ); ?>">
								<?php esc_html_e( 'Resend', 'gym-multi-participant-booking' ); ?>
							</button>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</td>
		<?php
	}

	/**
	 * Get participants from order item (STATIC).
	 *
	 * @since 1.0.0
	 * @param int $item_id Order item ID.
	 * @return array Array of participants or empty array.
	 */
	public static function get_participants_from_order_item( $item_id ) {
		try {
			$item = new WC_Order_Item_Product( $item_id );
			$participants = $item->get_meta( '_gmpb_participants', true );

			return is_array( $participants ) ? $participants : array();
		} catch ( Exception $e ) {
			error_log( 'GMPB Error getting participants: ' . $e->getMessage() );
			return array();
		}
	}

	/**
	 * Update email sent status for a participant (STATIC).
	 *
	 * @since 1.0.0
	 * @param int  $item_id           Order item ID.
	 * @param int  $participant_index Participant index in array.
	 * @param bool $status            Email sent status.
	 * @return bool True on success, false on failure.
	 */
	public static function update_email_sent_status( $item_id, $participant_index, $status = true ) {
		try {
			$item = new WC_Order_Item_Product( $item_id );
			$participants = $item->get_meta( '_gmpb_participants', true );

			if ( ! is_array( $participants ) || ! isset( $participants[ $participant_index ] ) ) {
				return false;
			}

			// Update status.
			$participants[ $participant_index ]['email_sent'] = $status;

			// Save back to order item.
			$item->update_meta_data( '_gmpb_participants', $participants );
			$item->save();

			return true;
		} catch ( Exception $e ) {
			error_log( 'GMPB Error updating email status: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Handle AJAX resend email request.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_ajax_resend_email() {
		// Verify nonce.
		check_ajax_referer( 'gmpb_resend_email', 'nonce' );

		// Check capability.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'gym-multi-participant-booking' ) ) );
		}

		// Get POST data.
		$order_id          = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$item_id           = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : 0;
		$participant_index = isset( $_POST['participant_index'] ) ? absint( $_POST['participant_index'] ) : 0;

		// Validate data.
		if ( ! $order_id || ! $item_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'gym-multi-participant-booking' ) ) );
		}

		// Get participants.
		$participants = self::get_participants_from_order_item( $item_id );

		if ( ! isset( $participants[ $participant_index ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Participant not found.', 'gym-multi-participant-booking' ) ) );
		}

		// Get order.
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found.', 'gym-multi-participant-booking' ) ) );
		}

		// Get order item.
		$item = $order->get_item( $item_id );
		if ( ! $item ) {
			wp_send_json_error( array( 'message' => __( 'Order item not found.', 'gym-multi-participant-booking' ) ) );
		}

		// Get product.
		$product = $item->get_product();
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Product not found.', 'gym-multi-participant-booking' ) ) );
		}

		// Check if participant booking is enabled.
		if ( ! GMPB_Product_Settings::is_participant_booking_enabled( $product->get_id() ) ) {
			wp_send_json_error( array( 'message' => __( 'Participant booking not enabled for this product.', 'gym-multi-participant-booking' ) ) );
		}

		// Get participant data.
		$participant = $participants[ $participant_index ];

		// Send email via email handler.
		$email_handler = new GMPB_Email_Handler();

		// Try to use a method to send single participant email.
		// This assumes the email handler has a method for this.
		$sent = apply_filters( 'gmpb_send_single_participant_email', false, $participant, $item, $order, $product );

		if ( ! $sent ) {
			// Fallback: Call a basic email send.
			$sent = $this->send_single_participant_email( $participant, $item, $order, $product );
		}

		if ( $sent ) {
			// Update email sent status.
			self::update_email_sent_status( $item_id, $participant_index, true );

			wp_send_json_success( array( 'message' => __( 'Confirmation email sent successfully.', 'gym-multi-participant-booking' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to send email.', 'gym-multi-participant-booking' ) ) );
		}
	}

	/**
	 * Send confirmation email to a single participant.
	 *
	 * @since 1.0.0
	 * @param array                 $participant Participant data.
	 * @param WC_Order_Item_Product $item        Order item.
	 * @param WC_Order              $order       Order object.
	 * @param WC_Product            $product     Product object.
	 * @return bool True on success, false on failure.
	 */
	private function send_single_participant_email( $participant, $item, $order, $product ) {
		$email = $participant['email'];

		if ( ! is_email( $email ) ) {
			return false;
		}

		// Get email settings.
		$from_name    = get_option( 'gmpb_email_from_name', get_bloginfo( 'name' ) );
		$from_address = get_option( 'gmpb_email_from_address', get_option( 'admin_email' ) );

		$to      = $email;
		$subject = sprintf(
			/* translators: 1: site name, 2: product name */
			__( '[%1$s] Booking Confirmation - %2$s', 'gym-multi-participant-booking' ),
			$from_name,
			$product->get_name()
		);

		$name = ! empty( $participant['name'] ) ? $participant['name'] : __( 'Valued Customer', 'gym-multi-participant-booking' );

		// Simple email message.
		$message = sprintf(
			__( 'Dear %1$s,

Your gym booking has been confirmed!

Product: %2$s
Order Number: #%3$s

Thank you for your booking.

Best regards,
%4$s', 'gym-multi-participant-booking' ),
			$name,
			$product->get_name(),
			$order->get_order_number(),
			$from_name
		);

		// Set headers.
		$headers = array(
			'From: ' . $from_name . ' <' . $from_address . '>',
		);

		// Send email.
		return wp_mail( $to, $subject, $message, $headers );
	}
}
