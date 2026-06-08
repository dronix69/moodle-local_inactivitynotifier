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
 * Scheduled task: sends inactivity notifications to students.
 *
 * @package   local_inactivitynotifier
 * @copyright 2026 Daniel Ferrada
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_inactivitynotifier\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/inactivitynotifier/lib.php');

/**
 * Class implementing the scheduled notification task.
 */
class send_notifications extends \core\task\scheduled_task
{
    /**
     * Returns the human-readable task name (appears in the admin UI).
     */
    public function get_name(): string {
        return get_string('task_send_notifications', 'local_inactivitynotifier');
    }

    /**
     * Main task logic.
     * Iterates through all active courses and notifies inactive students.
     */
    public function execute(): void {
        global $DB;

        // Check if plugin is enabled.
        $enabled = get_config('local_inactivitynotifier', 'enabled');
        if (!$enabled) {
            mtrace('local_inactivitynotifier: plugin disabled, skipping execution.');
            return;
        }

        $days            = (int) get_config('local_inactivitynotifier', 'inactivedays') ?: 7;
        $onlyvisible     = (bool) get_config('local_inactivitynotifier', 'onlyvisible');
        $remindfrequency = (int) get_config('local_inactivitynotifier', 'remind_frequency') ?: 7;

        $excludedcourses    = get_config('local_inactivitynotifier', 'excluded_courses');
        $excludedcategories = get_config('local_inactivitynotifier', 'excluded_categories');

        $now = time();
        $threshold = $now - ($days * DAYSECS);
        $remindthreshold = $now - ($remindfrequency * DAYSECS);

        $sqlwhere = 'u.deleted = 0 AND u.suspended = 0 AND (ul.timeaccess IS NULL OR ul.timeaccess < :threshold)';
        $sqlparams = [
            'now' => $now,
            'now2' => $now,
            'contextlevel' => CONTEXT_COURSE,
            'threshold' => $threshold,
            'remindthreshold' => $remindthreshold,
            'siteid' => SITEID,
        ];

        if ($onlyvisible) {
            $sqlwhere .= ' AND c.visible = 1';
        }

        if (!empty($excludedcourses)) {
            $courseslist = array_filter(array_map('intval', explode(',', $excludedcourses)));
            if (!empty($courseslist)) {
                [$insql, $inparams] = $DB->get_in_or_equal($courseslist, SQL_PARAMS_NAMED, 'excourse', false);
                $sqlwhere .= " AND c.id $insql";
                $sqlparams = array_merge($sqlparams, $inparams);
            }
        }

        if (!empty($excludedcategories)) {
            $catslist = array_filter(array_map('intval', explode(',', $excludedcategories)));
            if (!empty($catslist)) {
                [$insql, $inparams] = $DB->get_in_or_equal($catslist, SQL_PARAMS_NAMED, 'excat', false);
                $sqlwhere .= " AND c.category $insql";
                $sqlparams = array_merge($sqlparams, $inparams);
            }
        }

        $sql = "SELECT u.id AS userid, u.firstname, u.lastname, u.email,
                       c.id AS courseid, c.fullname AS coursefullname, c.shortname AS courseshortname,
                       c.enablecompletion,
                       COALESCE(ul.timeaccess, 0) AS lastaccess
                  FROM {user} u
                  JOIN {user_enrolments} ue ON ue.userid = u.id AND ue.status = 0
                       AND (ue.timestart = 0 OR ue.timestart <= :now)
                       AND (ue.timeend = 0 OR ue.timeend > :now2)
                  JOIN {enrol} e ON e.id = ue.enrolid AND e.status = 0
                  JOIN {course} c ON c.id = e.courseid AND c.id <> :siteid
                  JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel
                  JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = u.id
                  JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
             LEFT JOIN {user_lastaccess} ul ON ul.userid = u.id AND ul.courseid = c.id
             LEFT JOIN (
                 SELECT userid, courseid, MAX(timesent) AS lastsent
                   FROM {local_inactivitynotifier_sent}
               GROUP BY userid, courseid
             ) log ON log.userid = u.id AND log.courseid = c.id
                 WHERE $sqlwhere
                   AND (log.lastsent IS NULL OR log.lastsent < :remindthreshold)
              ORDER BY c.id";

        $rs = $DB->get_recordset_sql($sql, $sqlparams);
        $totalnotified = 0;
        $currentcourseid = null;
        $course = null;

        foreach ($rs as $record) {
            $courseid = $record->courseid;

            // Load or build the course object and check completion if needed.
            if ($courseid !== $currentcourseid) {
                $currentcourseid = $courseid;
                // Construct a course object for completion checking and mailing.
                $course = (object)[
                    'id' => $record->courseid,
                    'fullname' => $record->coursefullname,
                    'shortname' => $record->courseshortname,
                    'enablecompletion' => $record->enablecompletion,
                ];
                mtrace("Processing course: [{$course->id}] {$course->fullname}");
            }

            // Exclude students who completed the course.
            if ($course->enablecompletion) {
                $completion = new \completion_info($course);
                if ($completion->is_course_complete($record->userid)) {
                    mtrace("  → Student [{$record->userid}] completed course, skipping.");
                    continue;
                }
            }

            // Reconstruct the student user object.
            $student = (object)[
                'id' => $record->userid,
                'firstname' => $record->firstname,
                'lastname' => $record->lastname,
                'email' => $record->email,
            ];

            $sent = local_inactivitynotifier_send_message($student, $course, $days);

            if ($sent) {
                $totalnotified++;
                // Record the sent notification in the log.
                $logentry = (object)[
                    'userid' => $student->id,
                    'courseid' => $course->id,
                    'timesent' => $now,
                ];
                $DB->insert_record('local_inactivitynotifier_sent', $logentry);
                mtrace("  ✓ Notified: {$student->firstname} {$student->lastname} ({$student->email})");
            } else {
                mtrace("  ✗ Failed to notify: {$student->email}");
            }
        }
        $rs->close();

        mtrace("local_inactivitynotifier: task completed. Total notified: {$totalnotified}");
    }
}
