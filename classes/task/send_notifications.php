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

        // ── Check if plugin is enabled ────────────────────────────────────────
        $enabled = get_config('local_inactivitynotifier', 'enabled');
        if (!$enabled) {
            mtrace('local_inactivitynotifier: plugin disabled, skipping execution.');
            return;
        }

        $days        = (int) get_config('local_inactivitynotifier', 'inactivedays') ?: 7;
        $onlyvisible = (bool) get_config('local_inactivitynotifier', 'onlyvisible');

        // ── Build course query ─────────────────────────────────────────────────
        $where = 'id <> :siteid';
        $params = ['siteid' => SITEID];
        if ($onlyvisible) {
            $where .= ' AND visible = 1';
        }

        // Use recordset to avoid loading all courses into memory at once.
        $rs = $DB->get_recordset_select('course', $where, $params);
        $totalnotified = 0;

        foreach ($rs as $course) {
            mtrace("Processing course: [{$course->id}] {$course->fullname}");

            $inactiveusers = local_inactivitynotifier_get_inactive_users($course->id, $days);

            if (empty($inactiveusers)) {
                mtrace("  → No inactive users.");
                continue;
            }

            foreach ($inactiveusers as $student) {
                $sent = local_inactivitynotifier_send_message($student, $course, $days);

                if ($sent) {
                    $totalnotified++;
                    mtrace("  ✓ Notified: {$student->firstname} {$student->lastname} ({$student->email})");
                } else {
                    mtrace("  ✗ Failed to notify: {$student->email}");
                }
            }
        }
        $rs->close();

        mtrace("local_inactivitynotifier: task completed. Total notified: {$totalnotified}");
    }
}
