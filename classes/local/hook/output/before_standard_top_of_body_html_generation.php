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
 * Hook callback for injecting the navbar signup button.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_emailfirst\local\hook\output;

/**
 * Injects a "Sign Up" button next to the login link in the navbar for guests.
 */
class before_standard_top_of_body_html_generation {
    /**
     * Hook callback.
     *
     * @param \core\hook\output\before_standard_top_of_body_html_generation $hook
     */
    public static function callback(\core\hook\output\before_standard_top_of_body_html_generation $hook): void {
        self::redirect_core_pages();

        $html = self::get_signup_button_html();
        if ($html !== '') {
            $hook->add_html($html);
        }
    }

    /**
     * Redirect core signup-related pages to the branded emailfirst versions.
     */
    private static function redirect_core_pages(): void {
        global $CFG, $PAGE;

        if (empty($CFG->registerauth) || $CFG->registerauth !== 'emailfirst') {
            return;
        }

        $redirects = [
            '/login/verify_age_location.php' => '/auth/emailfirst/verify_age_location.php',
        ];

        foreach ($redirects as $core => $custom) {
            if ($PAGE->url->compare(new \moodle_url($core), URL_MATCH_BASE)) {
                redirect(new \moodle_url($custom));
            }
        }
    }

    /**
     * Build the script tag that injects the signup button, or empty string if not applicable.
     *
     * @return string
     */
    public static function get_signup_button_html(): string {
        global $CFG;

        if (\isloggedin() && !\isguestuser()) {
            return '';
        }

        $showsignup = !empty(\get_config('auth_emailfirst', 'shownavsignup'));

        require_once($CFG->dirroot . '/lib/authlib.php');
        if ($showsignup && !\signup_is_enabled()) {
            $showsignup = false;
        }

        // Build login button style.
        $loginstyle = self::build_inline_style('navlogin');
        $logintext  = \get_config('auth_emailfirst', 'navlogin_text');

        // Build signup button style.
        $signupstyle = self::build_inline_style('navsignup');
        $signuptext  = \get_config('auth_emailfirst', 'navsignup_text');
        if (empty($signuptext)) {
            $signuptext = \get_string('navsignup', 'auth_emailfirst');
        }

        // Nothing to inject if no customisation and signup hidden.
        if (!$showsignup && empty($loginstyle) && empty($logintext)) {
            return '';
        }

        $signupurl = new \moodle_url('/login/signup.php');

        $js  = 'document.addEventListener("DOMContentLoaded",function(){';
        $js .= 'var loginSpan=document.querySelector("[data-region=\\"usermenu\\"] span.login");';
        $js .= 'if(!loginSpan) return;';

        // Style the login link inside the span.
        if (!empty($loginstyle) || !empty($logintext)) {
            $js .= 'var loginLink=loginSpan.querySelector("a");';
            $js .= 'if(loginLink){';
            if (!empty($loginstyle)) {
                $js .= 'loginLink.setAttribute("style",' . json_encode($loginstyle) . ');';
            }
            if (!empty($logintext)) {
                $js .= 'loginLink.textContent=' . json_encode($logintext) . ';';
            }
            $js .= '}';
        }

        // Inject signup button.
        if ($showsignup) {
            $js .= 'var btn=document.createElement("a");';
            $js .= 'btn.href=' . json_encode($signupurl->out(false)) . ';';
            $js .= 'btn.className="btn btn-primary btn-sm ms-2 auth-emailfirst-nav-signup";';
            if (!empty($signupstyle)) {
                $js .= 'btn.setAttribute("style",' . json_encode($signupstyle) . ');';
            }
            $js .= 'btn.textContent=' . json_encode($signuptext) . ';';
            $js .= 'loginSpan.appendChild(btn);';
        }

        $js .= '});';

        return '<script>' . $js . '</script>';
    }

    /**
     * Build an inline CSS style string from the config for a given button prefix.
     *
     * @param string $prefix  Setting prefix (navlogin or navsignup).
     * @return string  Inline CSS or empty string.
     */
    private static function build_inline_style(string $prefix): string {
        $parts = [];

        $color = \get_config('auth_emailfirst', $prefix . '_textcolor');
        if (!empty($color)) {
            $parts[] = 'color:' . s($color) . ' !important';
        }

        $bg = \get_config('auth_emailfirst', $prefix . '_bgcolor');
        if (!empty($bg)) {
            $parts[] = 'background-color:' . s($bg) . ' !important';
            $parts[] = 'border-color:' . s($bg) . ' !important';
        }

        $padding = \get_config('auth_emailfirst', $prefix . '_padding');
        if (!empty($padding)) {
            $parts[] = 'padding:' . s($padding);
        }

        $radius = \get_config('auth_emailfirst', $prefix . '_borderradius');
        if (!empty($radius)) {
            $parts[] = 'border-radius:' . s($radius);
        }

        return implode(';', $parts);
    }
}
