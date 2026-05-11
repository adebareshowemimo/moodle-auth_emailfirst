<?php
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
 * Output renderer for auth_emailfirst.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_emailfirst\output;

use plugin_renderer_base;

/**
 * Renderer for auth_emailfirst plugin.
 */
class renderer extends plugin_renderer_base {
    /**
     * Render the signup form using the core signup form layout template.
     *
     * @param \auth_emailfirst_signup_form $form
     * @return string HTML
     */
    public function render_auth_emailfirst_signup_form($form) {
        global $SITE;

        $context = $form->export_for_template($this);
        $url = $this->get_logo_url();
        if ($url) {
            $url = $url->out(false);
        }
        $context['logourl'] = $url;
        $context['sitename'] = format_string(
            $SITE->fullname,
            true,
            ['context' => \context_course::instance(SITEID), "escape" => false]
        );

        return $this->render_from_template('core/signup_form_layout', $context);
    }
}
