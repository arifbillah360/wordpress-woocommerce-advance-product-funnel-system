<?php
/**
 * Product Settings Class
 *
 * Handles per-product settings for multi-participant booking.
 *
 * @package Gym_Multi_Participant_Booking
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GMPB_Product_Settings Class.
 *
 * Manages product-specific settings for participant booking.
 *
 * @since 1.0.0
 */
class GMPB_Product_Settings {

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
		// Add product data tab.
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_product_data_tab' ) );

		// Add product data panel.
		add_action( 'woocommerce_product_data_panels', array( $this, 'add_product_data_panel' ) );

		// Save product meta.
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_meta' ) );

		// Add custom column to products list.
		add_filter( 'manage_product_posts_columns', array( $this, 'add_product_column' ) );
		add_action( 'manage_product_posts_custom_column', array( $this, 'render_product_column' ), 10, 2 );
	}

	/**
	 * Add custom product data tab.
	 *
	 * @since 1.0.0
	 * @param array $tabs Existing product data tabs.
	 * @return array Modified product data tabs.
	 */
	public function add_product_data_tab( $tabs ) {
		$tabs['gmpb_participants'] = array(
			'label'    => __( 'Participants', 'gym-multi-participant-booking' ),
			'target'   => 'gmpb_participants_data',
			'class'    => array( 'show_if_simple', 'show_if_variable' ),
			'priority' => 25,
		);

		return $tabs;
	}

	/**
	 * Add product data panel.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_product_data_panel() {
		global $post;

		// Get current values.
		$enable_participants = get_post_meta( $post->ID, '_gmpb_enable_participants', true );
		$min_participants    = get_post_meta( $post->ID, '_gmpb_min_participants', true );
		$max_participants    = get_post_meta( $post->ID, '_gmpb_max_participants', true );
		$require_names       = get_post_meta( $post->ID, '_gmpb_require_names', true );
		$custom_label        = get_post_meta( $post->ID, '_gmpb_custom_label', true );

		// Set defaults.
		$min_participants = $min_participants ? $min_participants : get_option( 'gmpb_default_min_participants', 1 );
		$max_participants = $max_participants ? $max_participants : get_option( 'gmpb_default_max_participants', 10 );
		?>
		<div id="gmpb_participants_data" class="panel woocommerce_options_panel hidden">
			<div class="options_group">
				<p class="form-field">
					<strong><?php esc_html_e( 'Multi-Participant Booking', 'gym-multi-participant-booking' ); ?></strong>
				</p>

				<?php
				woocommerce_wp_checkbox(
					array(
						'id'            => '_gmpb_enable_participants',
						'label'         => __( 'Enable participant booking', 'gym-multi-participant-booking' ),
						'description'   => __( 'Allow customers to add multiple participants for this product.', 'gym-multi-participant-booking' ),
						'value'         => $enable_participants,
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => '_gmpb_min_participants',
						'label'             => __( 'Minimum participants', 'gym-multi-participant-booking' ),
						'description'       => __( 'Minimum number of participants required.', 'gym-multi-participant-booking' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '1',
							'step' => '1',
						),
						'value'             => $min_participants,
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => '_gmpb_max_participants',
						'label'             => __( 'Maximum participants', 'gym-multi-participant-booking' ),
						'description'       => __( 'Maximum number of participants allowed.', 'gym-multi-participant-booking' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '1',
							'step' => '1',
						),
						'value'             => $max_participants,
					)
				);

				woocommerce_wp_checkbox(
					array(
						'id'            => '_gmpb_require_names',
						'label'         => __( 'Require participant names', 'gym-multi-participant-booking' ),
						'description'   => __( 'Make participant names mandatory (emails are always required).', 'gym-multi-participant-booking' ),
						'value'         => $require_names,
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'          => '_gmpb_custom_label',
						'label'       => __( 'Custom section label', 'gym-multi-participant-booking' ),
						'description' => __( 'Custom label for the participants section (optional).', 'gym-multi-participant-booking' ),
						'placeholder' => __( 'Participant Information', 'gym-multi-participant-booking' ),
						'value'       => $custom_label,
					)
				);
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Save product meta data.
	 *
	 * @since 1.0.0
	 * @param int $post_id Product ID.
	 * @return void
	 */
	public function save_product_meta( $post_id ) {
		// Check nonce (WooCommerce handles this).
		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}

		// Check user permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save enable participants checkbox.
		$enable_participants = isset( $_POST['_gmpb_enable_participants'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_gmpb_enable_participants', $enable_participants );

		// Save min participants.
		if ( isset( $_POST['_gmpb_min_participants'] ) ) {
			$min_participants = absint( $_POST['_gmpb_min_participants'] );
			update_post_meta( $post_id, '_gmpb_min_participants', max( 1, $min_participants ) );
		}

		// Save max participants.
		if ( isset( $_POST['_gmpb_max_participants'] ) ) {
			$max_participants = absint( $_POST['_gmpb_max_participants'] );
			update_post_meta( $post_id, '_gmpb_max_participants', max( 1, $max_participants ) );
		}

		// Save require names checkbox.
		$require_names = isset( $_POST['_gmpb_require_names'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_gmpb_require_names', $require_names );

		// Save custom label.
		if ( isset( $_POST['_gmpb_custom_label'] ) ) {
			$custom_label = sanitize_text_field( wp_unslash( $_POST['_gmpb_custom_label'] ) );
			update_post_meta( $post_id, '_gmpb_custom_label', $custom_label );
		}
	}

	/**
	 * Add custom column to products list.
	 *
	 * @since 1.0.0
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_product_column( $columns ) {
		// Insert after 'product_type' column.
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			if ( 'product_type' === $key ) {
				$new_columns['gmpb_participants'] = __( 'Participants', 'gym-multi-participant-booking' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render custom product column.
	 *
	 * @since 1.0.0
	 * @param string $column  Column name.
	 * @param int    $post_id Product ID.
	 * @return void
	 */
	public function render_product_column( $column, $post_id ) {
		if ( 'gmpb_participants' === $column ) {
			$enabled = get_post_meta( $post_id, '_gmpb_enable_participants', true );

			if ( 'yes' === $enabled ) {
				$min = get_post_meta( $post_id, '_gmpb_min_participants', true );
				$max = get_post_meta( $post_id, '_gmpb_max_participants', true );

				echo '<span class="dashicons dashicons-yes-alt" style="color: #46b450;" title="' . esc_attr__( 'Enabled', 'gym-multi-participant-booking' ) . '"></span> ';
				echo '<small>' . esc_html( sprintf( '%d-%d', $min, $max ) ) . '</small>';
			} else {
				echo '<span class="dashicons dashicons-minus" style="color: #ddd;" title="' . esc_attr__( 'Disabled', 'gym-multi-participant-booking' ) . '"></span>';
			}
		}
	}

	/**
	 * Check if product has participants enabled.
	 *
	 * @since 1.0.0
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public function is_enabled_for_product( $product_id ) {
		return 'yes' === get_post_meta( $product_id, '_gmpb_enable_participants', true );
	}

	/**
	 * Get product participant settings.
	 *
	 * @since 1.0.0
	 * @param int $product_id Product ID.
	 * @return array
	 */
	public function get_product_settings( $product_id ) {
		return array(
			'enabled'       => $this->is_enabled_for_product( $product_id ),
			'min'           => (int) get_post_meta( $product_id, '_gmpb_min_participants', true ) ?: 1,
			'max'           => (int) get_post_meta( $product_id, '_gmpb_max_participants', true ) ?: 10,
			'require_names' => 'yes' === get_post_meta( $product_id, '_gmpb_require_names', true ),
			'custom_label'  => get_post_meta( $product_id, '_gmpb_custom_label', true ),
		);
	}
}
