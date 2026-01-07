/**
 * Admin JavaScript
 *
 * Handles admin functionality for product settings.
 *
 * @package Gym_Multi_Participant_Booking
 * @since 1.0.0
 */

(function($) {
	'use strict';

	/**
	 * Admin Manager Object
	 */
	var GMPBAdmin = {
		/**
		 * Initialize admin functionality.
		 */
		init: function() {
			this.bindEvents();
			this.toggleFields();
		},

		/**
		 * Bind event handlers.
		 */
		bindEvents: function() {
			var self = this;

			// Toggle participant fields when checkbox changes.
			$('#_gmpb_enable_participants').on('change', function() {
				self.toggleFields();
			});

			// Validate min/max values.
			$('#_gmpb_min_participants, #_gmpb_max_participants').on('change', function() {
				self.validateMinMax();
			});
		},

		/**
		 * Toggle visibility of participant fields.
		 */
		toggleFields: function() {
			var $checkbox = $('#_gmpb_enable_participants');
			var $fields = $('#_gmpb_min_participants, #_gmpb_max_participants, #_gmpb_require_names, #_gmpb_custom_label').closest('.form-field');

			if ($checkbox.is(':checked')) {
				$fields.show();
			} else {
				$fields.hide();
			}
		},

		/**
		 * Validate min and max participants.
		 */
		validateMinMax: function() {
			var $min = $('#_gmpb_min_participants');
			var $max = $('#_gmpb_max_participants');

			var min = parseInt($min.val(), 10);
			var max = parseInt($max.val(), 10);

			// Ensure min is not greater than max.
			if (min > max) {
				alert(gmpbAdminData.i18n.error + '\n\nMinimum participants cannot be greater than maximum participants.');
				$min.val(max);
			}

			// Ensure values are at least 1.
			if (min < 1) {
				$min.val(1);
			}

			if (max < 1) {
				$max.val(1);
			}
		}
	};

	/**
	 * Initialize when document is ready.
	 */
	$(document).ready(function() {
		GMPBAdmin.init();
	});

})(jQuery);
