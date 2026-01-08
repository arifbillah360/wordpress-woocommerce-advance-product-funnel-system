/**
 * Frontend JavaScript for Gym Multi-Participant Booking
 *
 * Handles dynamic participant field generation and validation on product pages.
 * Uses jQuery for WordPress compatibility.
 *
 * @package Gym_Multi_Participant_Booking
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Wait for DOM ready
    $(document).ready(function() {
        console.log('GMPB: Frontend JS loaded');
        console.log('GMPB: Data available:', typeof gmpbData !== 'undefined');

        // Check if gmpbData is available
        if (typeof gmpbData === 'undefined') {
            console.error('GMPB: gmpbData not found - script may not be localized properly');
            return;
        }

        console.log('GMPB: Configuration:', gmpbData);

        // Get references to key elements
        const $formWrapper = $('.gmpb-participant-fields-wrapper');
        const $participantsContainer = $('.gmpb-participants-container');
        const $qtyInput = $('input.qty');
        const $cartForm = $('form.cart');

        // Check if participant form exists
        if ($formWrapper.length === 0) {
            console.log('GMPB: No participant form found on this page');
            return;
        }

        console.log('GMPB: Participant form found, initializing...');

        // Configuration
        const maxParticipants = parseInt(gmpbData.maxParticipants) || 10;
        const minParticipants = parseInt(gmpbData.minParticipants) || 1;

        // Current participant count
        let currentCount = 1;

        /**
         * Initialize participant fields
         */
        function init() {
            console.log('GMPB: Initializing with min:', minParticipants, 'max:', maxParticipants);

            // Get initial quantity
            const initialQty = parseInt($qtyInput.val()) || 1;
            console.log('GMPB: Initial quantity:', initialQty);

            // Update fields to match quantity
            updateParticipantFields(initialQty);

            // Bind events
            bindEvents();

            console.log('GMPB: Initialization complete');
        }

        /**
         * Bind all event handlers
         */
        function bindEvents() {
            // Add participant button
            $(document).on('click', '.gmpb-add-btn', function(e) {
                e.preventDefault();
                console.log('GMPB: Add button clicked');
                handleAddParticipant();
            });

            // Remove participant button
            $(document).on('click', '.gmpb-remove-btn', function(e) {
                e.preventDefault();
                const index = $(this).data('index');
                console.log('GMPB: Remove button clicked for index:', index);
                handleRemoveParticipant(index);
            });

            // Quantity input change
            $qtyInput.on('change input', function() {
                const qty = parseInt($(this).val()) || 1;
                console.log('GMPB: Quantity changed to:', qty);
                updateParticipantFields(qty);
            });

            // Form submission validation
            $cartForm.on('submit', function(e) {
                console.log('GMPB: Form submitted, validating...');
                if (!validateAllFields()) {
                    e.preventDefault();
                    console.log('GMPB: Validation failed, preventing submission');
                    scrollToFirstError();
                    return false;
                }
                console.log('GMPB: Validation passed');
            });

            // Real-time email validation
            $(document).on('blur', '.gmpb-participant-email', function() {
                validateEmailField($(this));
            });

            // Remove error styling on input
            $(document).on('input', '.gmpb-input', function() {
                $(this).removeClass('error');
                $(this).closest('.gmpb-participant-row').find('.gmpb-error-message').remove();
            });
        }

        /**
         * Handle add participant button click
         */
        function handleAddParticipant() {
            if (currentCount >= maxParticipants) {
                alert(gmpbData.i18n?.maxReached || 'Maximum number of participants reached.');
                return;
            }

            currentCount++;
            addParticipantRow(currentCount);

            // Update quantity input
            $qtyInput.val(currentCount).trigger('change');

            console.log('GMPB: Participant added. New count:', currentCount);
        }

        /**
         * Handle remove participant button click
         */
        function handleRemoveParticipant(index) {
            if (currentCount <= minParticipants) {
                alert(gmpbData.i18n?.minRequired || 'Minimum number of participants required.');
                return;
            }

            // Remove the participant row
            $(`.gmpb-participant-row[data-index="${index}"]`).fadeOut(300, function() {
                $(this).remove();

                // Renumber remaining participants
                renumberParticipants();
            });

            currentCount--;

            // Update quantity input
            $qtyInput.val(currentCount);

            console.log('GMPB: Participant removed. New count:', currentCount);
        }

        /**
         * Update participant fields based on quantity
         */
        function updateParticipantFields(quantity) {
            // Limit quantity
            if (quantity > maxParticipants) {
                quantity = maxParticipants;
                $qtyInput.val(quantity);
                alert(gmpbData.i18n?.maxReached || 'Maximum number of participants reached.');
            }

            if (quantity < minParticipants) {
                quantity = minParticipants;
                $qtyInput.val(quantity);
            }

            console.log('GMPB: Updating fields. Target quantity:', quantity, 'Current count:', currentCount);

            // Add rows if quantity increased
            while (currentCount < quantity) {
                currentCount++;
                addParticipantRow(currentCount);
            }

            // Remove rows if quantity decreased
            while (currentCount > quantity) {
                removeLastParticipant();
                currentCount--;
            }

            // Update current count display
            $('.gmpb-current-count').val(currentCount);
        }

        /**
         * Add a single participant row
         */
        function addParticipantRow(index) {
            const participantNumber = index;

            const html = `
                <div class="gmpb-participant-row" data-index="${index}" style="display:none;">
                    <h4 class="gmpb-participant-heading">
                        ${gmpbData.i18n?.participantLabel || 'Participant'} ${participantNumber}
                    </h4>

                    <div class="gmpb-field-group">
                        <label for="gmpb_participant_name_${index}" class="gmpb-label">
                            ${gmpbData.i18n?.nameLabel || 'Name'}
                            <span class="required">*</span>
                        </label>
                        <input type="text"
                               class="gmpb-input gmpb-participant-name"
                               name="gmpb_participants[${index}][name]"
                               id="gmpb_participant_name_${index}"
                               placeholder="${gmpbData.i18n?.namePlaceholder || 'Full Name'}"
                               required>
                    </div>

                    <div class="gmpb-field-group">
                        <label for="gmpb_participant_email_${index}" class="gmpb-label">
                            ${gmpbData.i18n?.emailLabel || 'Email Address'}
                            <span class="required">*</span>
                        </label>
                        <input type="email"
                               class="gmpb-input gmpb-participant-email"
                               name="gmpb_participants[${index}][email]"
                               id="gmpb_participant_email_${index}"
                               placeholder="${gmpbData.i18n?.emailPlaceholder || 'email@example.com'}"
                               required>
                    </div>

                    <div class="gmpb-field-group">
                        <label for="gmpb_participant_phone_${index}" class="gmpb-label">
                            ${gmpbData.i18n?.phoneLabel || 'Phone Number'}
                        </label>
                        <input type="tel"
                               class="gmpb-input gmpb-participant-phone"
                               name="gmpb_participants[${index}][phone]"
                               id="gmpb_participant_phone_${index}"
                               placeholder="${gmpbData.i18n?.phonePlaceholder || '+1 (555) 000-0000'}">
                    </div>

                    ${index > 1 ? `<button type="button" class="button gmpb-remove-btn" data-index="${index}">
                        <span class="dashicons dashicons-no"></span>
                        ${gmpbData.i18n?.removeParticipant || 'Remove Participant'}
                    </button>` : ''}
                </div>
            `;

            $participantsContainer.append(html);
            $(`.gmpb-participant-row[data-index="${index}"]`).fadeIn(300);

            console.log('GMPB: Added participant row for index:', index);
        }

        /**
         * Remove the last participant
         */
        function removeLastParticipant() {
            const $lastRow = $participantsContainer.children('.gmpb-participant-row').last();

            if ($lastRow.length) {
                $lastRow.fadeOut(300, function() {
                    $(this).remove();
                });
            }
        }

        /**
         * Renumber participants after removal
         */
        function renumberParticipants() {
            let newIndex = 0;

            $('.gmpb-participant-row').each(function() {
                const $row = $(this);
                const oldIndex = $row.data('index');

                // Update data attribute
                $row.attr('data-index', newIndex);
                $row.data('index', newIndex);

                // Update heading
                $row.find('.gmpb-participant-heading').text(
                    (gmpbData.i18n?.participantLabel || 'Participant') + ' ' + (newIndex + 1)
                );

                // Update input names and IDs
                $row.find('input').each(function() {
                    const $input = $(this);
                    const name = $input.attr('name');
                    const id = $input.attr('id');

                    if (name) {
                        $input.attr('name', name.replace(/\[\d+\]/, `[${newIndex}]`));
                    }
                    if (id) {
                        $input.attr('id', id.replace(/_\d+$/, `_${newIndex}`));
                    }
                });

                // Update labels
                $row.find('label').each(function() {
                    const $label = $(this);
                    const forAttr = $label.attr('for');

                    if (forAttr) {
                        $label.attr('for', forAttr.replace(/_\d+$/, `_${newIndex}`));
                    }
                });

                // Update remove button
                $row.find('.gmpb-remove-btn').attr('data-index', newIndex);

                // Hide remove button for first participant
                if (newIndex === 0) {
                    $row.find('.gmpb-remove-btn').remove();
                }

                newIndex++;
            });

            // Update current count
            currentCount = newIndex;
        }

        /**
         * Validate all participant fields
         */
        function validateAllFields() {
            let isValid = true;

            // Clear previous errors
            $('.gmpb-error-message').remove();
            $('.gmpb-input').removeClass('error');

            $('.gmpb-participant-row').each(function() {
                const $row = $(this);
                const index = $row.data('index');

                // Validate name
                const $nameInput = $row.find('.gmpb-participant-name');
                if (!$nameInput.val().trim()) {
                    showError($nameInput, gmpbData.i18n?.nameRequired || 'Please enter participant name');
                    isValid = false;
                }

                // Validate email
                const $emailInput = $row.find('.gmpb-participant-email');
                if (!$emailInput.val().trim()) {
                    showError($emailInput, gmpbData.i18n?.emailRequired || 'Please enter email address');
                    isValid = false;
                } else if (!isValidEmail($emailInput.val())) {
                    showError($emailInput, gmpbData.i18n?.invalidEmail || 'Please enter a valid email address');
                    isValid = false;
                }
            });

            // Check for duplicate emails if not allowed
            if (hasDuplicateEmails()) {
                alert(gmpbData.i18n?.duplicateEmail || 'Each participant must have a unique email address');
                isValid = false;
            }

            return isValid;
        }

        /**
         * Validate single email field
         */
        function validateEmailField($input) {
            const email = $input.val().trim();

            if (email && !isValidEmail(email)) {
                showError($input, gmpbData.i18n?.invalidEmail || 'Please enter a valid email address');
                return false;
            } else {
                $input.removeClass('error');
                $input.closest('.gmpb-field-group').find('.gmpb-error-message').remove();
                return true;
            }
        }

        /**
         * Show error message for a field
         */
        function showError($input, message) {
            $input.addClass('error');

            // Remove existing error message
            $input.closest('.gmpb-field-group').find('.gmpb-error-message').remove();

            // Add new error message
            $input.closest('.gmpb-field-group').append(
                `<div class="gmpb-error-message">${message}</div>`
            );
        }

        /**
         * Check if email is valid
         */
        function isValidEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }

        /**
         * Check for duplicate emails
         */
        function hasDuplicateEmails() {
            const emails = [];
            let hasDuplicate = false;

            $('.gmpb-participant-email').each(function() {
                const email = $(this).val().trim().toLowerCase();

                if (email) {
                    if (emails.includes(email)) {
                        hasDuplicate = true;
                        return false; // break loop
                    }
                    emails.push(email);
                }
            });

            return hasDuplicate;
        }

        /**
         * Scroll to first error
         */
        function scrollToFirstError() {
            const $firstError = $('.gmpb-input.error').first();

            if ($firstError.length) {
                $('html, body').animate({
                    scrollTop: $firstError.offset().top - 100
                }, 500);

                $firstError.focus();
            }
        }

        // Initialize on page load
        init();
    });

})(jQuery);
