/**
 * Frontend JavaScript
 *
 * Handles participant field management on product pages.
 *
 * @package Gym_Multi_Participant_Booking
 * @since 1.0.0
 */

(function($) {
	'use strict';

	/**
	 * Participant Manager Object
	 */
	var GMPBParticipants = {
		/**
		 * Current participant count.
		 */
		count: 1,

		/**
		 * Minimum participants required.
		 */
		minParticipants: 1,

		/**
		 * Maximum participants allowed.
		 */
		maxParticipants: 10,

		/**
		 * Whether names are required.
		 */
		requireNames: false,

		/**
		 * Wrapper element.
		 */
		$wrapper: null,

		/**
		 * Initialize the participant manager.
		 */
		init: function() {
			var self = this;

			// Find wrapper.
			self.$wrapper = $('.gmpb-participant-fields-wrapper');

			if (self.$wrapper.length === 0) {
				return;
			}

			// Get settings from data attributes.
			self.minParticipants = parseInt(self.$wrapper.data('min'), 10) || 1;
			self.maxParticipants = parseInt(self.$wrapper.data('max'), 10) || 10;
			self.requireNames = self.$wrapper.data('require-names') === 1;

			// Bind events.
			self.bindEvents();

			// Initial update.
			self.updateButtons();
		},

		/**
		 * Bind event handlers.
		 */
		bindEvents: function() {
			var self = this;

			// Add participant button.
			$(document).on('click', '.gmpb-add-btn', function(e) {
				e.preventDefault();
				self.addParticipant();
			});

			// Remove participant button.
			$(document).on('click', '.gmpb-remove-btn', function(e) {
				e.preventDefault();
				var $row = $(this).closest('.gmpb-participant-row');
				self.removeParticipant($row);
			});

			// Real-time email validation.
			$(document).on('blur', '.gmpb-participant-email', function() {
				self.validateEmail($(this));
			});

			// Form validation before submit.
			$('form.cart').on('submit', function(e) {
				if (!self.validateForm()) {
					e.preventDefault();
					return false;
				}
			});
		},

		/**
		 * Add a new participant row.
		 */
		addParticipant: function() {
			var self = this;

			// Check max limit.
			if (self.count >= self.maxParticipants) {
				alert(gmpbData.i18n.maxReached);
				return;
			}

			self.count++;

			// Get template from first row.
			var $container = $('.gmpb-participants-container');
			var index = self.count - 1;
			var requiredAttr = self.requireNames ? 'required' : '';

			// Build new row HTML.
			var html = '<div class="gmpb-participant-row" data-index="' + index + '">' +
				'<h4 class="gmpb-participant-heading">' +
				'Participant ' + self.count +
				'</h4>' +
				'<div class="gmpb-field-group">' +
				'<label for="gmpb_participant_name_' + index + '" class="gmpb-label">' +
				'Name' +
				(self.requireNames ? ' <span class="required">*</span>' : '') +
				'</label>' +
				'<input type="text" class="gmpb-input gmpb-participant-name" ' +
				'name="gmpb_participants[' + index + '][name]" ' +
				'id="gmpb_participant_name_' + index + '" ' +
				'placeholder="Full Name" ' +
				requiredAttr + '>' +
				'</div>' +
				'<div class="gmpb-field-group">' +
				'<label for="gmpb_participant_email_' + index + '" class="gmpb-label">' +
				'Email Address <span class="required">*</span>' +
				'</label>' +
				'<input type="email" class="gmpb-input gmpb-participant-email" ' +
				'name="gmpb_participants[' + index + '][email]" ' +
				'id="gmpb_participant_email_' + index + '" ' +
				'placeholder="email@example.com" ' +
				'required>' +
				'</div>' +
				'<button type="button" class="button gmpb-remove-btn">' +
				gmpbData.i18n.removeParticipant +
				'</button>' +
				'</div>';

			// Append to container.
			$container.append(html);

			// Update counter.
			$('.gmpb-current-count').val(self.count);

			// Update buttons.
			self.updateButtons();

			// Scroll to new row.
			$('html, body').animate({
				scrollTop: $container.find('.gmpb-participant-row:last').offset().top - 100
			}, 500);
		},

		/**
		 * Remove a participant row.
		 *
		 * @param {jQuery} $row Row to remove.
		 */
		removeParticipant: function($row) {
			var self = this;

			// Check minimum limit.
			if (self.count <= self.minParticipants) {
				alert(gmpbData.i18n.minRequired);
				return;
			}

			// Remove row with animation.
			$row.fadeOut(300, function() {
				$(this).remove();
				self.count--;

				// Update counter.
				$('.gmpb-current-count').val(self.count);

				// Renumber rows.
				self.renumberRows();

				// Update buttons.
				self.updateButtons();
			});
		},

		/**
		 * Renumber participant rows.
		 */
		renumberRows: function() {
			$('.gmpb-participant-row').each(function(index) {
				var $row = $(this);
				var newIndex = index;

				// Update heading.
				$row.find('.gmpb-participant-heading').text(
					'Participant ' + (index + 1)
				);

				// Update data attribute.
				$row.attr('data-index', newIndex);

				// Update field names and IDs.
				$row.find('input').each(function() {
					var $input = $(this);
					var name = $input.attr('name');
					var id = $input.attr('id');

					if (name) {
						name = name.replace(/\[\d+\]/, '[' + newIndex + ']');
						$input.attr('name', name);
					}

					if (id) {
						id = id.replace(/_\d+$/, '_' + newIndex);
						$input.attr('id', id);
						$input.prev('label').attr('for', id);
					}
				});
			});
		},

		/**
		 * Update button states.
		 */
		updateButtons: function() {
			var self = this;

			// Update add button.
			var $addBtn = $('.gmpb-add-btn');
			if (self.count >= self.maxParticipants) {
				$addBtn.prop('disabled', true);
			} else {
				$addBtn.prop('disabled', false);
			}

			// Update remove buttons.
			var $removeButtons = $('.gmpb-remove-btn');
			if (self.count <= self.minParticipants) {
				$removeButtons.prop('disabled', true);
			} else {
				$removeButtons.prop('disabled', false);
			}
		},

		/**
		 * Validate email field.
		 *
		 * @param {jQuery} $input Email input field.
		 */
		validateEmail: function($input) {
			var email = $input.val().trim();

			if (email === '') {
				$input.removeClass('error');
				return true;
			}

			var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

			if (!emailRegex.test(email)) {
				$input.addClass('error');
				return false;
			} else {
				$input.removeClass('error');
				return true;
			}
		},

		/**
		 * Validate entire form.
		 *
		 * @return {boolean} Whether form is valid.
		 */
		validateForm: function() {
			var self = this;
			var isValid = true;
			var validCount = 0;

			// Validate each participant.
			$('.gmpb-participant-row').each(function() {
				var $row = $(this);
				var $name = $row.find('.gmpb-participant-name');
				var $email = $row.find('.gmpb-participant-email');

				var name = $name.val().trim();
				var email = $email.val().trim();

				// Check if email is provided.
				if (email === '') {
					return; // Skip this row.
				}

				// Validate email format.
				if (!self.validateEmail($email)) {
					isValid = false;
					alert(gmpbData.i18n.invalidEmail);
					$email.focus();
					return false; // Break loop.
				}

				// Check if name is required.
				if (self.requireNames && name === '') {
					isValid = false;
					alert(gmpbData.i18n.nameRequired);
					$name.focus();
					return false; // Break loop.
				}

				validCount++;
			});

			if (!isValid) {
				return false;
			}

			// Check minimum participants.
			if (validCount < self.minParticipants) {
				alert(gmpbData.i18n.minRequired);
				return false;
			}

			// Check maximum participants.
			if (validCount > self.maxParticipants) {
				alert(gmpbData.i18n.maxReached);
				return false;
			}

			return true;
		}
	};

	/**
	 * Initialize when document is ready.
	 */
	$(document).ready(function() {
		GMPBParticipants.init();
	});

})(jQuery);
