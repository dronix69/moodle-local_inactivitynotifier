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
│   ├── messages.php                     ← Message provider registration
│   └── tasks.php                        ← Cron task definition
│
├── classes/
│   ├── task/
│   │   └── send_notifications.php       ← Scheduled task (cron logic)
│   └── privacy/
│       └── provider.php                 ← GDPR declaration (null_provider)
│
└── lang/
    └── en/
        └── local_inactivitynotifier.php ← English language strings
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
| Enable plugin | Enable or disable sending | **Yes** |
| Only visible courses | Ignore hidden courses | **Yes** |
| Notification mode | How to deliver: Popup + Email, Email only, or Popup only | **Popup + Email** |
| Custom email subject | Subject template (variables: `{{firstname}}`, `{{coursename}}`, `{{days}}`, `{{courseurl}}`) | *(empty = uses default)* |
| Custom email body (HTML) | HTML body template (same variables available) | *(empty = uses default)* |

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
Iterate through all active courses (using recordset for memory efficiency)
       │
       ▼
For each course: find students (active enrolment only, not suspended/deleted)
whose last access is older than N days
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
Log result in cron output
```

---

## Privacy (GDPR)

This plugin **does not store any personal data**.
It only reads the `mdl_user_lastaccess` table which already exists in Moodle.
**It never inserts, updates, or deletes any records** in the Moodle database.
Implements `null_provider` according to the Moodle Privacy API.

---

## Requirements

- Moodle 4.1 or higher
- PHP 7.4+
- Cron task configured on the server

---

## License

GNU General Public License v3 or later — http://www.gnu.org/copyleft/gpl.html
