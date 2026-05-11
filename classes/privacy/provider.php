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
 * Privacy provider for auth_emailfirst.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_emailfirst\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider implementation.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describe the data stored by this plugin.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('auth_emailfirst_survey', [
            'userid' => 'privacy:metadata:auth_emailfirst_survey:userid',
            'referral_source' => 'privacy:metadata:auth_emailfirst_survey:referral_source',
            'timecreated' => 'privacy:metadata:auth_emailfirst_survey:timecreated',
        ], 'privacy:metadata:auth_emailfirst_survey');

        $collection->add_database_table('auth_emailfirst_ratelimit', [
            'ip' => 'privacy:metadata:auth_emailfirst_ratelimit:ip',
            'timeattempt' => 'privacy:metadata:auth_emailfirst_ratelimit:timeattempt',
        ], 'privacy:metadata:auth_emailfirst_ratelimit');

        $collection->add_database_table('auth_emailfirst_security_log', [
            'event' => 'privacy:metadata:auth_emailfirst_security_log:event',
            'ip' => 'privacy:metadata:auth_emailfirst_security_log:ip',
            'timeattempt' => 'privacy:metadata:auth_emailfirst_security_log:timeattempt',
            'metadata' => 'privacy:metadata:auth_emailfirst_security_log:metadata',
        ], 'privacy:metadata:auth_emailfirst_security_log');

        $collection->add_user_preference(
            'auth_emailfirst_token',
            'privacy:metadata:preference:auth_emailfirst_token'
        );
        $collection->add_user_preference(
            'auth_emailfirst_token_expiry',
            'privacy:metadata:preference:auth_emailfirst_token_expiry'
        );
        $collection->add_user_preference(
            'auth_emailfirst_reset_token',
            'privacy:metadata:preference:auth_emailfirst_reset_token'
        );
        $collection->add_user_preference(
            'auth_emailfirst_reset_token_expiry',
            'privacy:metadata:preference:auth_emailfirst_reset_token_expiry'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {auth_emailfirst_survey} s
                  JOIN {context} ctx ON ctx.instanceid = s.userid AND ctx.contextlevel = :contextlevel
                 WHERE s.userid = :userid";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_USER,
            'userid' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Get the list of users in a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_USER) {
            return;
        }

        $sql = "SELECT s.userid
                  FROM {auth_emailfirst_survey} s
                 WHERE s.userid = :userid";

        $userlist->add_from_sql('userid', $sql, ['userid' => $context->instanceid]);
    }

    /**
     * Export user data.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $record = $DB->get_record('auth_emailfirst_survey', ['userid' => $userid]);

        if ($record) {
            $data = (object) [
                'referral_source' => $record->referral_source,
                'timecreated' => \core_privacy\local\request\transform::datetime($record->timecreated),
            ];

            $context = \context_user::instance($userid);
            writer::with_context($context)->export_data(
                [get_string('pluginname', 'auth_emailfirst')],
                $data
            );
        }

        $preferences = (object) [
            'auth_emailfirst_token_expiry' => get_user_preferences('auth_emailfirst_token_expiry', null, $userid),
            'auth_emailfirst_reset_token_expiry' => get_user_preferences(
                'auth_emailfirst_reset_token_expiry',
                null,
                $userid
            ),
        ];

        writer::with_context(\context_user::instance($userid))->export_data(
            [get_string('pluginname', 'auth_emailfirst'), get_string('preferences')],
            $preferences
        );
    }

    /**
     * Delete all data for all users in a context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel !== CONTEXT_USER) {
            return;
        }

        $DB->delete_records('auth_emailfirst_survey', ['userid' => $context->instanceid]);
        unset_user_preference('auth_emailfirst_token', $context->instanceid);
        unset_user_preference('auth_emailfirst_token_expiry', $context->instanceid);
        unset_user_preference('auth_emailfirst_reset_token', $context->instanceid);
        unset_user_preference('auth_emailfirst_reset_token_expiry', $context->instanceid);
    }

    /**
     * Delete data for a user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $DB->delete_records('auth_emailfirst_survey', ['userid' => $userid]);
        unset_user_preference('auth_emailfirst_token', $userid);
        unset_user_preference('auth_emailfirst_token_expiry', $userid);
        unset_user_preference('auth_emailfirst_reset_token', $userid);
        unset_user_preference('auth_emailfirst_reset_token_expiry', $userid);
    }

    /**
     * Delete data for users in a context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_USER) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('auth_emailfirst_survey', "userid $insql", $inparams);
        foreach ($userids as $userid) {
            unset_user_preference('auth_emailfirst_token', $userid);
            unset_user_preference('auth_emailfirst_token_expiry', $userid);
            unset_user_preference('auth_emailfirst_reset_token', $userid);
            unset_user_preference('auth_emailfirst_reset_token_expiry', $userid);
        }
    }
}
