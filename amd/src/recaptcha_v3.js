/**
 * ReCAPTCHA v3 handler for signup and login forms.
 *
 * Handles invisible token generation and injection for reCAPTCHA v3.
 * v3 does not require user interaction; it scores requests silently in background.
 *
 * @module     auth_emailfirst/recaptcha_v3
 * @package
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {

var activeForm = null;
var feedbackElement = null;

/**
 * Initialize reCAPTCHA v3 for a form.
 *
 * @param {string} siteKey Google reCAPTCHA v3 Site Key.
 * @param {string} action Action name ('signup', 'login', etc.).
 * @param {number} threshold Score threshold (0.0-1.0); requests below blocked.
 */
var init = function(siteKey, action, threshold) {
    // Default threshold to 0.5 if not provided.
    if (!threshold) {
        threshold = 0.5;
    }

    // Load Google reCAPTCHA v3 script.
    loadRecaptchaScript(siteKey);

    // Hook form submission.
    document.addEventListener('DOMContentLoaded', function() {
        activeForm = document.querySelector('form');
        if (!activeForm) {
            return;
        }

        // Prevent default submission; we'll submit after getting token.
        activeForm.addEventListener('submit', handleFormSubmit);
    });

    /**
     * Handle form submission by getting reCAPTCHA token first.
     *
     * @param {Event} e Submit event.
     */
    function handleFormSubmit(e) {
        // Don't submit yet.
        e.preventDefault();

        // Show loading spinner/disabled button if desired.
        const submitBtn = activeForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Verifying...';
        }

        // Request token from Google with the specified action.
        if (!window.grecaptcha) {
            showError('Bot verification failed. Please refresh and try again.');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit';
            }
            return;
        }

        window.grecaptcha.execute(siteKey, {action: action})
            .then(function(token) {
                // Inject token into hidden field.
                const responseField = document.getElementById('g-recaptcha-response');
                if (responseField) {
                    responseField.value = token;
                }

                // Now submit the form (without triggering this handler again).
                activeForm.removeEventListener('submit', handleFormSubmit);
                activeForm.submit();
                return token;
            })
            .catch(function() {
                showError('Bot verification failed. Please try again.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit';
                }
            });
    }
};

/**
 * Load Google reCAPTCHA v3 script if not already loaded.
 *
 * @param {string} siteKey Google reCAPTCHA v3 Site Key.
 */
function loadRecaptchaScript(siteKey) {
    // Check if already loaded.
    if (window.grecaptcha) {
        return;
    }

    // Script URL with site key.
    const scriptUrl = 'https://www.google.com/recaptcha/api.js?render=' + encodeURIComponent(siteKey);

    // Create script element.
    const script = document.createElement('script');
    script.src = scriptUrl;
    script.async = true;
    script.defer = true;
    script.onerror = function() {
        return;
    };

    // Append to head.
    document.head.appendChild(script);
}

/**
 * Show reCAPTCHA feedback without using a blocking browser alert.
 *
 * @param {string} message User-facing error message.
 */
function showError(message) {
    if (!activeForm) {
        return;
    }

    if (!feedbackElement) {
        feedbackElement = document.createElement('div');
        feedbackElement.className = 'alert alert-danger';
        feedbackElement.setAttribute('role', 'alert');
        activeForm.prepend(feedbackElement);
    }

    feedbackElement.textContent = message;
}

return {
    init: init
};
});
