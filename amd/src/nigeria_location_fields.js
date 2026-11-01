/**
 * Dependent Nigeria State and LGA profile fields.
 *
 * @module     auth_emailfirst/nigeria_location_fields
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    /**
     * Replace a text profile field with a select while retaining form attributes.
     *
     * @param {HTMLElement} field Existing input or select.
     * @return {HTMLSelectElement}
     */
    const ensureSelect = function(field) {
        if (field.tagName === 'SELECT') {
            return field;
        }

        const select = document.createElement('select');
        Array.from(field.attributes).forEach(function(attribute) {
            if (attribute.name !== 'type' && attribute.name !== 'value') {
                select.setAttribute(attribute.name, attribute.value);
            }
        });
        select.className = field.className;
        select.dataset.initialValue = field.value;
        field.replaceWith(select);
        return select;
    };

    /**
     * Add a dropdown option.
     *
     * @param {HTMLSelectElement} select Select element.
     * @param {string} value Option value.
     * @param {string} label Option label.
     */
    const addOption = function(select, value, label) {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = label;
        select.appendChild(option);
    };

    /**
     * Initialise dependent location fields.
     *
     * @param {Object} config Module configuration.
     * @param {Object<string, string[]>} config.locations State/LGA map.
     * @param {string} config.selectState State placeholder.
     * @param {string} config.selectLga LGA placeholder.
     * @param {string} config.selectStateFirst LGA placeholder before state selection.
     */
    const init = function(config) {
        const stateField = document.querySelector('[name="profile_field_state"]');
        const lgaField = document.querySelector('[name="profile_field_lga"]');
        if (!stateField || !lgaField || !config.locations) {
            return;
        }

        const initialState = stateField.value || stateField.dataset.initialValue || '';
        const initialLga = lgaField.value || lgaField.dataset.initialValue || '';
        const stateSelect = ensureSelect(stateField);
        const lgaSelect = ensureSelect(lgaField);

        stateSelect.textContent = '';
        addOption(stateSelect, '', config.selectState);
        Object.keys(config.locations).forEach(function(state) {
            addOption(stateSelect, state, state);
        });
        if (Object.prototype.hasOwnProperty.call(config.locations, initialState)) {
            stateSelect.value = initialState;
        }

        const populateLgas = function(preferredLga) {
            const state = stateSelect.value;
            const lgas = config.locations[state] || [];
            lgaSelect.textContent = '';
            addOption(lgaSelect, '', state ? config.selectLga : config.selectStateFirst);
            lgas.forEach(function(lga) {
                addOption(lgaSelect, lga, lga);
            });
            lgaSelect.disabled = !state;
            if (preferredLga && lgas.includes(preferredLga)) {
                lgaSelect.value = preferredLga;
            }
        };

        populateLgas(initialLga);
        stateSelect.addEventListener('change', function() {
            populateLgas('');
        });
    };

    return {init: init};
});
