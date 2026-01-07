<?php
/**
 * Participant Manager Class
 *
 * Handles participant management for bookings.
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
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'display_participant_fields' ) );

		// Validate participant data before adding to cart.
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_participants' ), 10, 3 );

		// Add participant data to cart item.
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 3 );

		// Display participant data in cart.
		add_filter( 'woocommerce_get_item_data', array( $this, 'display_cart_item_data' ), 10, 2 );

		// Add participant data to order items.
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_order_item_meta' ), 10, 4 );

		// Display participant data in order details.
		add_action( 'woocommerce_order_item_meta_end', array( $this, 'display_order_item_meta' ), 10, 4 );
	}

	/**
	 * Display participant fields on product page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function display_participant_fields() {
		global $product;

		if ( ! $product ) {
			return;
		}

		// Check if participants are enabled for this product.
		$enabled = get_post_meta( $product->get_id(), '_gmpb_enable_participants', true );

		if ( 'yes' !== $enabled ) {
			return;
		}

		// Get product settings.
		$min_participants = get_post_meta( $product->get_id(), '_gmpb_min_participants', true );
		$max_participants = get_post_meta( $product->get_id(), '_gmpb_max_participants', true );
		$require_names    = get_post_meta( $product->get_id(), '_gmpb_require_names', true );
		$custom_label     = get_post_meta( $product->get_id(), '_gmpb_custom_label', true );

		// Set defaults.
		$min_participants = $min_participants ? absint( $min_participants ) : 1;
		$max_participants = $max_participants ? absint( $max_participants ) : 10;
		$require_names    = 'yes' === $require_names;
		$custom_label     = ! empty( $custom_label ) ? $custom_label : __( 'Participant Information', 'gym-multi-participant-booking' );

		// Load template.
		include GMPB_PLUGIN_DIR . 'templates/participant-fields.php';
	}

	/**
	 * Validate participant data before adding to cart.
	 *
	 * @since 1.0.0
	 * @param bool $passed      Validation status.
	 * @param int  $product_id  Product ID.
	 * @param int  $quantity    Quantity.
	 * @return bool
	 */
	public function validate_participants( $passed, $product_id, $quantity ) {
		// Check if participants are enabled for this product.
		$enabled = get_post_meta( $product_id, '_gmpb_enable_participants', true );

		if ( 'yes' !== $enabled ) {
			return $passed;
		}

		// Check if participant data was submitted.
		if ( ! isset( $_POST['gmpb_participants'] ) || ! is_array( $_POST['gmpb_participants'] ) ) {
			wc_add_notice( __( 'Please add participant information.', 'gym-multi-participant-booking' ), 'error' );
			return false;
		}

		// Get product settings.
		$min_participants = get_post_meta( $product_id, '_gmpb_min_participants', true );
		$max_participants = get_post_meta( $product_id, '_gmpb_max_participants', true );
		$require_names    = get_post_meta( $product_id, '_gmpb_require_names', true );

		$min_participants = $min_participants ? absint( $min_participants ) : 1;
		$max_participants = $max_participants ? absint( $max_participants ) : 10;
		$require_names    = 'yes' === $require_names;

		// Count valid participants.
		$valid_count = 0;
		$participants = array_map( 'wp_unslash', $_POST['gmpb_participants'] );

		foreach ( $participants as $index => $participant ) {
			$name  = isset( $participant['name'] ) ? sanitize_text_field( $participant['name'] ) : '';
			$email = isset( $participant['email'] ) ? sanitize_email( $participant['email'] ) : '';

			// Check if email is provided and valid.
			if ( empty( $email ) ) {
				continue;
			}

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

		return $passed;
	}

	/**
	 * Add participant data to cart item.
	 *
	 * @since 1.0.0
	 * @param array $cart_item_data Cart item data.
	 * @param int   $product_id     Product ID.
	 * @param int   $variation_id   Variation ID.
	 * @return array
	 */
	public function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		// Check if participants data exists.
		if ( ! isset( $_POST['gmpb_participants'] ) || ! is_array( $_POST['gmpb_participants'] ) ) {
			return $cart_item_data;
		}

		// Sanitize and store participant data.
		$participants = array();
		$posted_participants = array_map( 'wp_unslash', $_POST['gmpb_participants'] );

		foreach ( $posted_participants as $participant ) {
			$name  = isset( $participant['name'] ) ? sanitize_text_field( $participant['name'] ) : '';
			$email = isset( $participant['email'] ) ? sanitize_email( $participant['email'] ) : '';

			// Only add participants with valid email.
			if ( ! empty( $email ) && is_email( $email ) ) {
				$participants[] = array(
					'name'  => $name,
					'email' => $email,
				);
			}
		}

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
	 * @return array
	 */
	public function display_cart_item_data( $item_data, $cart_item ) {
		if ( ! isset( $cart_item['gmpb_participants'] ) || ! is_array( $cart_item['gmpb_participants'] ) ) {
			return $item_data;
		}

		$participants_html = '<ul style="margin: 5px 0; padding-left: 20px;">';

		foreach ( $cart_item['gmpb_participants'] as $index => $participant ) {
			$name  = ! empty( $participant['name'] ) ? esc_html( $participant['name'] ) : __( 'N/A', 'gym-multi-participant-booking' );
			$email = esc_html( $participant['email'] );

			$participants_html .= sprintf(
				'<li>%s - %s</li>',
				$name,
				$email
			);
		}

		$participants_html .= '</ul>';

		$item_data[] = array(
			'key'     => __( 'Participants', 'gym-multi-participant-booking' ),
			'value'   => $participants_html,
			'display' => $participants_html,
		);

		return $item_data;
	}

	/**
	 * Add participant data to order items.
	 *
	 * @since 1.0.0
	 * @param WC_Order_Item_Product $item          Order item.
	 * @param string                $cart_item_key Cart item key.
	 * @param array                 $values        Cart item values.
	 * @param WC_Order              $order         Order object.
	 * @return void
	 */
	public function add_order_item_meta( $item, $cart_item_key, $values, $order ) {
		if ( isset( $values['gmpb_participants'] ) && is_array( $values['gmpb_participants'] ) ) {
			$item->add_meta_data( '_gmpb_participants', $values['gmpb_participants'], true );
		}
	}

	/**
	 * Display participant data in order details.
	 *
	 * @since 1.0.0
	 * @param int           $item_id   Order item ID.
	 * @param WC_Order_Item $item      Order item.
	 * @param WC_Order      $order     Order object.
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
	 * Get participants from order item.
	 *
	 * @since 1.0.0
	 * @param WC_Order_Item_Product $item Order item.
	 * @return array
	 */
	public function get_order_item_participants( $item ) {
		$participants = $item->get_meta( '_gmpb_participants' );

		if ( empty( $participants ) || ! is_array( $participants ) ) {
			return array();
		}

		return $participants;
	}
}
