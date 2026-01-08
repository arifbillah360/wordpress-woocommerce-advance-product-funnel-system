/**
 * Frontend JavaScript for Gym Multi-Participant Booking
 *
 * Handles dynamic participant field generation and validation on product pages.
 * ONLY runs on products where participant booking is enabled.
 *
 * @package Gym_Multi_Participant_Booking
 * @since 1.0.0
 */

(function() {
    'use strict';

    /**
     * Participant Manager Class
     *
     * Manages dynamic participant field generation based on product quantity.
     */
    class ParticipantManager {
        /**
         * Constructor
         */
        constructor() {
            // Check if participant form exists on page
            this.formWrapper = document.querySelector('.gmpb-participant-form');

            if (!this.formWrapper) {
                return; // Exit if form not present - product doesn't have booking enabled
            }

            // Get references to key elements
            this.quantityInput = document.querySelector('input.qty');
            this.participantsContainer = document.querySelector('.gmpb-participants-container');
            this.cartForm = document.querySelector('form.cart');

            // Ensure required elements exist
            if (!this.quantityInput || !this.participantsContainer || !this.cartForm) {
                console.warn('GMPB: Required form elements not found');
                return;
            }

            // Get configuration from localized data
            this.config = {
                maxParticipants: parseInt(gmpbData?.maxParticipants) || 10,
                minParticipants: parseInt(gmpbData?.minParticipants) || 1,
                requirePhone: gmpbData?.requirePhone || false,
                allowDuplicateEmails: gmpbData?.allowDuplicateEmails || false,
                labels: gmpbData?.labels || {
                    name: 'Full Name',
                    email: 'Email Address',
                    phone: 'Phone Number'
                },
                errors: gmpbData?.errors || {
                    nameRequired: 'Please enter participant name',
                    emailRequired: 'Please enter email address',
                    emailInvalid: 'Please enter a valid email address',
                    phoneRequired: 'Please enter phone number',
                    duplicateEmail: 'Each participant must have a unique email address',
                    minParticipants: 'Minimum participants required',
                    maxParticipants: 'Maximum participants exceeded'
                }
            };

            // State management
            this.currentQuantity = 0;
            this.debounceTimer = null;

            // Initialize
            this.init();
        }

        /**
         * Initialize the participant manager
         */
        init() {
            console.log('GMPB: Initializing participant manager');

            // Bind event handlers
            this.bindEvents();

            // Trigger initial field generation based on current quantity
            const initialQuantity = parseInt(this.quantityInput.value) || this.config.minParticipants;
            this.updateParticipantFields(initialQuantity);
        }

        /**
         * Bind event handlers
         */
        bindEvents() {
            // Listen to quantity changes with debouncing
            this.quantityInput.addEventListener('change', (e) => {
                this.handleQuantityChange(e);
            });

            this.quantityInput.addEventListener('input', (e) => {
                this.handleQuantityChangeDebounced(e);
            });

            // Form validation before submit
            this.cartForm.addEventListener('submit', (e) => {
                if (!this.validateParticipantFields()) {
                    e.preventDefault();
                    this.scrollToFirstError();
                    return false;
                }
            });

            // Real-time email validation on blur
            this.participantsContainer.addEventListener('blur', (e) => {
                if (e.target.matches('input[type="email"]')) {
                    this.validateEmailField(e.target);
                }
            }, true);

            // Auto-focus next field on input
            this.participantsContainer.addEventListener('input', (e) => {
                if (e.target.matches('input[name^="participant_"]')) {
                    this.handleAutoFocus(e.target);
                }
            });

            // Remove error styling on input
            this.participantsContainer.addEventListener('input', (e) => {
                if (e.target.classList.contains('error')) {
                    e.target.classList.remove('error');
                }
            });
        }

        /**
         * Handle quantity change with debouncing
         *
         * @param {Event} e - Input event
         */
        handleQuantityChangeDebounced(e) {
            clearTimeout(this.debounceTimer);

            this.debounceTimer = setTimeout(() => {
                this.handleQuantityChange(e);
            }, 300);
        }

        /**
         * Handle quantity change event
         *
         * @param {Event} e - Change event
         */
        handleQuantityChange(e) {
            let quantity = parseInt(e.target.value) || 0;

            // Enforce minimum
            if (quantity < this.config.minParticipants) {
                quantity = this.config.minParticipants;
                e.target.value = quantity;
            }

            // Enforce maximum
            if (quantity > this.config.maxParticipants) {
                quantity = this.config.maxParticipants;
                e.target.value = quantity;
                this.showGlobalWarning(
                    `Maximum ${this.config.maxParticipants} participants allowed per booking.`
                );
            }

            // Update fields
            this.updateParticipantFields(quantity);
        }

        /**
         * Update participant fields based on quantity
         *
         * @param {number} quantity - Number of participants
         */
        updateParticipantFields(quantity) {
            // Prevent unnecessary updates
            if (quantity === this.currentQuantity) {
                return;
            }

            console.log(`GMPB: Updating fields for ${quantity} participants`);

            // Store current values before clearing
            const currentValues = this.getCurrentFieldValues();

            // Clear existing fields
            this.participantsContainer.innerHTML = '';

            // Generate new fields
            for (let i = 1; i <= quantity; i++) {
                const fieldSet = this.createParticipantFieldSet(i);
                this.participantsContainer.appendChild(fieldSet);

                // Restore previous values if they exist
                if (currentValues[i]) {
                    this.restoreFieldValues(i, currentValues[i]);
                }
            }

            // Update state
            this.currentQuantity = quantity;

            // Animate fields in
            this.animateFieldsIn();

            // Announce change to screen readers
            this.announceToScreenReader(`${quantity} participant ${quantity === 1 ? 'field' : 'fields'} displayed`);
        }

        /**
         * Get current field values before clearing
         *
         * @return {Object} Current field values indexed by participant number
         */
        getCurrentFieldValues() {
            const values = {};
            const fields = this.participantsContainer.querySelectorAll('.gmpb-participant-field');

            fields.forEach((fieldSet) => {
                const index = parseInt(fieldSet.dataset.participantIndex);
                const nameInput = fieldSet.querySelector(`input[name="participant_name_${index}"]`);
                const emailInput = fieldSet.querySelector(`input[name="participant_email_${index}"]`);
                const phoneInput = fieldSet.querySelector(`input[name="participant_phone_${index}"]`);

                values[index] = {
                    name: nameInput?.value || '',
                    email: emailInput?.value || '',
                    phone: phoneInput?.value || ''
                };
            });

            return values;
        }

        /**
         * Restore field values after regeneration
         *
         * @param {number} index - Participant index
         * @param {Object} values - Field values to restore
         */
        restoreFieldValues(index, values) {
            const nameInput = document.querySelector(`input[name="participant_name_${index}"]`);
            const emailInput = document.querySelector(`input[name="participant_email_${index}"]`);
            const phoneInput = document.querySelector(`input[name="participant_phone_${index}"]`);

            if (nameInput) nameInput.value = values.name;
            if (emailInput) emailInput.value = values.email;
            if (phoneInput) phoneInput.value = values.phone;
        }

        /**
         * Create participant field set HTML
         *
         * @param {number} index - Participant number (1-based)
         * @return {HTMLElement} Field set element
         */
        createParticipantFieldSet(index) {
            const fieldSet = document.createElement('div');
            fieldSet.className = 'gmpb-participant-field';
            fieldSet.dataset.participantIndex = index;
            fieldSet.setAttribute('role', 'group');
            fieldSet.setAttribute('aria-labelledby', `participant-heading-${index}`);

            // Build HTML structure
            fieldSet.innerHTML = `
                <div class="gmpb-participant-field__header">
                    <span class="gmpb-participant-field__number" id="participant-heading-${index}">
                        Participant ${index}
                    </span>
                </div>
                <div class="gmpb-participant-field__group">
                    <div class="gmpb-field">
                        <label for="participant_name_${index}">
                            ${this.config.labels.name} <span class="required" aria-label="required">*</span>
                        </label>
                        <input
                            type="text"
                            id="participant_name_${index}"
                            name="participant_name_${index}"
                            class="gmpb-input"
                            required
                            placeholder="Enter full name"
                            autocomplete="name"
                            aria-required="true"
                            aria-describedby="participant-name-desc-${index}">
                        <span class="gmpb-field-description" id="participant-name-desc-${index}" style="display:none;">
                            Enter the full name of participant ${index}
                        </span>
                    </div>
                    <div class="gmpb-field">
                        <label for="participant_email_${index}">
                            ${this.config.labels.email} <span class="required" aria-label="required">*</span>
                        </label>
                        <input
                            type="email"
                            id="participant_email_${index}"
                            name="participant_email_${index}"
                            class="gmpb-input"
                            required
                            placeholder="Enter email address"
                            autocomplete="email"
                            aria-required="true"
                            aria-describedby="participant-email-desc-${index}">
                        <span class="gmpb-field-description" id="participant-email-desc-${index}" style="display:none;">
                            Enter a valid email address for participant ${index}
                        </span>
                    </div>
                    ${this.config.requirePhone ? `
                    <div class="gmpb-field">
                        <label for="participant_phone_${index}">
                            ${this.config.labels.phone} <span class="required" aria-label="required">*</span>
                        </label>
                        <input
                            type="tel"
                            id="participant_phone_${index}"
                            name="participant_phone_${index}"
                            class="gmpb-input"
                            required
                            placeholder="Enter phone number"
                            autocomplete="tel"
                            aria-required="true"
                            aria-describedby="participant-phone-desc-${index}">
                        <span class="gmpb-field-description" id="participant-phone-desc-${index}" style="display:none;">
                            Enter a phone number for participant ${index}
                        </span>
                    </div>
                    ` : ''}
                </div>
                <div class="gmpb-participant-field__errors" role="alert" aria-live="polite"></div>
            `;

            return fieldSet;
        }

        /**
         * Animate fields in with staggered effect
         */
        animateFieldsIn() {
            const fields = this.participantsContainer.querySelectorAll('.gmpb-participant-field');

            fields.forEach((field, index) => {
                // Set initial state
                field.style.opacity = '0';
                field.style.transform = 'translateY(20px)';

                // Animate in with delay
                setTimeout(() => {
                    field.style.transition = 'all 0.3s ease';
                    field.style.opacity = '1';
                    field.style.transform = 'translateY(0)';
                }, index * 50);
            });
        }

        /**
         * Validate all participant fields before form submission
         *
         * @return {boolean} Whether all fields are valid
         */
        validateParticipantFields() {
            const fields = this.participantsContainer.querySelectorAll('.gmpb-participant-field');
            let isValid = true;
            const emails = [];

            // Clear all previous errors
            this.clearAllErrors();

            // Validate each participant
            fields.forEach((fieldSet) => {
                const index = fieldSet.dataset.participantIndex;
                const nameInput = fieldSet.querySelector(`input[name="participant_name_${index}"]`);
                const emailInput = fieldSet.querySelector(`input[name="participant_email_${index}"]`);
                const phoneInput = fieldSet.querySelector(`input[name="participant_phone_${index}"]`);

                // Validate name
                if (!nameInput.value.trim()) {
                    this.showFieldError(fieldSet, nameInput, this.config.errors.nameRequired);
                    isValid = false;
                }

                // Validate email
                if (!emailInput.value.trim()) {
                    this.showFieldError(fieldSet, emailInput, this.config.errors.emailRequired);
                    isValid = false;
                } else if (!this.isValidEmail(emailInput.value)) {
                    this.showFieldError(fieldSet, emailInput, this.config.errors.emailInvalid);
                    isValid = false;
                } else {
                    // Collect emails for duplicate check
                    emails.push(emailInput.value.trim().toLowerCase());
                }

                // Validate phone (if required)
                if (this.config.requirePhone && phoneInput && !phoneInput.value.trim()) {
                    this.showFieldError(fieldSet, phoneInput, this.config.errors.phoneRequired);
                    isValid = false;
                }
            });

            // Check for duplicate emails (if not allowed)
            if (!this.config.allowDuplicateEmails && this.hasDuplicates(emails)) {
                this.showGlobalError(this.config.errors.duplicateEmail);
                isValid = false;
            }

            return isValid;
        }

        /**
         * Validate individual email field
         *
         * @param {HTMLElement} emailInput - Email input element
         * @return {boolean} Whether email is valid
         */
        validateEmailField(emailInput) {
            const value = emailInput.value.trim();

            if (!value) {
                emailInput.classList.remove('error');
                return true;
            }

            if (!this.isValidEmail(value)) {
                emailInput.classList.add('error');
                return false;
            } else {
                emailInput.classList.remove('error');
                return true;
            }
        }

        /**
         * Check if email format is valid
         *
         * @param {string} email - Email address to validate
         * @return {boolean} Whether email is valid
         */
        isValidEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email.trim());
        }

        /**
         * Check if array has duplicate values
         *
         * @param {Array} arr - Array to check
         * @return {boolean} Whether duplicates exist
         */
        hasDuplicates(arr) {
            return new Set(arr).size !== arr.length;
        }

        /**
         * Show field-specific error
         *
         * @param {HTMLElement} fieldSet - Parent field set
         * @param {HTMLElement} input - Input element
         * @param {string} message - Error message
         */
        showFieldError(fieldSet, input, message) {
            // Add error class to input
            input.classList.add('error');
            input.setAttribute('aria-invalid', 'true');

            // Create error message element
            const errorDiv = fieldSet.querySelector('.gmpb-participant-field__errors');
            const errorMessage = document.createElement('div');
            errorMessage.className = 'gmpb-error-message';
            errorMessage.textContent = message;
            errorMessage.setAttribute('role', 'alert');

            errorDiv.appendChild(errorMessage);

            // Focus the input
            input.focus();
        }

        /**
         * Show global error message
         *
         * @param {string} message - Error message
         */
        showGlobalError(message) {
            const existingError = this.formWrapper.querySelector('.gmpb-global-error');

            if (existingError) {
                existingError.remove();
            }

            const errorDiv = document.createElement('div');
            errorDiv.className = 'gmpb-global-error';
            errorDiv.setAttribute('role', 'alert');
            errorDiv.textContent = message;

            this.participantsContainer.insertAdjacentElement('beforebegin', errorDiv);
        }

        /**
         * Show global warning message
         *
         * @param {string} message - Warning message
         */
        showGlobalWarning(message) {
            const existingWarning = this.formWrapper.querySelector('.gmpb-global-warning');

            if (existingWarning) {
                existingWarning.remove();
            }

            const warningDiv = document.createElement('div');
            warningDiv.className = 'gmpb-global-warning';
            warningDiv.setAttribute('role', 'status');
            warningDiv.textContent = message;

            this.participantsContainer.insertAdjacentElement('beforebegin', warningDiv);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                warningDiv.remove();
            }, 5000);
        }

        /**
         * Clear all error messages and states
         */
        clearAllErrors() {
            // Clear field errors
            const errorDivs = this.participantsContainer.querySelectorAll('.gmpb-participant-field__errors');
            errorDivs.forEach(div => {
                div.innerHTML = '';
            });

            // Remove error classes
            const errorInputs = this.participantsContainer.querySelectorAll('input.error');
            errorInputs.forEach(input => {
                input.classList.remove('error');
                input.removeAttribute('aria-invalid');
            });

            // Remove global errors
            const globalError = this.formWrapper.querySelector('.gmpb-global-error');
            if (globalError) {
                globalError.remove();
            }
        }

        /**
         * Scroll to first error field
         */
        scrollToFirstError() {
            const firstError = this.participantsContainer.querySelector('input.error');

            if (firstError) {
                const offset = 100; // Offset from top
                const elementPosition = firstError.getBoundingClientRect().top + window.pageYOffset;
                const offsetPosition = elementPosition - offset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });

                // Focus the field after scroll
                setTimeout(() => {
                    firstError.focus();
                }, 500);
            }
        }

        /**
         * Handle auto-focus to next field
         *
         * @param {HTMLElement} currentInput - Current input element
         */
        handleAutoFocus(currentInput) {
            const allInputs = Array.from(
                this.participantsContainer.querySelectorAll('input[name^="participant_"]')
            );

            const currentIndex = allInputs.indexOf(currentInput);

            // If field is filled and not the last field
            if (currentInput.value.trim() && currentIndex < allInputs.length - 1) {
                const nextInput = allInputs[currentIndex + 1];

                // Only auto-focus if next field is empty
                if (!nextInput.value.trim()) {
                    setTimeout(() => {
                        nextInput.focus();
                    }, 100);
                }
            }
        }

        /**
         * Announce message to screen readers
         *
         * @param {string} message - Message to announce
         */
        announceToScreenReader(message) {
            let announcer = document.getElementById('gmpb-screen-reader-announcer');

            if (!announcer) {
                announcer = document.createElement('div');
                announcer.id = 'gmpb-screen-reader-announcer';
                announcer.className = 'screen-reader-text';
                announcer.setAttribute('aria-live', 'polite');
                announcer.setAttribute('aria-atomic', 'true');
                announcer.style.cssText = 'position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;';
                document.body.appendChild(announcer);
            }

            announcer.textContent = message;
        }
    }

    /**
     * Initialize when DOM is ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            new ParticipantManager();
        });
    } else {
        new ParticipantManager();
    }

})();
