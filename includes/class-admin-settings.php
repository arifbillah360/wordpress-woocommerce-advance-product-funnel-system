<?php
/**
 * Admin Settings Class
 *
 * Handles plugin settings and admin pages.
 * Comprehensive settings management using WordPress Settings API.
 *
 * @package Gym_Multi_Participant_Booking
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GMPB_Admin_Settings Class.
 *
 * Manages plugin settings and admin interface.
 *
 * @since 1.0.0
 */
class GMPB_Admin_Settings {

	/**
	 * Option name for storing all settings.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const OPTION_NAME = 'gmpb_settings';

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
		// Add admin menu.
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );

		// Register settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Admin notices.
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );

		// Enqueue admin styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_gmpb_reset_settings', array( $this, 'ajax_reset_settings' ) );
		add_action( 'wp_ajax_gmpb_test_email', array( $this, 'ajax_test_email' ) );
		add_action( 'wp_ajax_gmpb_export_settings', array( $this, 'ajax_export_settings' ) );
		add_action( 'wp_ajax_gmpb_import_settings', array( $this, 'ajax_import_settings' ) );
	}

	/**
	 * Add settings page under WooCommerce menu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_settings_page() {
		add_submenu_page(
			'woocommerce',
			__( 'Multi-Participant Booking Settings', 'gym-multi-participant-booking' ),
			__( 'Participant Booking', 'gym-multi-participant-booking' ),
			'manage_woocommerce',
			'gmpb-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_styles( $hook ) {
		if ( 'woocommerce_page_gmpb-settings' !== $hook ) {
			return;
		}

		// Inline styles for settings page.
		$custom_css = "
			.gmpb-settings-wrapper {
				display: flex;
				gap: 20px;
				margin-top: 20px;
			}
			.gmpb-settings-main {
				flex: 1;
				max-width: 800px;
			}
			.gmpb-settings-sidebar {
				width: 300px;
				flex-shrink: 0;
			}
			.gmpb-help-box,
			.gmpb-info-box {
				background: #fff;
				border: 1px solid #ccd0d4;
				box-shadow: 0 1px 1px rgba(0,0,0,.04);
				padding: 15px;
				margin-bottom: 20px;
			}
			.gmpb-help-box h3,
			.gmpb-info-box h3 {
				margin-top: 0;
				margin-bottom: 15px;
				font-size: 14px;
				font-weight: 600;
			}
			.gmpb-help-box ul {
				margin: 0;
				padding-left: 20px;
			}
			.gmpb-help-box li {
				margin-bottom: 8px;
			}
			.gmpb-info-box p {
				margin: 10px 0;
			}
			.gmpb-info-box .gmpb-stat {
				font-size: 24px;
				font-weight: bold;
				color: #2271b1;
			}
			.gmpb-action-buttons {
				margin-top: 20px;
				padding-top: 20px;
				border-top: 1px solid #ddd;
			}
			.gmpb-action-buttons .button {
				margin-right: 10px;
				margin-bottom: 10px;
			}
			.gmpb-test-email-section {
				background: #f0f6fc;
				border: 1px solid #c3e6ff;
				padding: 15px;
				margin-top: 20px;
				border-radius: 4px;
			}
			.gmpb-test-email-section input[type='email'] {
				margin: 10px 0;
			}
			@media (max-width: 1200px) {
				.gmpb-settings-wrapper {
					flex-direction: column;
				}
				.gmpb-settings-sidebar {
					width: 100%;
				}
			}
		";
		wp_add_inline_style( 'wp-admin', $custom_css );
	}

	/**
	 * Register plugin settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_settings() {
		// Register main settings option.
		register_setting(
			'gmpb_settings_group',
			self::OPTION_NAME,
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		// Register settings sections.
		$this->register_general_settings();
		$this->register_email_settings();
		$this->register_form_settings();
	}

	/**
	 * Register general settings section.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_general_settings() {
		add_settings_section(
			'gmpb_general_section',
			__( 'General Settings', 'gym-multi-participant-booking' ),
			function() {
				echo '<p>' . esc_html__( 'Configure general plugin behavior and defaults.', 'gym-multi-participant-booking' ) . '</p>';
			},
			'gmpb-settings'
		);

		// Default max participants.
		add_settings_field(
			'default_max_participants',
			__( 'Default Max Participants Per Booking', 'gym-multi-participant-booking' ),
			array( $this, 'render_number_field' ),
			'gmpb-settings',
			'gmpb_general_section',
			array(
				'id'          => 'default_max_participants',
				'default'     => 10,
				'min'         => 1,
				'max'         => 100,
				'description' => __( 'Maximum number of participants per order (can be overridden per product).', 'gym-multi-participant-booking' ),
			)
		);

		// Require phone number.
		add_settings_field(
			'require_phone_number',
			__( 'Require Phone Number', 'gym-multi-participant-booking' ),
			array( $this, 'render_checkbox_field' ),
			'gmpb-settings',
			'gmpb_general_section',
			array(
				'id'          => 'require_phone_number',
				'default'     => 'no',
				'label'       => __( 'Make phone number field mandatory for all participants', 'gym-multi-participant-booking' ),
				'description' => '',
			)
		);

		// Allow duplicate emails.
		add_settings_field(
			'allow_duplicate_emails',
			__( 'Allow Duplicate Emails', 'gym-multi-participant-booking' ),
			array( $this, 'render_checkbox_field' ),
			'gmpb-settings',
			'gmpb_general_section',
			array(
				'id'          => 'allow_duplicate_emails',
				'default'     => 'no',
				'label'       => __( 'Allow the same email address for multiple participants in one order', 'gym-multi-participant-booking' ),
				'description' => '',
			)
		);

		// Enable email logging.
		add_settings_field(
			'enable_email_logging',
			__( 'Enable Email Logging', 'gym-multi-participant-booking' ),
			array( $this, 'render_checkbox_field' ),
			'gmpb-settings',
			'gmpb_general_section',
			array(
				'id'          => 'enable_email_logging',
				'default'     => 'yes',
				'label'       => __( 'Log all email sending activities in order notes', 'gym-multi-participant-booking' ),
				'description' => '',
			)
		);
	}

	/**
	 * Register email settings section.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_email_settings() {
		add_settings_section(
			'gmpb_email_section',
			__( 'Email Settings', 'gym-multi-participant-booking' ),
			function() {
				echo '<p>' . esc_html__( 'Configure email notifications sent to participants.', 'gym-multi-participant-booking' ) . '</p>';
				echo '<p><em>' . esc_html__( 'Available placeholders: {product_name}, {order_id}, {order_number}, {order_date}, {gym_name}, {participant_name}', 'gym-multi-participant-booking' ) . '</em></p>';
			},
			'gmpb-settings'
		);

		// Email subject.
		add_settings_field(
			'email_subject',
			__( 'Email Subject', 'gym-multi-participant-booking' ),
			array( $this, 'render_text_field' ),
			'gmpb-settings',
			'gmpb_email_section',
			array(
				'id'          => 'email_subject',
				'default'     => __( 'Your Booking Confirmation - {product_name}', 'gym-multi-participant-booking' ),
				'description' => __( 'Subject line for participant emails. Use placeholders for dynamic content.', 'gym-multi-participant-booking' ),
			)
		);

		// Email from name.
		add_settings_field(
			'email_from_name',
			__( 'From Name', 'gym-multi-participant-booking' ),
			array( $this, 'render_text_field' ),
			'gmpb-settings',
			'gmpb_email_section',
			array(
				'id'          => 'email_from_name',
				'default'     => get_bloginfo( 'name' ),
				'description' => __( 'Name shown in the email "From" field.', 'gym-multi-participant-booking' ),
			)
		);

		// Email from email.
		add_settings_field(
			'email_from_email',
			__( 'From Email', 'gym-multi-participant-booking' ),
			array( $this, 'render_email_field' ),
			'gmpb-settings',
			'gmpb_email_section',
			array(
				'id'          => 'email_from_email',
				'default'     => get_option( 'admin_email' ),
				'description' => __( 'Email address shown in the "From" field.', 'gym-multi-participant-booking' ),
			)
		);

		// Gym name.
		add_settings_field(
			'gym_name',
			__( 'Gym/Business Name', 'gym-multi-participant-booking' ),
			array( $this, 'render_text_field' ),
			'gmpb-settings',
			'gmpb_email_section',
			array(
				'id'          => 'gym_name',
				'default'     => get_bloginfo( 'name' ),
				'description' => __( 'Your gym or business name (used in email templates).', 'gym-multi-participant-booking' ),
			)
		);

		// Gym address.
		add_settings_field(
			'gym_address',
			__( 'Gym Address', 'gym-multi-participant-booking' ),
			array( $this, 'render_textarea_field' ),
			'gmpb-settings',
			'gmpb_email_section',
			array(
				'id'          => 'gym_address',
				'default'     => '',
				'rows'        => 3,
				'description' => __( 'Full address including city and zip code.', 'gym-multi-participant-booking' ),
			)
		);

		// Gym contact email.
		add_settings_field(
			'gym_contact_email',
			__( 'Contact Email', 'gym-multi-participant-booking' ),
			array( $this, 'render_email_field' ),
			'gmpb-settings',
			'gmpb_email_section',
			array(
				'id'          => 'gym_contact_email',
				'default'     => get_option( 'admin_email' ),
				'description' => __( 'Support email address for participants to contact.', 'gym-multi-participant-booking' ),
			)
		);

		// Gym contact phone.
		add_settings_field(
			'gym_contact_phone',
			__( 'Contact Phone', 'gym-multi-participant-booking' ),
			array( $this, 'render_text_field' ),
			'gmpb-settings',
			'gmpb_email_section',
			array(
				'id'          => 'gym_contact_phone',
				'default'     => '',
				'description' => __( 'Support phone number for participants.', 'gym-multi-participant-booking' ),
			)
		);

		// Email additional notes.
		add_settings_field(
			'email_additional_notes',
			__( 'Additional Email Notes', 'gym-multi-participant-booking' ),
			array( $this, 'render_textarea_field' ),
			'gmpb-settings',
			'gmpb_email_section',
			array(
				'id'          => 'email_additional_notes',
				'default'     => '',
				'rows'        => 5,
				'description' => __( 'Extra information to include in confirmation emails (e.g., parking info, what to bring).', 'gym-multi-participant-booking' ),
			)
		);
	}

	/**
	 * Register form settings section.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_form_settings() {
		add_settings_section(
			'gmpb_form_section',
			__( 'Form Settings', 'gym-multi-participant-booking' ),
			function() {
				echo '<p>' . esc_html__( 'Customize the participant form fields and labels shown on product pages.', 'gym-multi-participant-booking' ) . '</p>';
			},
			'gmpb-settings'
		);

		// Form title.
		add_settings_field(
			'form_title',
			__( 'Form Title', 'gym-multi-participant-booking' ),
			array( $this, 'render_text_field' ),
			'gmpb-settings',
			'gmpb_form_section',
			array(
				'id'          => 'form_title',
				'default'     => __( 'Enter Participant Details', 'gym-multi-participant-booking' ),
				'description' => __( 'Title displayed above the participant form.', 'gym-multi-participant-booking' ),
			)
		);

		// Form description.
		add_settings_field(
			'form_description',
			__( 'Form Description', 'gym-multi-participant-booking' ),
			array( $this, 'render_textarea_field' ),
			'gmpb-settings',
			'gmpb_form_section',
			array(
				'id'          => 'form_description',
				'default'     => __( 'Please provide details for each participant.', 'gym-multi-participant-booking' ),
				'rows'        => 3,
				'description' => __( 'Instructions shown to customers above the form.', 'gym-multi-participant-booking' ),
			)
		);

		// Label participant name.
		add_settings_field(
			'label_participant_name',
			__( 'Name Field Label', 'gym-multi-participant-booking' ),
			array( $this, 'render_text_field' ),
			'gmpb-settings',
			'gmpb_form_section',
			array(
				'id'          => 'label_participant_name',
				'default'     => __( 'Full Name', 'gym-multi-participant-booking' ),
				'description' => __( 'Label for participant name field.', 'gym-multi-participant-booking' ),
			)
		);

		// Label participant email.
		add_settings_field(
			'label_participant_email',
			__( 'Email Field Label', 'gym-multi-participant-booking' ),
			array( $this, 'render_text_field' ),
			'gmpb-settings',
			'gmpb_form_section',
			array(
				'id'          => 'label_participant_email',
				'default'     => __( 'Email Address', 'gym-multi-participant-booking' ),
				'description' => __( 'Label for participant email field.', 'gym-multi-participant-booking' ),
			)
		);

		// Label participant phone.
		add_settings_field(
			'label_participant_phone',
			__( 'Phone Field Label', 'gym-multi-participant-booking' ),
			array( $this, 'render_text_field' ),
			'gmpb-settings',
			'gmpb_form_section',
			array(
				'id'          => 'label_participant_phone',
				'default'     => __( 'Phone Number', 'gym-multi-participant-booking' ),
				'description' => __( 'Label for participant phone field (if enabled).', 'gym-multi-participant-booking' ),
			)
		);

		// Required field indicator.
		add_settings_field(
			'required_field_indicator',
			__( 'Required Field Indicator', 'gym-multi-participant-booking' ),
			array( $this, 'render_text_field' ),
			'gmpb-settings',
			'gmpb_form_section',
			array(
				'id'          => 'required_field_indicator',
				'default'     => '*',
				'description' => __( 'Symbol shown for required fields.', 'gym-multi-participant-booking' ),
			)
		);
	}

	/**
	 * Render text field.
	 *
	 * @since 1.0.0
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_text_field( $args ) {
		$value = $this->get_setting( $args['id'], $args['default'] );
		printf(
			'<input type="text" id="%s" name="%s[%s]" value="%s" class="regular-text" />',
			esc_attr( $args['id'] ),
			esc_attr( self::OPTION_NAME ),
			esc_attr( $args['id'] ),
			esc_attr( $value )
		);
		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
		}
	}

	/**
	 * Render textarea field.
	 *
	 * @since 1.0.0
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_textarea_field( $args ) {
		$value = $this->get_setting( $args['id'], $args['default'] );
		$rows  = isset( $args['rows'] ) ? absint( $args['rows'] ) : 5;
		printf(
			'<textarea id="%s" name="%s[%s]" rows="%d" class="large-text">%s</textarea>',
			esc_attr( $args['id'] ),
			esc_attr( self::OPTION_NAME ),
			esc_attr( $args['id'] ),
			$rows,
			esc_textarea( $value )
		);
		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
		}
	}

	/**
	 * Render checkbox field.
	 *
	 * @since 1.0.0
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_checkbox_field( $args ) {
		$value = $this->get_setting( $args['id'], $args['default'] );
		printf(
			'<input type="checkbox" id="%s" name="%s[%s]" value="yes" %s />',
			esc_attr( $args['id'] ),
			esc_attr( self::OPTION_NAME ),
			esc_attr( $args['id'] ),
			checked( $value, 'yes', false )
		);
		printf(
			'<label for="%s">%s</label>',
			esc_attr( $args['id'] ),
			esc_html( $args['label'] )
		);
		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
		}
	}

	/**
	 * Render number field.
	 *
	 * @since 1.0.0
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_number_field( $args ) {
		$value = $this->get_setting( $args['id'], $args['default'] );
		$min   = isset( $args['min'] ) ? absint( $args['min'] ) : 1;
		$max   = isset( $args['max'] ) ? absint( $args['max'] ) : 100;
		$step  = isset( $args['step'] ) ? absint( $args['step'] ) : 1;

		printf(
			'<input type="number" id="%s" name="%s[%s]" value="%s" min="%d" max="%d" step="%d" class="small-text" />',
			esc_attr( $args['id'] ),
			esc_attr( self::OPTION_NAME ),
			esc_attr( $args['id'] ),
			esc_attr( $value ),
			$min,
			$max,
			$step
		);
		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
		}
	}

	/**
	 * Render email field.
	 *
	 * @since 1.0.0
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_email_field( $args ) {
		$value = $this->get_setting( $args['id'], $args['default'] );
		printf(
			'<input type="email" id="%s" name="%s[%s]" value="%s" class="regular-text" />',
			esc_attr( $args['id'] ),
			esc_attr( self::OPTION_NAME ),
			esc_attr( $args['id'] ),
			esc_attr( $value )
		);
		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
		}
	}

	/**
	 * Sanitize settings.
	 *
	 * @since 1.0.0
	 * @param array $input Raw input data.
	 * @return array Sanitized data.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		// Text fields.
		$text_fields = array(
			'email_subject',
			'email_from_name',
			'gym_name',
			'gym_contact_phone',
			'form_title',
			'label_participant_name',
			'label_participant_email',
			'label_participant_phone',
			'required_field_indicator',
		);
		foreach ( $text_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$sanitized[ $field ] = sanitize_text_field( $input[ $field ] );
			}
		}

		// Textarea fields.
		$textarea_fields = array(
			'gym_address',
			'email_additional_notes',
			'form_description',
		);
		foreach ( $textarea_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$sanitized[ $field ] = sanitize_textarea_field( $input[ $field ] );
			}
		}

		// Email fields.
		$email_fields = array(
			'email_from_email',
			'gym_contact_email',
		);
		foreach ( $email_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$sanitized[ $field ] = sanitize_email( $input[ $field ] );
			}
		}

		// Checkbox fields.
		$checkbox_fields = array(
			'require_phone_number',
			'allow_duplicate_emails',
			'enable_email_logging',
		);
		foreach ( $checkbox_fields as $field ) {
			$sanitized[ $field ] = isset( $input[ $field ] ) && 'yes' === $input[ $field ] ? 'yes' : 'no';
		}

		// Number fields.
		if ( isset( $input['default_max_participants'] ) ) {
			$sanitized['default_max_participants'] = max( 1, min( 100, absint( $input['default_max_participants'] ) ) );
		}

		return apply_filters( 'gmpb_sanitize_settings', $sanitized, $input );
	}

	/**
	 * Get setting value.
	 *
	 * @since 1.0.0
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed Setting value.
	 */
	public function get_setting( $key, $default = '' ) {
		$settings = get_option( self::OPTION_NAME, array() );
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Show admin notices.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function show_admin_notices() {
		// Only on settings page.
		$screen = get_current_screen();
		if ( ! $screen || 'woocommerce_page_gmpb-settings' !== $screen->id ) {
			return;
		}

		// Show success message after save.
		if ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Settings saved successfully!', 'gym-multi-participant-booking' ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Render settings page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_settings_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'gym-multi-participant-booking' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="gmpb-settings-wrapper">
				<div class="gmpb-settings-main">
					<form method="post" action="options.php">
						<?php
						settings_fields( 'gmpb_settings_group' );
						do_settings_sections( 'gmpb-settings' );
						submit_button();
						?>
					</form>

					<!-- Action Buttons -->
					<div class="gmpb-action-buttons">
						<h3><?php esc_html_e( 'Additional Actions', 'gym-multi-participant-booking' ); ?></h3>

						<button type="button" class="button button-secondary" id="gmpb-reset-settings">
							<?php esc_html_e( 'Reset to Defaults', 'gym-multi-participant-booking' ); ?>
						</button>

						<button type="button" class="button button-secondary" id="gmpb-export-settings">
							<?php esc_html_e( 'Export Settings', 'gym-multi-participant-booking' ); ?>
						</button>

						<label for="gmpb-import-file" class="button button-secondary">
							<?php esc_html_e( 'Import Settings', 'gym-multi-participant-booking' ); ?>
						</label>
						<input type="file" id="gmpb-import-file" accept=".json" style="display: none;">
					</div>

					<!-- Test Email Section -->
					<div class="gmpb-test-email-section">
						<h3><?php esc_html_e( 'Test Email Configuration', 'gym-multi-participant-booking' ); ?></h3>
						<p><?php esc_html_e( 'Send a test email to verify your email settings are working correctly.', 'gym-multi-participant-booking' ); ?></p>
						<input type="email" id="gmpb-test-email-address" class="regular-text"
						       placeholder="<?php esc_attr_e( 'Enter email address', 'gym-multi-participant-booking' ); ?>"
						       value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>">
						<button type="button" class="button button-primary" id="gmpb-send-test-email">
							<?php esc_html_e( 'Send Test Email', 'gym-multi-participant-booking' ); ?>
						</button>
						<div id="gmpb-test-email-result"></div>
					</div>
				</div>

				<!-- Sidebar -->
				<div class="gmpb-settings-sidebar">
					<div class="gmpb-info-box">
						<h3><?php esc_html_e( 'Quick Stats', 'gym-multi-participant-booking' ); ?></h3>
						<p>
							<strong><?php esc_html_e( 'Products with Participant Booking:', 'gym-multi-participant-booking' ); ?></strong><br>
							<span class="gmpb-stat"><?php echo count( GMPB_Product_Settings::get_enabled_products() ); ?></span>
						</p>
						<p>
							<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>" class="button button-small">
								<?php esc_html_e( 'Manage Products', 'gym-multi-participant-booking' ); ?> &rarr;
							</a>
						</p>
					</div>

					<div class="gmpb-help-box">
						<h3><?php esc_html_e( 'Need Help?', 'gym-multi-participant-booking' ); ?></h3>
						<ul>
							<li><a href="#" target="_blank"><?php esc_html_e( 'Documentation', 'gym-multi-participant-booking' ); ?></a></li>
							<li><a href="#" target="_blank"><?php esc_html_e( 'Video Tutorials', 'gym-multi-participant-booking' ); ?></a></li>
							<li><a href="#" target="_blank"><?php esc_html_e( 'Support Forum', 'gym-multi-participant-booking' ); ?></a></li>
						</ul>
					</div>

					<div class="gmpb-help-box">
						<h3><?php esc_html_e( 'How to Use', 'gym-multi-participant-booking' ); ?></h3>
						<ol style="padding-left: 20px; font-size: 13px; line-height: 1.6;">
							<li><?php esc_html_e( 'Go to Products and edit any product', 'gym-multi-participant-booking' ); ?></li>
							<li><?php esc_html_e( 'Click on the "Participants" tab', 'gym-multi-participant-booking' ); ?></li>
							<li><?php esc_html_e( 'Enable participant booking', 'gym-multi-participant-booking' ); ?></li>
							<li><?php esc_html_e( 'Set min/max participants', 'gym-multi-participant-booking' ); ?></li>
							<li><?php esc_html_e( 'Save the product', 'gym-multi-participant-booking' ); ?></li>
						</ol>
					</div>
				</div>
			</div>
		</div>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Reset settings.
			$('#gmpb-reset-settings').on('click', function(e) {
				e.preventDefault();
				if (!confirm('<?php echo esc_js( __( 'Are you sure you want to reset all settings to defaults? This cannot be undone.', 'gym-multi-participant-booking' ) ); ?>')) {
					return;
				}

				$.post(ajaxurl, {
					action: 'gmpb_reset_settings',
					nonce: '<?php echo wp_create_nonce( 'gmpb_reset_settings' ); ?>'
				}, function(response) {
					if (response.success) {
						location.reload();
					} else {
						alert(response.data.message || '<?php echo esc_js( __( 'Error resetting settings.', 'gym-multi-participant-booking' ) ); ?>');
					}
				});
			});

			// Export settings.
			$('#gmpb-export-settings').on('click', function(e) {
				e.preventDefault();

				$.post(ajaxurl, {
					action: 'gmpb_export_settings',
					nonce: '<?php echo wp_create_nonce( 'gmpb_export_settings' ); ?>'
				}, function(response) {
					if (response.success) {
						var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(response.data, null, 2));
						var downloadAnchorNode = document.createElement('a');
						downloadAnchorNode.setAttribute("href", dataStr);
						downloadAnchorNode.setAttribute("download", "gmpb-settings-" + Date.now() + ".json");
						document.body.appendChild(downloadAnchorNode);
						downloadAnchorNode.click();
						downloadAnchorNode.remove();
					}
				});
			});

			// Import settings.
			$('#gmpb-import-file').on('change', function(e) {
				var file = e.target.files[0];
				if (!file) return;

				var reader = new FileReader();
				reader.onload = function(e) {
					try {
						var settings = JSON.parse(e.target.result);

						if (!confirm('<?php echo esc_js( __( 'Import these settings? Current settings will be overwritten.', 'gym-multi-participant-booking' ) ); ?>')) {
							return;
						}

						$.post(ajaxurl, {
							action: 'gmpb_import_settings',
							nonce: '<?php echo wp_create_nonce( 'gmpb_import_settings' ); ?>',
							settings: JSON.stringify(settings)
						}, function(response) {
							if (response.success) {
								location.reload();
							} else {
								alert(response.data.message || '<?php echo esc_js( __( 'Error importing settings.', 'gym-multi-participant-booking' ) ); ?>');
							}
						});
					} catch(err) {
						alert('<?php echo esc_js( __( 'Invalid JSON file.', 'gym-multi-participant-booking' ) ); ?>');
					}
				};
				reader.readAsText(file);
			});

			// Send test email.
			$('#gmpb-send-test-email').on('click', function(e) {
				e.preventDefault();
				var email = $('#gmpb-test-email-address').val();
				var $button = $(this);
				var $result = $('#gmpb-test-email-result');

				if (!email) {
					alert('<?php echo esc_js( __( 'Please enter an email address.', 'gym-multi-participant-booking' ) ); ?>');
					return;
				}

				$button.prop('disabled', true).text('<?php echo esc_js( __( 'Sending...', 'gym-multi-participant-booking' ) ); ?>');
				$result.html('');

				$.post(ajaxurl, {
					action: 'gmpb_test_email',
					nonce: '<?php echo wp_create_nonce( 'gmpb_test_email' ); ?>',
					email: email
				}, function(response) {
					$button.prop('disabled', false).text('<?php echo esc_js( __( 'Send Test Email', 'gym-multi-participant-booking' ) ); ?>');

					if (response.success) {
						$result.html('<p style="color: green; margin-top: 10px;">✓ ' + response.data.message + '</p>');
					} else {
						$result.html('<p style="color: red; margin-top: 10px;">✗ ' + (response.data.message || '<?php echo esc_js( __( 'Failed to send test email.', 'gym-multi-participant-booking' ) ); ?>') + '</p>');
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * AJAX: Reset settings to defaults.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_reset_settings() {
		check_ajax_referer( 'gmpb_reset_settings', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'gym-multi-participant-booking' ) ) );
		}

		delete_option( self::OPTION_NAME );
		wp_send_json_success( array( 'message' => __( 'Settings reset successfully.', 'gym-multi-participant-booking' ) ) );
	}

	/**
	 * AJAX: Export settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_export_settings() {
		check_ajax_referer( 'gmpb_export_settings', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'gym-multi-participant-booking' ) ) );
		}

		$settings = get_option( self::OPTION_NAME, array() );
		wp_send_json_success( $settings );
	}

	/**
	 * AJAX: Import settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_import_settings() {
		check_ajax_referer( 'gmpb_import_settings', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'gym-multi-participant-booking' ) ) );
		}

		$settings_json = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : '';
		$settings      = json_decode( $settings_json, true );

		if ( ! is_array( $settings ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid settings data.', 'gym-multi-participant-booking' ) ) );
		}

		// Sanitize before saving.
		$sanitized = $this->sanitize_settings( $settings );
		update_option( self::OPTION_NAME, $sanitized );

		wp_send_json_success( array( 'message' => __( 'Settings imported successfully.', 'gym-multi-participant-booking' ) ) );
	}

	/**
	 * AJAX: Send test email.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_test_email() {
		check_ajax_referer( 'gmpb_test_email', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'gym-multi-participant-booking' ) ) );
		}

		$email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';

		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid email address.', 'gym-multi-participant-booking' ) ) );
		}

		$subject = $this->get_setting( 'email_subject', __( 'Test Email - Gym Multi-Participant Booking', 'gym-multi-participant-booking' ) );
		$gym_name = $this->get_setting( 'gym_name', get_bloginfo( 'name' ) );

		$message = '<html><body>';
		$message .= '<h2>' . esc_html__( 'Test Email from Gym Multi-Participant Booking', 'gym-multi-participant-booking' ) . '</h2>';
		$message .= '<p>' . esc_html__( 'This is a test email to verify your email configuration is working correctly.', 'gym-multi-participant-booking' ) . '</p>';
		$message .= '<p><strong>' . esc_html__( 'Gym Name:', 'gym-multi-participant-booking' ) . '</strong> ' . esc_html( $gym_name ) . '</p>';
		$message .= '<p><strong>' . esc_html__( 'From Name:', 'gym-multi-participant-booking' ) . '</strong> ' . esc_html( $this->get_setting( 'email_from_name', get_bloginfo( 'name' ) ) ) . '</p>';
		$message .= '<p><strong>' . esc_html__( 'From Email:', 'gym-multi-participant-booking' ) . '</strong> ' . esc_html( $this->get_setting( 'email_from_email', get_option( 'admin_email' ) ) ) . '</p>';
		$message .= '<p>' . esc_html__( 'If you received this email, your settings are configured correctly!', 'gym-multi-participant-booking' ) . '</p>';
		$message .= '</body></html>';

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $this->get_setting( 'email_from_name', get_bloginfo( 'name' ) ) . ' <' . $this->get_setting( 'email_from_email', get_option( 'admin_email' ) ) . '>',
		);

		$sent = wp_mail( $email, $subject, $message, $headers );

		if ( $sent ) {
			wp_send_json_success( array( 'message' => sprintf( __( 'Test email sent successfully to %s', 'gym-multi-participant-booking' ), $email ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to send test email. Please check your email configuration.', 'gym-multi-participant-booking' ) ) );
		}
	}
}
