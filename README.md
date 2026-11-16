# BigBlueButton BN Experience - Data Hub

## Documentation

| Document | Audience | Description |
| --- | --- | --- |
| [docs/ADMIN_GUIDE.md](docs/ADMIN_GUIDE.md) | Administrators | Supported versions, prerequisites, installation, reports, and troubleshooting |
| [docs/DEVELOPER_GUIDE.md](docs/DEVELOPER_GUIDE.md) | Developers | `bbbext` hook integration, event ingestion, persistence, reports, and extension practices |
| [docs/DATA_AND_PRIVACY.md](docs/DATA_AND_PRIVACY.md) | Administrators and developers | Analytics data model, access control, retention, Privacy API, and migrations |

A Moodle BigBlueButton extension plugin that captures meeting analytics and provides an activity dashboard plus attendance and engagement reports.

## Description

This plugin extends the BigBlueButton activity module by capturing real-time meeting data via BigBlueButton's analytics callback. It stores attendance records, engagement metrics (chat messages, talk time, hand raises, emoji reactions, poll votes), and presents this data through comprehensive Moodle Reportbuilder reports.

**Note:** This plugin requires the parent `bbbext_bnx` (BN Experience) plugin to be installed and enabled.

## Features

- **Attendance Tracking**: Records who joined, when they joined/left, and session duration
- **Engagement Metrics**: Tracks chat messages, talk count, talk time, hand raises, emoji reactions, and poll votes
- **Reportbuilder Integration**: Uses Moodle's Reportbuilder for customizable, filterable, downloadable reports
- **Activity Dashboard**: Summarizes attendance and engagement across sessions, with charts and a selectable session overview
- **Attendance Report**: Shows meeting participation with join/leave times and duration
- **Engagement Report**: Displays interaction metrics for measuring student participation quality
- **Capability-Based Access**: Only authorized users (teachers, managers, admins) can view reports
- **GDPR Compliant**: Full Privacy API support for data export and deletion

## Requirements

- Moodle 5.3 (the current `1.2-beta.2` beta release)
- BigBlueButton plugin (`mod_bigbluebuttonbn`)
- BN Experience plugin (`bbbext_bnx` 1.3 or later; minimum version code `2026110500`) - must be enabled
- BigBlueButton server with analytics callback enabled (`meetingevents_enabled`)

## Installation

1. Copy the plugin to `mod/bigbluebuttonbn/extension/bnx_datahub/`
2. Visit Site Administration > Notifications to complete installation
3. Ensure a compatible `bbbext_bnx` 1.3 release is enabled
4. Enable meeting events: Site Administration > Plugins > Activity modules > BigBlueButton > General settings > `meetingevents_enabled`
5. Open **Dashboard +** from the BigBlueButton activity navigation to view the activity summary and session overview.

## Sidecar contract

As a subplugin of `mod_bigbluebuttonbn`, `bbbext_bnx_datahub` follows a strict sidecar contract: it MUST NOT write to settings owned by other components. In particular it never enables `bigbluebuttonbn_meetingevents_enabled` from its install, upgrade, observer or `on_enable` code paths. Operators are responsible for enabling that flag on the parent plugin during installation. DataHub also requires the parent `bbbext_bnx` sidecar to remain enabled; if BNX is disabled, DataHub self-disables in its own namespace and surfaces the prerequisite via both its own settings page and a status check at *Site administration > Server > Status* (`bbbext_bnx_datahub_bnx_enabled`). The meeting-events prerequisite remains visible on the same DataHub settings page and there as `bbbext_bnx_datahub_meetingevents_enabled`, while the legacy BN Reports conflict is surfaced as `bbbext_bnx_datahub_bnreports_conflict`. Conformance tests in `tests/meetingevents_enablement_test.php` pin the no-cross-plugin-write contract plus the sidecar-owned prerequisite checks.

## Capabilities

| Capability | Description | Default Roles |
|------------|-------------|---------------|
| `bbbext/bnx_datahub:viewreports` | View attendance and engagement reports | Manager, Editing Teacher, Admin |

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

### Reportbuilder Entities

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
- User ID and display name
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
6. **Report Display**: Reportbuilder renders the data with columns, filters, and export options

## Privacy

This plugin stores personal user data for meeting analytics. Data stored includes:
- User ID and display name
- Meeting attendance timestamps
- Engagement metrics (chat count, talk time, etc.)

The plugin implements Moodle's Privacy API:
- Users can export their attendance and engagement data
- User data is deleted upon GDPR deletion requests
- Data is associated with the activity context

See `classes/privacy/provider.php` for the complete privacy implementation.

## License

GNU GPL v3 or later - http://www.gnu.org/copyleft/gpl.html

## Author

Jesus Federico (jesus [at] blindsidenetworks [dt] com)

## Copyright

2025 onwards, Blindside Networks Inc
