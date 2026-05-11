/**
 * Multi-step signup wizard for auth_emailfirst.
 *
 * @module     auth_emailfirst/signup_steps
 * @package
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {

    var steps = [];
    var formEl = null;
    var strings = {};

    /**
     * Initialise the multi-step wizard.
     *
     * @param {Object} config Configuration object.
     * @param {string[]} config.steps Array of step header element names.
     * @param {Object} config.strings Translated UI strings.
     */
    var init = function(config) {
        strings = config.strings || {};
        var stepNames = config.steps || [];

        // Collect fieldsets by their Moodle-generated IDs.
        steps = [];
        for (var i = 0; i < stepNames.length; i++) {
            var fieldset = document.getElementById('id_' + stepNames[i]);
            if (fieldset) {
                steps.push(fieldset);
            }
        }

        if (steps.length < 2) {
            return; // No point in multi-step with fewer than 2 steps.
        }

        formEl = steps[0].closest('form');
        if (!formEl) {
            return;
        }

        // Remove Moodle's default collapse behaviour on fieldsets.
        steps.forEach(function(step) {
            step.classList.remove('collapsible', 'collapsed');
            var toggle = step.querySelector('.fheader');
            if (toggle) {
                toggle.removeAttribute('role');
                toggle.removeAttribute('aria-expanded');
                toggle.removeAttribute('aria-controls');
                toggle.style.cursor = 'default';
                // Replace the link inside legend with plain text.
                var link = toggle.querySelector('a');
                if (link) {
                    toggle.textContent = link.textContent;
                }
            }
        });

        // Build progress bar.
        var progressEl = document.createElement('div');
        progressEl.id = 'emailfirst-progress';
        progressEl.className = 'mb-4';
        formEl.insertBefore(progressEl, steps[0]);

        // Add navigation buttons to each step.
        addNavButtons();

        // Hide submit/cancel buttons by default — shown only on last step.
        var actionBar = formEl.querySelector('#fgroup_id_buttonar');
        if (actionBar) {
            actionBar.style.display = 'none';
        }

        // Hide Moodle's Collapse/Expand all button — not needed in wizard mode.
        // We hide rather than remove so Moodle's collapsesections.js doesn't crash.
        var collapseActions = document.querySelector('.collapsible-actions');
        if (collapseActions) {
            collapseActions.style.display = 'none';
        }

        // Show step 0.
        showStep(0);
    };

    /**
     * Add Next / Back buttons inside each step fieldset.
     */
    var addNavButtons = function() {
        var nextLabel = strings.next || 'Next';
        var backLabel = strings.back || 'Back';

        steps.forEach(function(step, idx) {
            var nav = document.createElement('div');
            nav.className = 'emailfirst-step-nav mt-3 d-flex justify-content-between';

            var html = '';

            // Back button (not on first step).
            if (idx > 0) {
                html += '<button type="button" class="btn btn-secondary emailfirst-back" ' +
                    'data-step="' + idx + '">' + backLabel + '</button>';
            } else {
                html += '<span></span>';
            }

            // Next button (not on last step).
            if (idx < steps.length - 1) {
                html += '<button type="button" class="btn btn-primary emailfirst-next" ' +
                    'data-step="' + idx + '">' + nextLabel + '</button>';
            }

            nav.innerHTML = html;
            step.appendChild(nav);
        });

        // Event delegation on form.
        formEl.addEventListener('click', function(e) {
            var target = e.target;
            if (target.classList.contains('emailfirst-next')) {
                validateAndNext(parseInt(target.getAttribute('data-step'), 10));
            } else if (target.classList.contains('emailfirst-back')) {
                showStep(parseInt(target.getAttribute('data-step'), 10) - 1);
            }
        });
    };

    /**
     * Show a specific step and hide all others.
     *
     * @param {number} idx Zero-based step index.
     */
    var showStep = function(idx) {

        steps.forEach(function(step, i) {
            step.style.display = (i === idx) ? '' : 'none';
        });

        // Show action buttons only on last step.
        var actionBar = formEl.querySelector('#fgroup_id_buttonar');
        if (actionBar) {
            actionBar.style.display = (idx === steps.length - 1) ? '' : 'none';
        }

        // Update progress bar.
        var progressEl = document.getElementById('emailfirst-progress');
        if (progressEl) {
            var pct = ((idx + 1) / steps.length * 100).toFixed(0);
            var label = (strings.step_x_of_y || 'Step {current} of {total}')
                .replace('{current}', idx + 1)
                .replace('{total}', steps.length);

            progressEl.innerHTML =
                '<div class="d-flex justify-content-between align-items-center mb-1">' +
                    '<small class="text-muted">' + label + '</small>' +
                '</div>' +
                '<div class="progress" style="height:6px;">' +
                    '<div class="progress-bar" role="progressbar" style="width:' + pct + '%" ' +
                        'aria-valuenow="' + pct + '" aria-valuemin="0" aria-valuemax="100"></div>' +
                '</div>';
        }

        // Focus first visible input.
        var firstInput = steps[idx].querySelector('input:not([type=hidden]), select, textarea');
        if (firstInput) {
            firstInput.focus();
        }
    };

    /**
     * Validate the current step's fields then advance.
     *
     * @param {number} stepIdx Current step index.
     */
    var validateAndNext = function(stepIdx) {
        clearStepErrors(stepIdx);

        var step = steps[stepIdx];
        var valid = true;

        // Check any required fields in this step.
        var fitems = step.querySelectorAll('.fitem');
        fitems.forEach(function(fitem) {
            // Moodle marks required fields with a span.req or .fdescription.required
            if (!fitem.querySelector('.fdescription.required, abbr.text-danger')) {
                return;
            }
            var input = fitem.querySelector('input:not([type=hidden]), select, textarea');
            if (input && (!input.value || !input.value.trim())) {
                setFieldError(input, strings.field_required || 'Required');
                valid = false;
            }
        });

        // Also check inputs with the HTML "required" attribute.
        var requiredInputs = step.querySelectorAll('input[required], textarea[required], select[required]');
        requiredInputs.forEach(function(input) {
            if (!input.value || !input.value.trim()) {
                // Only add error if not already set.
                if (!input.classList.contains('is-invalid')) {
                    setFieldError(input, strings.field_required || 'Required');
                    valid = false;
                }
            }
        });

        if (!valid) {
            return;
        }

        // Step-specific validation.
        if (stepIdx === 0) {
            // Email step.
            var emailInput = step.querySelector('[name="email"]');
            var email2Input = step.querySelector('[name="email2"]');
            if (emailInput && email2Input) {
                var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRe.test(emailInput.value)) {
                    setFieldError(emailInput, strings.invalid_email || 'Invalid email address');
                    return;
                }
                if (emailInput.value !== email2Input.value) {
                    setFieldError(email2Input, strings.emails_not_match || 'Emails do not match');
                    return;
                }
            }
        }

        // Default: proceed to next step.
        showStep(stepIdx + 1);
    };

    /**
     * Show a field-level error.
     *
     * @param {HTMLElement} input The input element.
     * @param {string} message Error message.
     */
    var setFieldError = function(input, message) {
        input.classList.add('is-invalid');
        var errorDiv = document.createElement('div');
        errorDiv.className = 'emailfirst-field-error invalid-feedback d-block';
        errorDiv.textContent = message;
        input.parentNode.appendChild(errorDiv);
    };

    /**
     * Clear all errors in a step.
     *
     * @param {number} stepIdx Step index.
     */
    var clearStepErrors = function(stepIdx) {
        var step = steps[stepIdx];
        var errors = step.querySelectorAll('.emailfirst-field-error');
        errors.forEach(function(el) {
 el.remove();
});
        var invalids = step.querySelectorAll('.is-invalid');
        invalids.forEach(function(el) {
 el.classList.remove('is-invalid');
});
    };

    return {init: init};
});
