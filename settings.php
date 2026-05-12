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
 * Admin settings for the Inactivity Notifier plugin.
 *
 * @package   local_inactivitynotifier
 * @copyright 2026 Daniel Ferrada
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_inactivitynotifier',
        get_string('pluginname', 'local_inactivitynotifier')
    );

    // ── Days of inactivity before notifying ──────────────────────────────────
    $settings->add(new admin_setting_configtext(
        'local_inactivitynotifier/inactivedays',
        get_string('setting_inactivedays', 'local_inactivitynotifier'),
        get_string('setting_inactivedays_desc', 'local_inactivitynotifier'),
        7,          // Default: 7 days.
        PARAM_INT
    ));

    // ── Enable / Disable plugin globally ─────────────────────────────────────
    $settings->add(new admin_setting_configcheckbox(
        'local_inactivitynotifier/enabled',
        get_string('setting_enabled', 'local_inactivitynotifier'),
        get_string('setting_enabled_desc', 'local_inactivitynotifier'),
        1           // Enabled by default.
    ));

    // ── Only notify in visible courses ───────────────────────────────────────
    $settings->add(new admin_setting_configcheckbox(
        'local_inactivitynotifier/onlyvisible',
        get_string('setting_onlyvisible', 'local_inactivitynotifier'),
        get_string('setting_onlyvisible_desc', 'local_inactivitynotifier'),
        1
    ));

    // ── Notification mode ────────────────────────────────────────────────────
    $settings->add(new admin_setting_configselect(
        'local_inactivitynotifier/notification_mode',
        get_string('setting_notification_mode', 'local_inactivitynotifier'),
        get_string('setting_notification_mode_desc', 'local_inactivitynotifier'),
        'both',
        [
            'both'       => get_string('mode_both', 'local_inactivitynotifier'),
            'email_only' => get_string('mode_email_only', 'local_inactivitynotifier'),
            'popup_only' => get_string('mode_popup_only', 'local_inactivitynotifier'),
        ]
    ));

    // ── Custom email subject template ────────────────────────────────────────
    $settings->add(new admin_setting_configtext(
        'local_inactivitynotifier/email_subject',
        get_string('setting_email_subject', 'local_inactivitynotifier'),
        get_string('setting_email_subject_desc', 'local_inactivitynotifier'),
        '',
        PARAM_RAW
    ));

    // ── Custom email body template (HTML) ────────────────────────────────────
    $settings->add(new admin_setting_configtextarea(
        'local_inactivitynotifier/email_body',
        get_string('setting_email_body', 'local_inactivitynotifier'),
        get_string('setting_email_body_desc', 'local_inactivitynotifier'),
        ''
    ));

    $ADMIN->add('localplugins', $settings);
}
