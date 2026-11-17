# BigBlueButton BN Experience - Data Hub

## Documentation

| Document | Audience | Description |
| --- | --- | --- |
| [docs/ADMIN_GUIDE.md](docs/ADMIN_GUIDE.md) | Administrators | Supported versions, prerequisites, installation, reports, and troubleshooting |
| [docs/DEVELOPER_GUIDE.md](docs/DEVELOPER_GUIDE.md) | Developers | `bbbext` hook integration, event ingestion, persistence, reports, and extension practices |
| [docs/DATA_AND_PRIVACY.md](docs/DATA_AND_PRIVACY.md) | Administrators and developers | Analytics data model, access control, retention, Privacy API, and migrations |

A free Moodle BigBlueButton extension that stores meeting analytics and provides an activity dashboard plus attendance and engagement reports.

## Description

DataHub receives analytics callbacks after a BigBlueButton session ends. It stores attendance records and engagement metrics, including chat messages, talk time, hand raises, emoji reactions, and poll votes, and presents them in a dashboard and Moodle Report Builder reports. Repeated callbacks can update stored data. DataHub does not expose a separate external reporting API.

**Note:** This plugin requires the parent `bbbext_bnx` (BN Experience) plugin to be installed and enabled.

## Features

- **Attendance Tracking**: Records who joined, when they joined/left, and attendance duration
- **Engagement Metrics**: Tracks chat messages, talk count, talk time, hand raises, emoji reactions, and poll votes
- **Report Builder Integration**: Uses Moodle Report Builder for customizable, filterable, downloadable reports
- **Activity Dashboard**: Summarizes attendance and engagement across sessions, with charts and a selectable session overview
- **Attendance Report**: Shows meeting participation with join/leave times and duration
- **Engagement Report**: Displays interaction metrics for measuring student participation quality
- **Session Exclusions**: Exclude and restore sessions in the dashboard and built-in reports without deleting their data
- **Capability-Based Access**: Restricts report access and exclusion actions using Moodle capabilities and group visibility
- **Privacy API**: Supports export and deletion of user-associated attendance and engagement data

## Requirements

- Moodle 5.3 (see [version.php](version.php))
- BigBlueButton plugin (`mod_bigbluebuttonbn`)
- BN Experience plugin (`bbbext_bnx` 1.3 or later; minimum version code `2026110500`) - must be enabled
- A BigBlueButton server configured to send analytics callbacks, with meeting events enabled in the Moodle BigBlueButton settings (`meetingevents_enabled`)

## Installation

1. Install and enable a compatible `bbbext_bnx` 1.3 or later release.
2. Enable meeting events: Site administration > Plugins > Activity modules > BigBlueButton > General settings > `meetingevents_enabled`.
3. Copy DataHub to `public/mod/bigbluebuttonbn/extension/bnx_datahub/` under the Moodle root.
4. Visit Site administration > Notifications to complete installation and confirm DataHub is enabled.
5. Open **Dashboard +** from the BigBlueButton activity navigation to view the activity summary and session overview.

## Prerequisites and status checks

DataHub does not change settings owned by BigBlueButton or BNX. An administrator must enable meeting events in BigBlueButton. If BNX is disabled, DataHub disables itself and must be re-enabled after the prerequisite is restored.

At **Site administration > Server > Status**, DataHub reports on BNX and meeting-events availability, conflicts with the legacy BN Reports plugin, pending BN Reports migration, and historical meeting-duration discrepancies. See the [administrator guide](docs/ADMIN_GUIDE.md) for troubleshooting and recovery.

## Capabilities

| Capability | Description | Default Roles |
|------------|-------------|---------------|
| `bbbext/bnx_datahub:viewreports` | View attendance and engagement reports | Manager, Editing Teacher, Admin |
| `bbbext/bnx_datahub:manageexclusions` | Exclude and restore sessions in an activity | Manager, Editing Teacher |

When first registered, these capabilities clone permissions from `moodle/course:manageactivities`, including custom roles and context overrides. Review the resulting grants. Group visibility also limits which sessions a viewer can access; see the [administrator guide](docs/ADMIN_GUIDE.md).

## Exclusions and custom reports

Users with the exclusion capability can exclude a session from the dashboard and built-in attendance and engagement reports, including downloads, and restore it later. Exclusion does not delete data. Custom Report Builder reports can still include excluded sessions; use the **Excluded from default reports** column or filter to control their visibility.

Disable all schedules for custom reports using DataHub datasources. Standard scheduled and ad-hoc sends are rejected because recipient access cannot be reliably enforced; a rejected recurring schedule can interrupt the scheduler run and delay other reports. Interactive reports and downloads remain subject to the viewer's permissions and group visibility.

## Maintenance tools

Run the following commands from the Moodle root. Back up the site before migration or applying changes, and review the [administrator guide](docs/ADMIN_GUIDE.md) for prerequisites and detailed procedures.

| Tool | Purpose |
| --- | --- |
| `php public/mod/bigbluebuttonbn/extension/bnx_datahub/cli/migrate_bnreports.php` | Migrate legacy BN Reports data. |
| `php public/mod/bigbluebuttonbn/extension/bnx_datahub/cli/repair_durations.php` | Preview supported historical duration corrections; add `--apply` after review. |
| `php public/mod/bigbluebuttonbn/extension/bnx_datahub/cli/cleanup_orphan_attendees.php` | Preview orphan attendees; add `--orphan-meetings` to select orphan meetings and their attendees, and `--apply` after review to delete them. |

Cleanup is irreversible. Stop concurrent activity or course deletions before reviewing orphan meetings.

## Architecture

### Database Tables

| Table | Purpose |
|-------|---------|
| `bbbext_bnx_datahub_meeting` | Stores meeting session data (start/end times, duration, metadata) |
| `bbbext_bnx_datahub_meeting_user` | Stores attendee data and engagement metrics per meeting |

### Key Classes

| Class | Purpose |
|-------|---------|
| `logger` | Core data storage - logs meeting and attendee data to database |
| `broker_meeting_events_addons` | Processes webhook events from BigBlueButton server |
| `navigation_append_addon` | Adds the dashboard link to activity navigation without replacing other navigation contributions |
| `dashboard_service` and `dashboard` | Build and render activity-level dashboard summaries and session details |
| `privacy\provider` | GDPR Privacy API implementation |

### Report Builder Entities

| Entity | Purpose |
|--------|---------|
| `meeting` | Meeting data columns (name, start time, end time, duration) |
| `attendee` | Attendee data columns (name, role, join/leave times, engagement metrics) |
| `room` | BigBlueButton activity columns (room name, ID) |

### System Reports

| Report | Purpose |
|--------|---------|
| `attendance` | Shows who attended meetings and for how long |
| `engagement` | Shows participant interaction metrics (chats, talks, hand raises, etc.) |

## Data Captured

### Meeting Data
- External/Internal meeting IDs
- Meeting name
- Start and end times
- Duration
- Raw metadata from BigBlueButton

### Attendee Data
- User ID when known and display name (guests may not have a Moodle user ID)
- Attendee role (`attendeerole`, moderator/viewer)
- Join and leave timestamps
- Elapsed time in meeting
- **Engagement Metrics:**
  - `engchats` - Number of chat messages sent
  - `engtalks` - Number of times unmuted to speak
  - `engtalktime` - Total seconds of talk time
  - `engraisehand` - Number of hand raises
  - `engemojis` - Number of emoji reactions
  - `engpollvotes` - Number of poll responses
  - `attendeescore` - Calculated engagement score

## Testing

### Running PHPUnit Tests

Run these commands from the Moodle root with a dedicated PHPUnit database and
data directory configured. Initializing PHPUnit resets its configured test data.

```bash
# Initialize PHPUnit (if not already done)
php admin/tool/phpunit/cli/init.php

# Run all plugin tests
vendor/bin/phpunit --test-suffix _test.php public/mod/bigbluebuttonbn/extension/bnx_datahub/tests/

# Run individual test files
vendor/bin/phpunit public/mod/bigbluebuttonbn/extension/bnx_datahub/tests/logger_test.php
vendor/bin/phpunit public/mod/bigbluebuttonbn/extension/bnx_datahub/tests/privacy_provider_test.php
vendor/bin/phpunit public/mod/bigbluebuttonbn/extension/bnx_datahub/tests/broker_meeting_events_addons_test.php
```

### Test Coverage

PHPUnit tests cover callback ingestion and storage, dashboard services and
rendering, reports and access checks, navigation, prerequisites, migration,
backup and restore, and the Privacy API. The test files in `tests/` are the
authoritative inventory; use `--list-tests` with the command above to inspect it.

### Behat Features

The feature files in `tests/behat/` cover prerequisites, navigation, reports,
permissions, stored data, and dashboard session selection without JavaScript.
They are the authoritative inventory of browser scenarios.

#### Running Behat Tests

Configure a dedicated Behat database and data directory before initialization;
initialization resets the configured Behat test data.

```bash
# Initialize Behat (if not already done)
php admin/tool/behat/cli/init.php

# Run all bnx_datahub tests
vendor/bin/behat --config /path/to/behatdata/behatrun/behat/behat.yml --tags @bbbext_bnx_datahub

# Run specific feature file
vendor/bin/behat --config /path/to/behatdata/behatrun/behat/behat.yml \
  public/mod/bigbluebuttonbn/extension/bnx_datahub/tests/behat/attendance_report.feature

# Run with verbose output
vendor/bin/behat --config /path/to/behatdata/behatrun/behat/behat.yml --tags @bbbext_bnx_datahub -v
```

Custom steps are defined in `tests/behat/behat_bbbext_bnx_datahub.php`.

## How It Works

1. **Meeting Start**: When a BigBlueButton meeting starts, the server begins tracking participation
2. **Analytics Callback**: When the meeting ends, BigBlueButton sends analytics data to Moodle via webhook
3. **Event Processing**: `broker_meeting_events_addons` receives the webhook and extracts meeting data
4. **Data Storage**: `logger` class stores meeting and attendee records in the database
5. **Report Access**: Teachers access reports via links in the activity navigation
6. **Report Display**: Report Builder renders the data with columns, filters, and export options

## Privacy

This plugin stores personal user data for meeting analytics. Data stored includes:
- User ID and display name
- Meeting attendance timestamps
- Engagement metrics (chat count, talk time, etc.)

The Moodle Privacy API supports export and deletion of user-associated attendance and engagement records in the activity context. Excluded sessions remain subject to privacy exports and deletion requests. DataHub does not impose a separate retention schedule.

Analytics are included in backups only when the activity's user data is included and the backup is not anonymized. On restore, attendees who cannot be mapped to Moodle users are omitted; potentially identifying raw payloads for affected meetings are also discarded. Deleting an activity or course removes its analytics after any recycle-bin backup, subject to deferred deletion and backup retention.

See the [data and privacy guide](docs/DATA_AND_PRIVACY.md) for guest data, restore, retention, and security details.

## Support

Report bugs and request improvements in the [DataHub issue tracker](https://github.com/blindsidenetworks-moodle/moodle-bbbext_bnx_datahub/issues). Do not include personal data or credentials in reports.

## License

GNU GPL v3 or later. See [COPYING](COPYING).

## Author

Jesus Federico (jesus [at] blindsidenetworks [dt] com)

## Copyright

2025 onwards, Blindside Networks Inc
