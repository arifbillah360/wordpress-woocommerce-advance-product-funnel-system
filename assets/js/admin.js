/**
 * Admin JavaScript for Gym Multi-Participant Booking
 *
 * Handles admin functionality including:
 * - Product settings toggles
 * - Resending participant emails from order page
 * - Bulk email operations
 * - CSV export of participants
 *
 * @package Gym_Multi_Participant_Booking
 * @since 1.0.0
 */

(function($) {
	'use strict';

	/**
	 * Product Settings Manager
	 *
	 * Handles product data tab functionality.
	 */
	var GMPBProductSettings = {
		/**
		 * Initialize product settings.
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
				alert('Minimum participants cannot be greater than maximum participants.');
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
	 * Email Resend Manager
	 *
	 * Handles resending participant emails from order admin page.
	 */
	var GMPBEmailResend = {
		/**
		 * Initialize email resend functionality.
		 */
		init: function() {
			this.bindEvents();
		},

		/**
		 * Bind event handlers.
		 */
		bindEvents: function() {
			var self = this;

			// Single email resend
			$(document).on('click', '.gmpb-resend-email', function(e) {
				e.preventDefault();
				self.handleResendEmail($(this));
			});

			// Bulk resend
			$(document).on('click', '.gmpb-bulk-resend', function(e) {
				e.preventDefault();
				self.handleBulkResend();
			});

			// CSV export
			$(document).on('click', '.gmpb-export-participants', function(e) {
				e.preventDefault();
				self.handleExportCSV($(this));
			});

			// Select all checkboxes
			$(document).on('change', '.gmpb-select-all', function() {
				$('.gmpb-participant-checkbox').prop('checked', $(this).prop('checked'));
			});
		},

		/**
		 * Handle single email resend.
		 *
		 * @param {jQuery} $button - Resend button element
		 */
		handleResendEmail: function($button) {
			var self = this;

			// Get data attributes
			var orderId = $button.data('order-id');
			var itemId = $button.data('item-id');
			var participantIndex = $button.data('participant-index');

			// Confirm before sending
			if (!confirm(gmpbAdmin.confirmMessage || 'Are you sure you want to resend this email?')) {
				return;
			}

			// Prepare data
			var data = {
				action: 'gmpb_resend_participant_email',
				nonce: gmpbAdmin.nonce,
				order_id: orderId,
				item_id: itemId,
				participant_index: participantIndex
			};

			// Send AJAX request
			$.ajax({
				url: gmpbAdmin.ajaxUrl,
				type: 'POST',
				data: data,
				beforeSend: function() {
					self.setButtonLoading($button);
				},
				success: function(response) {
					if (response.success) {
						self.handleResendSuccess($button, response.data);
					} else {
						self.handleResendError($button, response.data);
					}
				},
				error: function(xhr, status, error) {
					console.error('GMPB Resend Error:', error);
					self.handleResendError($button, {
						message: gmpbAdmin.errors.networkError || 'Network error occurred. Please try again.'
					});
				}
			});
		},

		/**
		 * Set button to loading state.
		 *
		 * @param {jQuery} $button - Button element
		 */
		setButtonLoading: function($button) {
			$button
				.prop('disabled', true)
				.addClass('gmpb-loading')
				.data('original-text', $button.text())
				.text(gmpbAdmin.sendingText || 'Sending...');
		},

		/**
		 * Handle successful email resend.
		 *
		 * @param {jQuery} $button - Button element
		 * @param {Object} data - Response data
		 */
		handleResendSuccess: function($button, data) {
			// Update button
			$button
				.prop('disabled', false)
				.removeClass('gmpb-loading')
				.addClass('gmpb-success')
				.text(gmpbAdmin.resentText || 'Resent!');

			// Update status indicator in the same row
			var $row = $button.closest('tr');
			var $statusCell = $row.find('.gmpb-email-status');

			if ($statusCell.length) {
				$statusCell.html('<span class="gmpb-email-sent" style="color: #46b450;">✓ Sent</span>');
			}

			// Show success notice
			this.showAdminNotice(data.message || 'Email sent successfully!', 'success');

			// Reset button after 3 seconds
			setTimeout(function() {
				$button
					.removeClass('gmpb-success')
					.text($button.data('original-text') || gmpbAdmin.resendText || 'Resend Email');
			}, 3000);
		},

		/**
		 * Handle email resend error.
		 *
		 * @param {jQuery} $button - Button element
		 * @param {Object} data - Response data
		 */
		handleResendError: function($button, data) {
			// Update button
			$button
				.prop('disabled', false)
				.removeClass('gmpb-loading')
				.addClass('gmpb-error')
				.text('Error!');

			// Show error notice
			this.showAdminNotice(
				data.message || gmpbAdmin.errors.genericError || 'Failed to resend email. Please try again.',
				'error'
			);

			// Reset button after 3 seconds
			setTimeout(function() {
				$button
					.removeClass('gmpb-error')
					.text($button.data('original-text') || gmpbAdmin.resendText || 'Resend Email');
			}, 3000);
		},

		/**
		 * Handle bulk email resend.
		 */
		handleBulkResend: function() {
			var self = this;
			var selectedParticipants = this.getSelectedParticipants();

			if (selectedParticipants.length === 0) {
				alert('Please select at least one participant to resend emails.');
				return;
			}

			if (!confirm('Resend emails to ' + selectedParticipants.length + ' participant(s)?')) {
				return;
			}

			// Show progress indicator
			var $progressBar = this.showProgressBar(selectedParticipants.length);
			var completed = 0;
			var successful = 0;
			var failed = 0;

			// Process each participant with staggered timing
			selectedParticipants.forEach(function(participant, index) {
				setTimeout(function() {
					var data = {
						action: 'gmpb_resend_participant_email',
						nonce: gmpbAdmin.nonce,
						order_id: participant.orderId,
						item_id: participant.itemId,
						participant_index: participant.participantIndex
					};

					$.ajax({
						url: gmpbAdmin.ajaxUrl,
						type: 'POST',
						data: data,
						success: function(response) {
							if (response.success) {
								successful++;
							} else {
								failed++;
							}
						},
						error: function() {
							failed++;
						},
						complete: function() {
							completed++;
							self.updateProgressBar($progressBar, completed, selectedParticipants.length);

							// Show final results
							if (completed === selectedParticipants.length) {
								self.showAdminNotice(
									'Bulk resend completed: ' + successful + ' successful, ' + failed + ' failed.',
									failed === 0 ? 'success' : 'warning'
								);
								$progressBar.fadeOut(500, function() {
									$(this).remove();
								});

								// Uncheck all
								$('.gmpb-participant-checkbox, .gmpb-select-all').prop('checked', false);
							}
						}
					});
				}, index * 500); // Stagger by 500ms to avoid server overload
			});
		},

		/**
		 * Get selected participants.
		 *
		 * @return {Array} Array of selected participant data
		 */
		getSelectedParticipants: function() {
			var participants = [];

			$('.gmpb-participant-checkbox:checked').each(function() {
				var $checkbox = $(this);
				participants.push({
					orderId: $checkbox.data('order-id'),
					itemId: $checkbox.data('item-id'),
					participantIndex: $checkbox.data('participant-index')
				});
			});

			return participants;
		},

		/**
		 * Show progress bar.
		 *
		 * @param {number} total - Total number of items
		 * @return {jQuery} Progress bar element
		 */
		showProgressBar: function(total) {
			var $progressBar = $('<div class="gmpb-progress-container" style="margin: 20px 0;"><div class="gmpb-progress-bar" style="background: #0073aa; height: 30px; width: 0%; transition: width 0.3s; border-radius: 3px; line-height: 30px; color: #fff; text-align: center; font-weight: 600;">0%</div></div>');

			// Insert after the bulk action button
			$('.gmpb-bulk-resend').after($progressBar);

			return $progressBar;
		},

		/**
		 * Update progress bar.
		 *
		 * @param {jQuery} $progressBar - Progress bar container
		 * @param {number} completed - Number of completed items
		 * @param {number} total - Total number of items
		 */
		updateProgressBar: function($progressBar, completed, total) {
			var percentage = Math.round((completed / total) * 100);
			var $bar = $progressBar.find('.gmpb-progress-bar');

			$bar.css('width', percentage + '%').text(percentage + '%');
		},

		/**
		 * Handle CSV export.
		 *
		 * @param {jQuery} $button - Export button
		 */
		handleExportCSV: function($button) {
			var orderId = $button.data('order-id');
			var itemId = $button.data('item-id');

			// Extract participant data from the table
			var participants = this.extractParticipantData(itemId);

			if (participants.length === 0) {
				alert('No participants found to export.');
				return;
			}

			// Generate CSV
			var csv = this.generateCSV(participants);

			// Download CSV
			this.downloadCSV(csv, 'participants-order-' + orderId + '-item-' + itemId + '.csv');

			this.showAdminNotice('Participant data exported successfully!', 'success');
		},

		/**
		 * Extract participant data from DOM.
		 *
		 * @param {number} itemId - Order item ID
		 * @return {Array} Participant data
		 */
		extractParticipantData: function(itemId) {
			var participants = [];
			var $table = $('.gmpb-participants-table[data-item-id="' + itemId + '"]');

			if (!$table.length) {
				// Try finding by proximity
				$table = $('.gmpb-participants-table').first();
			}

			$table.find('tbody tr').each(function(index) {
				var $row = $(this);
				participants.push({
					number: index + 1,
					name: $row.find('td:eq(1)').text().trim(),
					email: $row.find('td:eq(2)').text().trim(),
					phone: $row.find('td:eq(3)').text().trim() || 'N/A',
					emailSent: $row.find('td:eq(4)').text().includes('✓') ? 'Yes' : 'No'
				});
			});

			return participants;
		},

		/**
		 * Generate CSV from participant data.
		 *
		 * @param {Array} participants - Participant data
		 * @return {string} CSV content
		 */
		generateCSV: function(participants) {
			var headers = ['#', 'Name', 'Email', 'Phone', 'Email Sent'];
			var rows = [headers];

			participants.forEach(function(p) {
				rows.push([
					p.number,
					'"' + p.name.replace(/"/g, '""') + '"', // Escape quotes
					p.email,
					p.phone,
					p.emailSent
				]);
			});

			return rows.map(function(row) {
				return row.join(',');
			}).join('\n');
		},

		/**
		 * Download CSV file.
		 *
		 * @param {string} csv - CSV content
		 * @param {string} filename - File name
		 */
		downloadCSV: function(csv, filename) {
			var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
			var link = document.createElement('a');

			if (link.download !== undefined) {
				var url = URL.createObjectURL(blob);
				link.setAttribute('href', url);
				link.setAttribute('download', filename);
				link.style.visibility = 'hidden';
				document.body.appendChild(link);
				link.click();
				document.body.removeChild(link);
				URL.revokeObjectURL(url);
			}
		},

		/**
		 * Show WordPress admin notice.
		 *
		 * @param {string} message - Notice message
		 * @param {string} type - Notice type (success, error, warning, info)
		 */
		showAdminNotice: function(message, type) {
			type = type || 'info';

			var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');

			// Insert after page title
			var $titleArea = $('.wp-header-end');

			if ($titleArea.length) {
				$titleArea.after($notice);
			} else {
				// Fallback: insert at top of wrap
				$('.wrap').prepend($notice);
			}

			// Initialize dismiss button
			$notice.find('.notice-dismiss').on('click', function() {
				$notice.fadeOut(300, function() {
					$(this).remove();
				});
			});

			// Auto-dismiss after 5 seconds
			setTimeout(function() {
				$notice.fadeOut(300, function() {
					$(this).remove();
				});
			}, 5000);

			// Scroll to notice
			$('html, body').animate({
				scrollTop: $notice.offset().top - 50
			}, 300);
		}
	};

	/**
	 * Initialize when document is ready.
	 */
	$(document).ready(function() {
		// Initialize product settings
		if ($('#_gmpb_enable_participants').length) {
			GMPBProductSettings.init();
		}

		// Initialize email resend functionality
		if ($('.gmpb-resend-email').length || $('.gmpb-bulk-resend').length) {
			GMPBEmailResend.init();
		}
	});

})(jQuery);
