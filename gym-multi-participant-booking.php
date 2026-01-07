<?php
/**
 * Plugin Name:       Gym Multi-Participant Booking
 * Plugin URI:        https://github.com/arifbillah360/wordpress-woocommerce-advance-product-funnel-system
 * Description:       Allows customers to book multiple gym slots for specific products and automatically sends confirmation emails to each participant
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Gym Booking Team
 * Author URI:        https://github.com/arifbillah360
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gym-multi-participant-booking
 * Domain Path:       /languages
 * WC requires at least: 5.0
 * WC tested up to:   8.5
 *
 * @package Gym_Multi_Participant_Booking
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define plugin constants.
 */
if ( ! defined( 'GMPB_VERSION' ) ) {
	define( 'GMPB_VERSION', '1.0.0' );
}

if ( ! defined( 'GMPB_PLUGIN_DIR' ) ) {
	define( 'GMPB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'GMPB_PLUGIN_URL' ) ) {
	define( 'GMPB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'GMPB_PLUGIN_BASENAME' ) ) {
	define( 'GMPB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

/**
 * Main Plugin Class - Singleton Pattern
 *
 * @since 1.0.0
 */
class Gym_Multi_Participant_Booking {

	/**
	 * Single instance of the class.
	 *
	 * @since 1.0.0
	 * @var Gym_Multi_Participant_Booking
	 */
	private static $instance = null;

	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * Plugin name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $plugin_name = 'gym-multi-participant-booking';

	/**
	 * Participant Manager instance.
	 *
	 * @since 1.0.0
	 * @var GMPB_Participant_Manager
	 */
	public $participant_manager;

	/**
	 * Email Handler instance.
	 *
	 * @since 1.0.0
	 * @var GMPB_Email_Handler
	 */
	public $email_handler;

	/**
	 * Admin Settings instance.
	 *
	 * @since 1.0.0
	 * @var GMPB_Admin_Settings
	 */
	public $admin_settings;

	/**
	 * Product Settings instance.
	 *
	 * @since 1.0.0
	 * @var GMPB_Product_Settings
	 */
	public $product_settings;

	/**
	 * Get single instance of the class.
	 *
	 * @since 1.0.0
	 * @return Gym_Multi_Participant_Booking
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor - Private to enforce singleton.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		// Check if WooCommerce is active.
		if ( ! $this->is_woocommerce_active() ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		// Include required files.
		$this->includes();

		// Initialize hooks.
		$this->init_hooks();

		// Load text domain for translations.
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Check if WooCommerce is active.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function is_woocommerce_active() {
		// Check if WooCommerce class exists.
		if ( class_exists( 'WooCommerce' ) ) {
			return true;
		}

		// Check if WooCommerce is active in plugins list.
		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) );
		}

		return in_array( 'woocommerce/woocommerce.php', $active_plugins, true ) ||
		       array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );
	}

	/**
	 * Display admin notice if WooCommerce is not active.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function woocommerce_missing_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
					/* translators: %s: WooCommerce plugin name */
					esc_html__( '%1$s requires %2$s to be installed and activated.', 'gym-multi-participant-booking' ),
					'<strong>' . esc_html__( 'Gym Multi-Participant Booking', 'gym-multi-participant-booking' ) . '</strong>',
					'<strong>' . esc_html__( 'WooCommerce', 'gym-multi-participant-booking' ) . '</strong>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Include required files.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function includes() {
		// Include class files.
		require_once GMPB_PLUGIN_DIR . 'includes/class-participant-manager.php';
		require_once GMPB_PLUGIN_DIR . 'includes/class-email-handler.php';
		require_once GMPB_PLUGIN_DIR . 'includes/class-admin-settings.php';
		require_once GMPB_PLUGIN_DIR . 'includes/class-product-settings.php';

		// Initialize component classes.
		$this->participant_manager = new GMPB_Participant_Manager();
		$this->email_handler       = new GMPB_Email_Handler();
		$this->product_settings    = new GMPB_Product_Settings();

		// Initialize admin settings only in admin area.
		if ( is_admin() ) {
			$this->admin_settings = new GMPB_Admin_Settings();
		}
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init_hooks() {
		// Enqueue scripts and styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add settings link in plugins list.
		add_filter( 'plugin_action_links_' . GMPB_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );
	}

	/**
	 * Load plugin text domain for translations.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'gym-multi-participant-booking',
			false,
			dirname( GMPB_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Enqueue frontend scripts and styles.
	 * Only load on single product pages where the feature is enabled.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_frontend_scripts() {
		// Only load on single product pages.
		if ( ! is_product() ) {
			return;
		}

		global $post;

		// Check if multi-participant booking is enabled for this product.
		$enabled = get_post_meta( $post->ID, '_gmpb_enable_participants', true );

		if ( 'yes' !== $enabled ) {
			return;
		}

		// Enqueue CSS.
		wp_enqueue_style(
			'gmpb-frontend-styles',
			GMPB_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			GMPB_VERSION,
			'all'
		);

		// Enqueue JavaScript.
		wp_enqueue_script(
			'gmpb-frontend-scripts',
			GMPB_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			GMPB_VERSION,
			true
		);

		// Localize script with data.
		wp_localize_script(
			'gmpb-frontend-scripts',
			'gmpbData',
			array(
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'gmpb_frontend_nonce' ),
				'minParticipants' => (int) get_post_meta( $post->ID, '_gmpb_min_participants', true ) ?: 1,
				'maxParticipants' => (int) get_post_meta( $post->ID, '_gmpb_max_participants', true ) ?: 10,
				'i18n'           => array(
					'addParticipant'    => __( 'Add Participant', 'gym-multi-participant-booking' ),
					'removeParticipant' => __( 'Remove Participant', 'gym-multi-participant-booking' ),
					'emailRequired'     => __( 'Email is required for each participant.', 'gym-multi-participant-booking' ),
					'maxReached'        => __( 'Maximum number of participants reached.', 'gym-multi-participant-booking' ),
					'minRequired'       => __( 'Minimum number of participants required.', 'gym-multi-participant-booking' ),
					'invalidEmail'      => __( 'Please enter a valid email address.', 'gym-multi-participant-booking' ),
				),
			)
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 * Only load on product edit page and plugin settings page.
	 *
	 * @since 1.0.0
	 * @param string $hook_suffix The current admin page.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook_suffix ) {
		// Get current screen.
		$screen = get_current_screen();

		// Only load on specific pages.
		$allowed_pages = array(
			'post.php',           // Product edit page.
			'post-new.php',       // New product page.
			'toplevel_page_gym-multi-participant-booking', // Plugin settings page.
		);

		// Check if we're on an allowed page.
		if ( ! in_array( $hook_suffix, $allowed_pages, true ) ) {
			return;
		}

		// Additionally check if we're editing a product.
		if ( in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			if ( ! $screen || 'product' !== $screen->post_type ) {
				return;
			}
		}

		// Enqueue CSS.
		wp_enqueue_style(
			'gmpb-admin-styles',
			GMPB_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			GMPB_VERSION,
			'all'
		);

		// Enqueue JavaScript.
		wp_enqueue_script(
			'gmpb-admin-scripts',
			GMPB_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			GMPB_VERSION,
			true
		);

		// Localize script with data.
		wp_localize_script(
			'gmpb-admin-scripts',
			'gmpbAdminData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'gmpb_admin_nonce' ),
				'i18n'    => array(
					'confirmDelete' => __( 'Are you sure you want to delete this?', 'gym-multi-participant-booking' ),
					'error'         => __( 'An error occurred. Please try again.', 'gym-multi-participant-booking' ),
					'saved'         => __( 'Settings saved successfully.', 'gym-multi-participant-booking' ),
				),
			)
		);
	}

	/**
	 * Add settings link to plugin actions.
	 *
	 * @since 1.0.0
	 * @param array $links Existing plugin action links.
	 * @return array Modified plugin action links.
	 */
	public function add_settings_link( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=gym-multi-participant-booking' ) ),
			esc_html__( 'Settings', 'gym-multi-participant-booking' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Get plugin version.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Get plugin name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}
}

/**
 * Plugin activation hook.
 *
 * @since 1.0.0
 * @return void
 */
function gmpb_activate_plugin() {
	// Check WordPress version compatibility.
	global $wp_version;
	$required_wp_version = '5.8';

	if ( version_compare( $wp_version, $required_wp_version, '<' ) ) {
		deactivate_plugins( GMPB_PLUGIN_BASENAME );
		wp_die(
			sprintf(
				/* translators: %s: required WordPress version */
				esc_html__( 'Gym Multi-Participant Booking requires WordPress version %s or higher.', 'gym-multi-participant-booking' ),
				$required_wp_version
			),
			esc_html__( 'Plugin Activation Error', 'gym-multi-participant-booking' ),
			array( 'back_link' => true )
		);
	}

	// Check if WooCommerce is active.
	if ( ! class_exists( 'WooCommerce' ) ) {
		deactivate_plugins( GMPB_PLUGIN_BASENAME );
		wp_die(
			sprintf(
				/* translators: %s: WooCommerce plugin name */
				esc_html__( 'Gym Multi-Participant Booking requires %s to be installed and activated.', 'gym-multi-participant-booking' ),
				'<strong>' . esc_html__( 'WooCommerce', 'gym-multi-participant-booking' ) . '</strong>'
			),
			esc_html__( 'Plugin Activation Error', 'gym-multi-participant-booking' ),
			array( 'back_link' => true )
		);
	}

	// Create default options.
	add_option( 'gmpb_version', GMPB_VERSION );
	add_option( 'gmpb_enable_emails', 'yes' );
	add_option( 'gmpb_default_min_participants', 1 );
	add_option( 'gmpb_default_max_participants', 10 );
	add_option( 'gmpb_email_from_name', get_bloginfo( 'name' ) );
	add_option( 'gmpb_email_from_address', get_option( 'admin_email' ) );

	// Flush rewrite rules.
	flush_rewrite_rules();

	// Trigger activation action.
	do_action( 'gmpb_activated' );
}
register_activation_hook( __FILE__, 'gmpb_activate_plugin' );

/**
 * Plugin deactivation hook.
 *
 * @since 1.0.0
 * @return void
 */
function gmpb_deactivate_plugin() {
	// Clear any scheduled events.
	$timestamp = wp_next_scheduled( 'gmpb_daily_cleanup' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'gmpb_daily_cleanup' );
	}

	// Flush rewrite rules.
	flush_rewrite_rules();

	// Trigger deactivation action.
	do_action( 'gmpb_deactivated' );
}
register_deactivation_hook( __FILE__, 'gmpb_deactivate_plugin' );

/**
 * Initialize the plugin.
 *
 * @since 1.0.0
 * @return Gym_Multi_Participant_Booking
 */
function gmpb_init() {
	return Gym_Multi_Participant_Booking::get_instance();
}
add_action( 'plugins_loaded', 'gmpb_init', 10 );
