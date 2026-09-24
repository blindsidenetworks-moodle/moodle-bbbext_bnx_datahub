// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Refreshes the DataHub session overview without reloading the dashboard.
 *
 * @module     bbbext_bnx_datahub/session_selector
 * @copyright  2026 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    const SELECTORS = {
        session: '[data-region="bbbext-bnx-datahub-session"]',
        form: '.bbbext-bnx-datahub-dashboard__selector',
        submit: 'button[type="submit"]',
    };

    /**
     * Initialise asynchronous session selection.
     */
    const init = () => {
        const session = document.querySelector(SELECTORS.session);
        if (!session || !window.fetch) {
            return;
        }

        session.addEventListener('submit', async event => {
            const form = event.target.closest(SELECTORS.form);
            if (!form || !session.contains(form)) {
                return;
            }

            event.preventDefault();
            const url = new URL(form.dataset.fragmentUrl, window.location.origin);
            const formData = new FormData(form);
            url.search = new URLSearchParams(formData).toString();

            const submit = form.querySelector(SELECTORS.submit);
            submit.disabled = true;

            try {
                const response = await fetch(url, {credentials: 'same-origin'});
                if (!response.ok) {
                    throw new Error(response.statusText);
                }

                session.innerHTML = await response.text();
            } catch (error) {
                window.location.assign(`${form.action}?${new URLSearchParams(formData).toString()}`);
            }
        });

        session.addEventListener('change', event => {
            if (!event.target.matches('select[name="meetingid"]')) {
                return;
            }

            const form = event.target.closest(SELECTORS.form);
            if (form && session.contains(form)) {
                form.requestSubmit();
            }
        });
    };

    return {init};
});
