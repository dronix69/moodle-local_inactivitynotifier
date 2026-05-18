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
 * English language strings for the Inactivity Notifier plugin.
 *
 * @package   local_inactivitynotifier
 * @copyright 2026 Daniel Ferrada
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['message_body'] = 'Hi {$a->firstname},

We noticed you haven\'t visited the course "{$a->coursename}" for {$a->days} days.

Don\'t fall behind! Click the link below to continue your learning:
{$a->courseurl}

See you soon!';
$string['message_body_html'] = '<p>Hi <strong>{$a->firstname}</strong>,</p>
<p>We noticed you haven\'t visited the course <strong>"{$a->coursename}"</strong> for <strong>{$a->days} days</strong>.</p>
<p>Don\'t fall behind! Click the link below to continue your learning:</p>
<p><a href="{$a->courseurl}">{$a->courseurl}</a></p>
<p>See you soon! 👋</p>';
$string['message_small'] = 'You have been inactive in {$a} for several days.';
$string['message_subject'] = 'We miss you in {$a}!';
$string['mode_both'] = 'Popup + Email';
$string['mode_email_only'] = 'Email only';
$string['mode_popup_only'] = 'Popup only';
$string['pluginname'] = 'Inactivity Notifier';
$string['privacy:metadata'] = 'The Inactivity Notifier plugin does not store any personal data. It only reads existing Moodle access logs to determine inactivity.';
$string['setting_email_body'] = 'Custom email body (HTML)';
$string['setting_email_body_desc'] = 'Custom HTML body for the email. Leave empty to use the default. Available variables: {{firstname}}, {{coursename}}, {{days}}, {{courseurl}}';
$string['setting_email_subject'] = 'Custom email subject';
$string['setting_email_subject_desc'] = 'Custom subject for the email. Leave empty to use the default. Available variables: {{firstname}}, {{coursename}}, {{days}}, {{courseurl}}';
$string['setting_enabled'] = 'Enable plugin';
$string['setting_enabled_desc'] = 'When disabled, no notifications will be sent.';
$string['setting_inactivedays'] = 'Days of inactivity before notifying';
$string['setting_inactivedays_desc'] = 'Number of days a student must be inactive before receiving a notification.';
$string['setting_notification_mode'] = 'Notification mode';
$string['setting_notification_mode_desc'] = 'Select how notifications are delivered to students. "Both" respects each user\'s messaging preferences.';
$string['setting_onlyvisible'] = 'Only notify in visible courses';
$string['setting_onlyvisible_desc'] = 'If checked, hidden courses will be ignored.';
$string['task_send_notifications'] = 'Send inactivity notifications to students';
