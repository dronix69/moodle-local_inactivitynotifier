# local_inactivitynotifier — Inactivity Notifier for Moodle

Moodle local plugin that detects inactive students in courses and sends them a notification (popup + email) to encourage them to resume learning. Supports custom email templates configurable from the admin panel.

---

## Folder Structure

```
local/inactivitynotifier/
│
├── version.php                          ← Plugin metadata (version, requires)
├── lib.php                              ← Core reusable functions
├── settings.php                         ← Admin settings page
├── thirdpartylibs.xml                   ← Third-party library declarations
├── README.md                            ← This documentation
│
├── db/
│   ├── install.xml                      ← Database table definition (sent log)
│   ├── messages.php                     ← Message provider registration
│   ├── tasks.php                        ← Cron task definition
│   └── upgrade.php                      ← Upgrade script for new tables
│
├── classes/
│   ├── task/
│   │   └── send_notifications.php       ← Scheduled task (cron logic)
│   └── privacy/
│       └── provider.php                 ← GDPR declaration (null_provider)
│
└── lang/
    ├── en/
    │   └── local_inactivitynotifier.php ← English language strings
    └── es/
        └── local_inactivitynotifier.php ← Spanish language strings
```

---

## Installation

1. Copy the `inactivitynotifier` folder into `/moodle/local/`
2. Log in to Moodle as administrator.
3. Go to **Site administration → Notifications** and confirm the installation.
4. Configure the plugin at **Admin → Plugins → Local plugins → Inactivity Notifier**.

---

## Configuration

| Setting | Description | Default |
|---|---|---|
| Days of inactivity | Days without access before sending a notification | **7** |
| Remind frequency | Minimum days to wait before resending to the same student in the same course | **7** |
| Enable plugin | Enable or disable sending | **Yes** |
| Only visible courses | Ignore hidden courses | **Yes** |
| Excluded courses | Comma-separated list of course IDs to exclude | *(empty)* |
| Excluded categories | Comma-separated list of category IDs to exclude | *(empty)* |
| Notification mode | How to deliver: Popup + Email, Email only, or Popup only | **Popup + Email** |
| Custom email subject | Subject template (variables: `{{firstname}}`, `{{coursename}}`, `{{days}}`, `{{courseurl}}`) | *(empty = uses default)* |
| Custom email body (HTML) | HTML body template (same variables available, WYSIWYG editor) | *(empty = uses default)* |

---

## Custom Email Template

You can customize the email subject and body from the admin settings. Use these placeholders:

| Variable | Replaced with |
|---|---|
| `{{firstname}}` | Student's first name |
| `{{coursename}}` | Course full name |
| `{{days}}` | Number of days of inactivity |
| `{{courseurl}}` | Direct URL to the course |

If both fields are left empty, the default language strings are used. If only one is filled, the other falls back to the default.

Example custom template:

**Subject:**
```
Don't forget {{coursename}}, {{firstname}}!
```

**Body (HTML):**
```html
<h2>Hi {{firstname}}!</h2>
<p>We noticed you haven't visited <strong>{{coursename}}</strong> for {{days}} days.</p>
<p><a href="{{courseurl}}">Click here to continue your learning</a></p>
<p>See you soon!</p>
```

---

## Scheduled Task (Cron)

The `send_notifications` task runs **daily at 8:00 AM**.

Run it manually from the terminal:

```bash
php admin/cli/scheduled_task.php --execute='\local_inactivitynotifier\task\send_notifications'
```

---

## How It Works

```
Daily cron (8:00 AM)
       │
       ▼
Plugin enabled? ──NO──► End
       │ YES
       ▼
Single optimized SQL query joins: users, enrolments, roles (student),
course, lastaccess, and sent log table
       │
       ▼
Filters applied:
  ├─ Active enrolment, not suspended/deleted
  ├─ Last access older than N days (or never accessed)
  ├─ Visible courses only (optional)
  ├─ Excluded courses/categories removed
  ├─ Completed courses skipped (if completion enabled)
  └─ Already notified within remind frequency? → Skip
       │
       ▼
Build message:
  ├─ Custom template configured? → Replace {{variables}} with actual values
  └─ No custom template → Use default language strings
       │
       ▼
Check notification mode:
  ├─ Email only → Send via email_to_user() (direct email, no popup)
  ├─ Popup only → Send via message_send() (Moodle popup only)
  └─ Both → Send via message_send() (follows user preferences)
       │
       ▼
Record sent notification in local_inactivitynotifier_sent table
       │
       ▼
Log result in cron output
```

---

## Privacy (GDPR)

This plugin stores minimal data to prevent duplicate/reminder spam:
- **`local_inactivitynotifier_sent` table**: logs `userid`, `courseid`, and `timesent` for each notification sent.
- Users can request **export** or **deletion** of their data via Moodle's privacy API.
- The plugin implements `metadata\provider` and `request\plugin\provider` with full export and deletion support.

---

## Requirements

- Moodle 4.1 or higher
- PHP 8.1 +
- Cron task configured on the server

---

## License

GNU General Public License v3 or later — http://www.gnu.org/copyleft/gpl.html
