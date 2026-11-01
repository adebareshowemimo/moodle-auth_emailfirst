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

        $brandlogo = self::get_auth_page_brand_logo();
        if ($brandlogo !== '') {
            $hook->add_html($brandlogo);
        }

        $html = self::get_signup_button_html();
        if ($html !== '') {
            $hook->add_html($html);
        }
    }

    /**
     * Place the Starter U white logo outside the theme's login card.
     *
     * Boost Union prints this hook before its page wrapper. That gives the logo
     * its own element, so it can sit beside the card on desktop and above it on
     * mobile without changing the theme or moving form controls with JavaScript.
     *
     * @return string Logo link, or an empty string when it does not apply.
     */
    private static function get_auth_page_brand_logo(): string {
        global $PAGE;

        if (
            ($PAGE->theme->name ?? '') !== 'boost_union' ||
                (\isloggedin() && !\isguestuser())
        ) {
            return '';
        }

        $signup = $PAGE->url->compare(new \moodle_url('/auth/emailfirst/signup.php'), URL_MATCH_BASE);
        $verifyage = $PAGE->url->compare(new \moodle_url('/auth/emailfirst/verify_age_location.php'), URL_MATCH_BASE);
        $login = $PAGE->url->compare(new \moodle_url('/login/index.php'), URL_MATCH_BASE) &&
            !empty(\get_config('auth_emailfirst', 'enablecustomlogin'));
        if (!$signup && !$verifyage && !$login) {
            return '';
        }

        $logo = new \moodle_url('/auth/emailfirst/pix/starteru-white-logo.png');
        $home = new \moodle_url('/');
        $sitename = \format_string(\get_site()->fullname);
        return '<a class="auth-emailfirst-brand-link" href="' . \s($home->out(false)) .
            '" aria-label="' . \s($sitename) . ': ' . \s(\get_string('home')) . '">' .
            '<img src="' . \s($logo->out(false)) . '" alt="' . \s($sitename) . '"></a>';
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
        global $CFG, $PAGE;

        if (\isloggedin() && !\isguestuser()) {
            return '';
        }

        // ModernUI renders and styles both logged-out header actions itself.
        // Do not append the legacy JavaScript button or its inline styles,
        // which would otherwise duplicate and override the theme controls.
        if (($PAGE->theme->name ?? '') === 'modernui') {
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
