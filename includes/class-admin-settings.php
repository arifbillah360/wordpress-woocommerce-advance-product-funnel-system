<?php
/**
 * Admin Settings Class
 *
 * Handles plugin settings and admin pages.
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
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		// Register settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add admin menu page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Gym Booking', 'gym-multi-participant-booking' ),
			__( 'Gym Booking', 'gym-multi-participant-booking' ),
			'manage_options',
			'gym-multi-participant-booking',
			array( $this, 'render_settings_page' ),
			'dashicons-groups',
			56
		);

		add_submenu_page(
			'gym-multi-participant-booking',
			__( 'Settings', 'gym-multi-participant-booking' ),
			__( 'Settings', 'gym-multi-participant-booking' ),
			'manage_options',
			'gym-multi-participant-booking',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_settings() {
		// General settings section.
		add_settings_section(
			'gmpb_general_section',
			__( 'General Settings', 'gym-multi-participant-booking' ),
			array( $this, 'render_general_section' ),
			'gym-multi-participant-booking'
		);

		// Email settings section.
		add_settings_section(
			'gmpb_email_section',
			__( 'Email Settings', 'gym-multi-participant-booking' ),
			array( $this, 'render_email_section' ),
			'gym-multi-participant-booking'
		);

		// Register settings.
		register_setting( 'gmpb_settings', 'gmpb_enable_emails' );
		register_setting( 'gmpb_settings', 'gmpb_default_min_participants' );
		register_setting( 'gmpb_settings', 'gmpb_default_max_participants' );
		register_setting( 'gmpb_settings', 'gmpb_email_from_name' );
		register_setting( 'gmpb_settings', 'gmpb_email_from_address' );

		// Add settings fields.
		$this->add_settings_fields();
	}

	/**
	 * Add settings fields.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function add_settings_fields() {
		// Enable emails.
		add_settings_field(
			'gmpb_enable_emails',
			__( 'Enable Confirmation Emails', 'gym-multi-participant-booking' ),
			array( $this, 'render_checkbox_field' ),
			'gym-multi-participant-booking',
			'gmpb_general_section',
			array(
				'label_for'   => 'gmpb_enable_emails',
				'description' => __( 'Send confirmation emails to participants when orders are completed.', 'gym-multi-participant-booking' ),
			)
		);

		// Default min participants.
		add_settings_field(
			'gmpb_default_min_participants',
			__( 'Default Minimum Participants', 'gym-multi-participant-booking' ),
			array( $this, 'render_number_field' ),
			'gym-multi-participant-booking',
			'gmpb_general_section',
			array(
				'label_for'   => 'gmpb_default_min_participants',
				'description' => __( 'Default minimum number of participants (can be overridden per product).', 'gym-multi-participant-booking' ),
				'min'         => 1,
				'max'         => 100,
			)
		);

		// Default max participants.
		add_settings_field(
			'gmpb_default_max_participants',
			__( 'Default Maximum Participants', 'gym-multi-participant-booking' ),
			array( $this, 'render_number_field' ),
			'gym-multi-participant-booking',
			'gmpb_general_section',
			array(
				'label_for'   => 'gmpb_default_max_participants',
				'description' => __( 'Default maximum number of participants (can be overridden per product).', 'gym-multi-participant-booking' ),
				'min'         => 1,
				'max'         => 100,
			)
		);

		// Email from name.
		add_settings_field(
			'gmpb_email_from_name',
			__( 'From Name', 'gym-multi-participant-booking' ),
			array( $this, 'render_text_field' ),
			'gym-multi-participant-booking',
			'gmpb_email_section',
			array(
				'label_for'   => 'gmpb_email_from_name',
				'description' => __( 'The name that appears in the "From" field of confirmation emails.', 'gym-multi-participant-booking' ),
			)
		);

		// Email from address.
		add_settings_field(
			'gmpb_email_from_address',
			__( 'From Email Address', 'gym-multi-participant-booking' ),
			array( $this, 'render_email_field' ),
			'gym-multi-participant-booking',
			'gmpb_email_section',
			array(
				'label_for'   => 'gmpb_email_from_address',
				'description' => __( 'The email address that appears in the "From" field of confirmation emails.', 'gym-multi-participant-booking' ),
			)
		);
	}

	/**
	 * Render general settings section.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_general_section() {
		echo '<p>' . esc_html__( 'Configure general plugin settings.', 'gym-multi-participant-booking' ) . '</p>';
	}

	/**
	 * Render email settings section.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_email_section() {
		echo '<p>' . esc_html__( 'Configure email notification settings.', 'gym-multi-participant-booking' ) . '</p>';
	}

	/**
	 * Render checkbox field.
	 *
	 * @since 1.0.0
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_checkbox_field( $args ) {
		$option = get_option( $args['label_for'], 'yes' );
		?>
		<label>
			<input type="checkbox" id="<?php echo esc_attr( $args['label_for'] ); ?>"
			       name="<?php echo esc_attr( $args['label_for'] ); ?>"
			       value="yes" <?php checked( $option, 'yes' ); ?>>
			<?php echo isset( $args['description'] ) ? esc_html( $args['description'] ) : ''; ?>
		</label>
		<?php
	}

	/**
	 * Render number field.
	 *
	 * @since 1.0.0
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_number_field( $args ) {
		$option = get_option( $args['label_for'], 10 );
		$min    = isset( $args['min'] ) ? absint( $args['min'] ) : 1;
		$max    = isset( $args['max'] ) ? absint( $args['max'] ) : 100;
		?>
		<input type="number" id="<?php echo esc_attr( $args['label_for'] ); ?>"
		       name="<?php echo esc_attr( $args['label_for'] ); ?>"
		       value="<?php echo esc_attr( $option ); ?>"
		       min="<?php echo esc_attr( $min ); ?>"
		       max="<?php echo esc_attr( $max ); ?>"
		       class="small-text">
		<?php if ( isset( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render text field.
	 *
	 * @since 1.0.0
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_text_field( $args ) {
		$option = get_option( $args['label_for'], '' );
		?>
		<input type="text" id="<?php echo esc_attr( $args['label_for'] ); ?>"
		       name="<?php echo esc_attr( $args['label_for'] ); ?>"
		       value="<?php echo esc_attr( $option ); ?>"
		       class="regular-text">
		<?php if ( isset( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render email field.
	 *
	 * @since 1.0.0
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_email_field( $args ) {
		$option = get_option( $args['label_for'], get_option( 'admin_email' ) );
		?>
		<input type="email" id="<?php echo esc_attr( $args['label_for'] ); ?>"
		       name="<?php echo esc_attr( $args['label_for'] ); ?>"
		       value="<?php echo esc_attr( $option ); ?>"
		       class="regular-text">
		<?php if ( isset( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render settings page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_settings_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Save settings message.
		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error(
				'gmpb_messages',
				'gmpb_message',
				__( 'Settings saved successfully.', 'gym-multi-participant-booking' ),
				'success'
			);
		}

		// Show error/update messages.
		settings_errors( 'gmpb_messages' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form action="options.php" method="post">
				<?php
				settings_fields( 'gmpb_settings' );
				do_settings_sections( 'gym-multi-participant-booking' );
				submit_button( __( 'Save Settings', 'gym-multi-participant-booking' ) );
				?>
			</form>

			<hr>

			<h2><?php esc_html_e( 'How to Use', 'gym-multi-participant-booking' ); ?></h2>
			<div class="gmpb-usage-guide">
				<ol>
					<li>
						<strong><?php esc_html_e( 'Edit a Product:', 'gym-multi-participant-booking' ); ?></strong>
						<?php esc_html_e( 'Go to Products and edit any product where you want to enable participant booking.', 'gym-multi-participant-booking' ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Enable Participants Tab:', 'gym-multi-participant-booking' ); ?></strong>
						<?php esc_html_e( 'In the Product Data section, click on the "Participants" tab.', 'gym-multi-participant-booking' ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Configure Settings:', 'gym-multi-participant-booking' ); ?></strong>
						<?php esc_html_e( 'Check "Enable participant booking" and set minimum/maximum participants.', 'gym-multi-participant-booking' ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Save Product:', 'gym-multi-participant-booking' ); ?></strong>
						<?php esc_html_e( 'Update the product to save your changes.', 'gym-multi-participant-booking' ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Test:', 'gym-multi-participant-booking' ); ?></strong>
						<?php esc_html_e( 'Visit the product page and you\'ll see participant fields appear on products where it\'s enabled.', 'gym-multi-participant-booking' ); ?>
					</li>
				</ol>

				<p>
					<strong><?php esc_html_e( 'Note:', 'gym-multi-participant-booking' ); ?></strong>
					<?php esc_html_e( 'Participant booking will ONLY appear on products where you explicitly enable it. It will not affect other products.', 'gym-multi-participant-booking' ); ?>
				</p>
			</div>
		</div>

		<style>
			.gmpb-usage-guide {
				background: #fff;
				border: 1px solid #ccd0d4;
				padding: 20px;
				margin-top: 15px;
				max-width: 800px;
			}
			.gmpb-usage-guide ol {
				margin-left: 20px;
			}
			.gmpb-usage-guide li {
				margin-bottom: 15px;
				line-height: 1.6;
			}
		</style>
		<?php
	}
}
