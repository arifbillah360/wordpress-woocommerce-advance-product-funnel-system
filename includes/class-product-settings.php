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
	 * Meta key for enable/disable setting.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const META_KEY_ENABLE = '_gmpb_enable_participants';

	/**
	 * Meta key for minimum participants.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const META_KEY_MIN = '_gmpb_min_participants';

	/**
	 * Meta key for maximum participants.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const META_KEY_MAX = '_gmpb_max_participants';

	/**
	 * Meta key for require names setting.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const META_KEY_REQUIRE_NAMES = '_gmpb_require_names';

	/**
	 * Meta key for custom label.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const META_KEY_CUSTOM_LABEL = '_gmpb_custom_label';

	/**
	 * Transient key for enabled products cache.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const TRANSIENT_ENABLED_PRODUCTS = 'gmpb_enabled_products';

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

		// Add bulk actions.
		add_filter( 'bulk_actions-edit-product', array( $this, 'add_bulk_action' ) );
		add_filter( 'handle_bulk_actions-edit-product', array( $this, 'handle_bulk_action' ), 10, 3 );

		// Display bulk action admin notice.
		add_action( 'admin_notices', array( $this, 'bulk_action_admin_notice' ) );

		// Clear cache when product is saved.
		add_action( 'woocommerce_process_product_meta', array( $this, 'clear_enabled_products_cache' ), 20 );
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
		$enable_participants = get_post_meta( $post->ID, self::META_KEY_ENABLE, true );
		$min_participants    = get_post_meta( $post->ID, self::META_KEY_MIN, true );
		$max_participants    = get_post_meta( $post->ID, self::META_KEY_MAX, true );
		$require_names       = get_post_meta( $post->ID, self::META_KEY_REQUIRE_NAMES, true );
		$custom_label        = get_post_meta( $post->ID, self::META_KEY_CUSTOM_LABEL, true );

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
						'id'            => self::META_KEY_ENABLE,
						'label'         => __( 'Enable participant booking', 'gym-multi-participant-booking' ),
						'description'   => __( 'Allow customers to add multiple participants for this product.', 'gym-multi-participant-booking' ),
						'value'         => $enable_participants,
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => self::META_KEY_MIN,
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
						'id'                => self::META_KEY_MAX,
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
						'id'            => self::META_KEY_REQUIRE_NAMES,
						'label'         => __( 'Require participant names', 'gym-multi-participant-booking' ),
						'description'   => __( 'Make participant names mandatory (emails are always required).', 'gym-multi-participant-booking' ),
						'value'         => $require_names,
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'          => self::META_KEY_CUSTOM_LABEL,
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
		$enable_participants = isset( $_POST[ self::META_KEY_ENABLE ] ) ? 'yes' : 'no';
		update_post_meta( $post_id, self::META_KEY_ENABLE, $enable_participants );

		// Save min participants.
		if ( isset( $_POST[ self::META_KEY_MIN ] ) ) {
			$min_participants = absint( $_POST[ self::META_KEY_MIN ] );
			update_post_meta( $post_id, self::META_KEY_MIN, max( 1, $min_participants ) );
		}

		// Save max participants.
		if ( isset( $_POST[ self::META_KEY_MAX ] ) ) {
			$max_participants = absint( $_POST[ self::META_KEY_MAX ] );
			update_post_meta( $post_id, self::META_KEY_MAX, max( 1, $max_participants ) );
		}

		// Save require names checkbox.
		$require_names = isset( $_POST[ self::META_KEY_REQUIRE_NAMES ] ) ? 'yes' : 'no';
		update_post_meta( $post_id, self::META_KEY_REQUIRE_NAMES, $require_names );

		// Save custom label.
		if ( isset( $_POST[ self::META_KEY_CUSTOM_LABEL ] ) ) {
			$custom_label = sanitize_text_field( wp_unslash( $_POST[ self::META_KEY_CUSTOM_LABEL ] ) );
			update_post_meta( $post_id, self::META_KEY_CUSTOM_LABEL, $custom_label );
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
			if ( self::is_participant_booking_enabled( $post_id ) ) {
				$min = get_post_meta( $post_id, self::META_KEY_MIN, true );
				$max = get_post_meta( $post_id, self::META_KEY_MAX, true );

				echo '<span class="dashicons dashicons-yes-alt" style="color: #46b450;" title="' . esc_attr__( 'Enabled', 'gym-multi-participant-booking' ) . '"></span> ';
				echo '<small>' . esc_html( sprintf( '%d-%d', $min, $max ) ) . '</small>';
			} else {
				echo '<span class="dashicons dashicons-minus" style="color: #ddd;" title="' . esc_attr__( 'Disabled', 'gym-multi-participant-booking' ) . '"></span>';
			}
		}
	}

	/**
	 * Add bulk actions to products list.
	 *
	 * @since 1.0.0
	 * @param array $bulk_actions Existing bulk actions.
	 * @return array Modified bulk actions.
	 */
	public function add_bulk_action( $bulk_actions ) {
		$bulk_actions['gmpb_enable_participants']  = __( 'Enable Participant Booking', 'gym-multi-participant-booking' );
		$bulk_actions['gmpb_disable_participants'] = __( 'Disable Participant Booking', 'gym-multi-participant-booking' );

		return $bulk_actions;
	}

	/**
	 * Handle bulk actions.
	 *
	 * @since 1.0.0
	 * @param string $redirect_to Redirect URL.
	 * @param string $action      Bulk action name.
	 * @param array  $post_ids    Array of post IDs.
	 * @return string Modified redirect URL.
	 */
	public function handle_bulk_action( $redirect_to, $action, $post_ids ) {
		// Check if our action.
		if ( 'gmpb_enable_participants' !== $action && 'gmpb_disable_participants' !== $action ) {
			return $redirect_to;
		}

		// Check user capabilities.
		if ( ! current_user_can( 'edit_products' ) ) {
			return $redirect_to;
		}

		$value = ( 'gmpb_enable_participants' === $action ) ? 'yes' : 'no';
		$count = 0;

		// Update each product.
		foreach ( $post_ids as $post_id ) {
			// Verify it's a product.
			if ( 'product' === get_post_type( $post_id ) ) {
				update_post_meta( $post_id, self::META_KEY_ENABLE, $value );
				$count++;
			}
		}

		// Clear cache.
		delete_transient( self::TRANSIENT_ENABLED_PRODUCTS );

		// Add count to redirect URL.
		$redirect_to = add_query_arg(
			array(
				'gmpb_bulk_action' => $action,
				'gmpb_count'       => $count,
			),
			$redirect_to
		);

		return $redirect_to;
	}

	/**
	 * Display admin notice for bulk actions.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function bulk_action_admin_notice() {
		// Check if we have a bulk action result.
		if ( ! isset( $_GET['gmpb_bulk_action'] ) || ! isset( $_GET['gmpb_count'] ) ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_GET['gmpb_bulk_action'] ) );
		$count  = absint( $_GET['gmpb_count'] );

		if ( $count === 0 ) {
			return;
		}

		$message = '';

		if ( 'gmpb_enable_participants' === $action ) {
			$message = sprintf(
				/* translators: %d: number of products */
				_n(
					'Participant booking enabled for %d product.',
					'Participant booking enabled for %d products.',
					$count,
					'gym-multi-participant-booking'
				),
				$count
			);
		} elseif ( 'gmpb_disable_participants' === $action ) {
			$message = sprintf(
				/* translators: %d: number of products */
				_n(
					'Participant booking disabled for %d product.',
					'Participant booking disabled for %d products.',
					$count,
					'gym-multi-participant-booking'
				),
				$count
			);
		}

		if ( $message ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo esc_html( $message ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Clear enabled products cache.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function clear_enabled_products_cache() {
		delete_transient( self::TRANSIENT_ENABLED_PRODUCTS );
	}

	/**
	 * Check if participant booking is enabled for a product (STATIC).
	 *
	 * @since 1.0.0
	 * @param int $product_id Product ID.
	 * @return bool True if enabled, false otherwise.
	 */
	public static function is_participant_booking_enabled( $product_id ) {
		return 'yes' === get_post_meta( $product_id, self::META_KEY_ENABLE, true );
	}

	/**
	 * Get all products with participant booking enabled.
	 *
	 * @since 1.0.0
	 * @param bool $use_cache Whether to use cached results.
	 * @return array Array of product IDs.
	 */
	public static function get_enabled_products( $use_cache = true ) {
		// Try to get from cache.
		if ( $use_cache ) {
			$cached = get_transient( self::TRANSIENT_ENABLED_PRODUCTS );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		// Query products.
		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'   => self::META_KEY_ENABLE,
					'value' => 'yes',
				),
			),
		);

		$query       = new WP_Query( $args );
		$product_ids = $query->posts;

		// Cache for 1 hour.
		set_transient( self::TRANSIENT_ENABLED_PRODUCTS, $product_ids, HOUR_IN_SECONDS );

		return $product_ids;
	}

	/**
	 * Get product-specific setting with fallback to global setting.
	 *
	 * @since 1.0.0
	 * @param int    $product_id   Product ID.
	 * @param string $setting_key  Setting key.
	 * @param mixed  $default      Default value.
	 * @return mixed Setting value.
	 */
	public static function get_product_setting( $product_id, $setting_key, $default = '' ) {
		// Map setting keys to meta keys.
		$meta_key_map = array(
			'enabled'       => self::META_KEY_ENABLE,
			'min'           => self::META_KEY_MIN,
			'max'           => self::META_KEY_MAX,
			'require_names' => self::META_KEY_REQUIRE_NAMES,
			'custom_label'  => self::META_KEY_CUSTOM_LABEL,
		);

		// Map setting keys to global option keys.
		$option_key_map = array(
			'min' => 'gmpb_default_min_participants',
			'max' => 'gmpb_default_max_participants',
		);

		// Check if valid setting key.
		if ( ! isset( $meta_key_map[ $setting_key ] ) ) {
			return $default;
		}

		// Get product meta.
		$meta_key   = $meta_key_map[ $setting_key ];
		$meta_value = get_post_meta( $product_id, $meta_key, true );

		// If product has specific setting, return it.
		if ( ! empty( $meta_value ) ) {
			// Convert to boolean for checkbox fields.
			if ( in_array( $setting_key, array( 'enabled', 'require_names' ), true ) ) {
				return 'yes' === $meta_value;
			}

			return $meta_value;
		}

		// Fall back to global setting if available.
		if ( isset( $option_key_map[ $setting_key ] ) ) {
			$option_value = get_option( $option_key_map[ $setting_key ], $default );
			return $option_value;
		}

		return $default;
	}

	/**
	 * Get product participant settings (STATIC).
	 *
	 * Returns all participant booking settings for a specific product.
	 * Can be called statically without instantiating the class.
	 *
	 * @since 1.0.0
	 * @param int $product_id Product ID.
	 * @return array Array of settings: enabled, min, max, require_names, custom_label.
	 */
	public static function get_product_settings( $product_id ) {
		// Validate product ID.
		if ( empty( $product_id ) || ! is_numeric( $product_id ) ) {
			return array(
				'enabled'       => false,
				'min'           => 1,
				'max'           => 10,
				'require_names' => false,
				'custom_label'  => '',
			);
		}

		return array(
			'enabled'       => self::is_participant_booking_enabled( $product_id ),
			'min'           => (int) self::get_product_setting( $product_id, 'min', 1 ),
			'max'           => (int) self::get_product_setting( $product_id, 'max', 10 ),
			'require_names' => self::get_product_setting( $product_id, 'require_names', false ),
			'custom_label'  => self::get_product_setting( $product_id, 'custom_label', '' ),
		);
	}

	/**
	 * Check if product has participants enabled (non-static version).
	 *
	 * @since 1.0.0
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public function is_enabled_for_product( $product_id ) {
		return self::is_participant_booking_enabled( $product_id );
	}
}
