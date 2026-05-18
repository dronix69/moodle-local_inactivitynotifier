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
 * Main functions for the Inactivity Notifier plugin.
 *
 * @package   local_inactivitynotifier
 * @copyright 2026 Daniel Ferrada
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * Obtiene los estudiantes inactivos de un curso según los días configurados.
 *
 * @param int $courseid  ID del curso a revisar.
 * @param int $days      Días de inactividad permitidos.
 * @return array         Lista de objetos usuario inactivos.
 */
function local_inactivitynotifier_get_inactive_users(int $courseid, int $days): array {
    global $DB;

    $threshold = time() - ($days * DAYSECS);

    // Students enrolled in the course with active enrolment and student role.
    $sql = "SELECT u.id, u.firstname, u.lastname, u.email,
                   COALESCE(ul.timeaccess, 0) AS lastaccess
              FROM {user} u
              JOIN {user_enrolments} ue ON ue.userid = u.id AND ue.status = 0
              JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = :courseid
              JOIN {role_assignments} ra ON ra.userid = u.id
              JOIN {context} ctx ON ctx.id = ra.contextid
                   AND ctx.contextlevel = :contextlevel
                   AND ctx.instanceid = :instanceid
              JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
         LEFT JOIN {user_lastaccess} ul ON ul.userid = u.id AND ul.courseid = :courseid2
             WHERE u.deleted = 0
               AND u.suspended = 0
               AND (ul.timeaccess IS NULL OR ul.timeaccess < :threshold)";

    $params = [
        'courseid'     => $courseid,
        'courseid2'    => $courseid,
        'contextlevel' => CONTEXT_COURSE,
        'instanceid'   => $courseid,
        'threshold'    => $threshold,
    ];

    return $DB->get_records_sql($sql, $params);
}

/**
 * Sends a notification to an inactive student.
 * Supports custom templates and delivery mode (email only, popup only, or both).
 *
 * @param stdClass $student  Recipient user object.
 * @param stdClass $course   Course object.
 * @param int      $days     Days of inactivity.
 * @return bool              true if the message was sent successfully.
 */
function local_inactivitynotifier_send_message(stdClass $student, stdClass $course, int $days): bool {
    global $CFG;

    $courseurl = (string) new moodle_url('/course/view.php', ['id' => $course->id]);

    $subject    = get_string('message_subject', 'local_inactivitynotifier', $course->fullname);
    $bodyplain  = get_string('message_body', 'local_inactivitynotifier', [
        'firstname'  => $student->firstname,
        'coursename' => $course->fullname,
        'days'       => $days,
        'courseurl'  => $courseurl,
    ]);
    $bodyhtml   = get_string('message_body_html', 'local_inactivitynotifier', [
        'firstname'  => $student->firstname,
        'coursename' => $course->fullname,
        'days'       => $days,
        'courseurl'  => $courseurl,
    ]);

    $customsubject = get_config('local_inactivitynotifier', 'email_subject');
    $custombody    = get_config('local_inactivitynotifier', 'email_body');
    $mode          = get_config('local_inactivitynotifier', 'notification_mode');

    $vars = [
        '{{firstname}}'  => $student->firstname,
        '{{coursename}}' => $course->fullname,
        '{{days}}'       => $days,
        '{{courseurl}}'  => $courseurl,
    ];

    if (!empty($customsubject)) {
        $subject = str_replace(array_keys($vars), array_values($vars), $customsubject);
    }

    if (!empty($custombody)) {
        $bodyhtml = str_replace(array_keys($vars), array_values($vars), $custombody);
        $bodyplain = html_to_text($bodyhtml);
    }

    if ($mode === 'email_only') {
        $noreply = core_user::get_noreply_user();
        return email_to_user($student, $noreply, $subject, $bodyplain, $bodyhtml);
    }

    $message = new \core\message\message();
    $message->component        = 'local_inactivitynotifier';
    $message->name             = 'inactivity_notification';
    $message->userfrom         = core_user::get_noreply_user();
    $message->userto           = $student;
    $message->subject          = $subject;
    $message->fullmessage      = $bodyplain;
    $message->fullmessageformat = FORMAT_HTML;
    $message->fullmessagehtml   = $bodyhtml;
    $message->smallmessage      = get_string('message_small', 'local_inactivitynotifier', $course->fullname);
    $message->notification      = 1;

    return (bool) message_send($message);
}
