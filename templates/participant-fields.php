<?php
/**
 * Participant Fields Template
 *
 * Displays participant input fields on product page.
 *
 * @package Gym_Multi_Participant_Booking
 * @since 1.0.0
 *
 * @var int    $min_participants Minimum participants required.
 * @var int    $max_participants Maximum participants allowed.
 * @var bool   $require_names    Whether names are required.
 * @var string $custom_label     Custom section label.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="gmpb-participant-fields-wrapper"
     data-min="<?php echo esc_attr( $min_participants ); ?>"
     data-max="<?php echo esc_attr( $max_participants ); ?>"
     data-require-names="<?php echo $require_names ? '1' : '0'; ?>">

	<h3 class="gmpb-section-title">
		<?php echo esc_html( $custom_label ); ?>
	</h3>

	<p class="gmpb-description">
		<?php
		printf(
			/* translators: 1: minimum participants, 2: maximum participants */
			esc_html__( 'Please provide information for participants (minimum: %1$d, maximum: %2$d)', 'gym-multi-participant-booking' ),
			$min_participants,
			$max_participants
		);
		?>
	</p>

	<div class="gmpb-participants-container">
		<!-- Participant row template (first participant) -->
		<div class="gmpb-participant-row" data-index="0">
			<h4 class="gmpb-participant-heading">
				<?php esc_html_e( 'Participant 1', 'gym-multi-participant-booking' ); ?>
			</h4>

			<div class="gmpb-field-group">
				<label for="gmpb_participant_name_0" class="gmpb-label">
					<?php esc_html_e( 'Name', 'gym-multi-participant-booking' ); ?>
					<?php if ( $require_names ) : ?>
						<span class="required">*</span>
					<?php endif; ?>
				</label>
				<input type="text"
				       class="gmpb-input gmpb-participant-name"
				       name="gmpb_participants[0][name]"
				       id="gmpb_participant_name_0"
				       placeholder="<?php esc_attr_e( 'Full Name', 'gym-multi-participant-booking' ); ?>"
				       <?php echo $require_names ? 'required' : ''; ?>>
			</div>

			<div class="gmpb-field-group">
				<label for="gmpb_participant_email_0" class="gmpb-label">
					<?php esc_html_e( 'Email Address', 'gym-multi-participant-booking' ); ?>
					<span class="required">*</span>
				</label>
				<input type="email"
				       class="gmpb-input gmpb-participant-email"
				       name="gmpb_participants[0][email]"
				       id="gmpb_participant_email_0"
				       placeholder="<?php esc_attr_e( 'email@example.com', 'gym-multi-participant-booking' ); ?>"
				       required>
			</div>

			<?php if ( $min_participants < 2 ) : ?>
				<button type="button" class="button gmpb-remove-btn">
					<?php esc_html_e( 'Remove Participant', 'gym-multi-participant-booking' ); ?>
				</button>
			<?php endif; ?>
		</div>
	</div>

	<button type="button" class="button gmpb-add-btn">
		<span class="dashicons dashicons-plus-alt"></span>
		<?php esc_html_e( 'Add Another Participant', 'gym-multi-participant-booking' ); ?>
	</button>

	<input type="hidden" class="gmpb-current-count" value="1">
</div>
