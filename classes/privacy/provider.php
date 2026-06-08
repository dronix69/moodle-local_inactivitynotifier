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
 * GDPR privacy provider for the Inactivity Notifier plugin.
 *
 * @package   local_inactivitynotifier
 * @copyright 2026 Daniel Ferrada
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_inactivitynotifier\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;


/**
 * Privacy provider implementing GDPR compliance for local_inactivitynotifier.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Return metadata details.
     *
     * @param collection $collection The metadata collection.
     * @return collection The updated collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_inactivitynotifier_sent',
            [
                'userid' => 'privacy:metadata:userid',
                'courseid' => 'privacy:metadata:courseid',
                'timesent' => 'privacy:metadata:timesent',
            ],
            'privacy:metadata:tableexplanation'
        );
        return $collection;
    }

    /**
     * Get contexts for the given user ID.
     *
     * @param int $userid The user ID.
     * @return contextlist The context list.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // The contexts are the course contexts where the user was notified.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course} co ON co.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {local_inactivitynotifier_sent} s ON s.courseid = co.id
                 WHERE s.userid = :userid";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Export user data.
     *
     * @param approved_contextlist $contextlist List of contexts to export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $courseids = [];

        foreach ($contextlist as $context) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                $courseids[] = $context->instanceid;
            }
        }

        if (empty($courseids)) {
            return;
        }

        // Preload all records in bulk to avoid N+1 query performance issues.
        [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'course');
        $params = array_merge(['userid' => $userid], $inparams);
        $records = $DB->get_records_select(
            'local_inactivitynotifier_sent',
            "userid = :userid AND courseid $insql",
            $params
        );

        $recordsbycourse = [];
        if ($records) {
            foreach ($records as $record) {
                $recordsbycourse[$record->courseid][] = $record;
            }
        }

        foreach ($contextlist as $context) {
            if ($context->contextlevel != CONTEXT_COURSE) {
                continue;
            }

            $courseid = $context->instanceid;
            if (isset($recordsbycourse[$courseid])) {
                $data = [];
                foreach ($recordsbycourse[$courseid] as $record) {
                    $data[] = [
                        'timesent' => userdate($record->timesent),
                    ];
                }

                \core_privacy\local\request\writer::with_context($context)
                    ->export_data([get_string('privacy:metadata', 'local_inactivitynotifier')], (object)['notifications' => $data]);
            }
        }
    }

    /**
     * Delete all data for all users in the given context.
     *
     * @param \context $context The context to delete data from.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel == CONTEXT_COURSE) {
            $DB->delete_records('local_inactivitynotifier_sent', ['courseid' => $context->instanceid]);
        }
    }

    /**
     * Delete data for a user in approved contexts.
     *
     * @param approved_contextlist $contextlist List of contexts to delete data from.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $courseids = [];

        foreach ($contextlist as $context) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                $courseids[] = $context->instanceid;
            }
        }

        if (empty($courseids)) {
            return;
        }

        // Delete all matching records in a single bulk DB call to avoid N+1 queries.
        [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'course');
        $params = array_merge(['userid' => $userid], $inparams);
        $DB->delete_records_select(
            'local_inactivitynotifier_sent',
            "userid = :userid AND courseid $insql",
            $params
        );
    }
}
