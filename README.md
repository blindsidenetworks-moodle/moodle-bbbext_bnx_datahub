# BigBlueButton BN Experience - Data Hub

A Moodle BigBlueButton extension plugin that captures meeting analytics and provides attendance and engagement reports.

## Description

This plugin extends the BigBlueButton activity module by capturing real-time meeting data via BigBlueButton's analytics callback. It stores attendance records, engagement metrics (chat messages, talk time, hand raises, emoji reactions, poll votes), and presents this data through comprehensive Moodle Reportbuilder reports.

**Note:** This plugin requires the parent `bbbext_bnx` (BN Experience) plugin to be installed and enabled.

## Features

- **Attendance Tracking**: Records who joined, when they joined/left, and session duration
- **Engagement Metrics**: Tracks chat messages, talk count, talk time, hand raises, emoji reactions, and poll votes
- **Reportbuilder Integration**: Uses Moodle's Reportbuilder for customizable, filterable, downloadable reports
- **Attendance Report**: Shows meeting participation with join/leave times and duration
- **Engagement Report**: Displays interaction metrics for measuring student participation quality
- **Capability-Based Access**: Only authorized users (teachers, managers, admins) can view reports
- **GDPR Compliant**: Full Privacy API support for data export and deletion

## Requirements

- Moodle 5.1 or later
- BigBlueButton plugin (`mod_bigbluebuttonbn`)
- BN Experience plugin (`bbbext_bnx`) - must be enabled
- BigBlueButton server with analytics callback enabled (`meetingevents_enabled`)

## Installation

1. Copy the plugin to `mod/bigbluebuttonbn/extension/bnx_datahub/`
2. Visit Site Administration > Notifications to complete installation
3. Ensure `bbbext_bnx` is enabled
4. Enable meeting events: Site Administration > Plugins > Activity modules > BigBlueButton > General settings > `meetingevents_enabled`

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
| `navigation_override_addon` | Adds report links to activity navigation |
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
- Role (moderator/viewer)
- Join and leave timestamps
- Elapsed time in meeting
- **Engagement Metrics:**
  - `engchats` - Number of chat messages sent
  - `engtalks` - Number of times unmuted to speak
  - `engtalktime` - Total seconds of talk time
  - `engraisehand` - Number of hand raises
  - `engemojis` - Number of emoji reactions
  - `engpollvotes` - Number of poll responses
  - `score` - Calculated engagement score

## Testing

### Running PHPUnit Tests

```bash
# Initialize PHPUnit (if not already done)
php admin/tool/phpunit/cli/init.php

# Run all tests
vendor/bin/phpunit public/mod/bigbluebuttonbn/extension/bnx_datahub/tests/

# Run individual test files
vendor/bin/phpunit public/mod/bigbluebuttonbn/extension/bnx_datahub/tests/logger_test.php
vendor/bin/phpunit public/mod/bigbluebuttonbn/extension/bnx_datahub/tests/privacy_provider_test.php
vendor/bin/phpunit public/mod/bigbluebuttonbn/extension/bnx_datahub/tests/broker_meeting_events_addons_test.php
```

### Test Coverage

The plugin includes approximately 71 PHPUnit tests across 9 test files:

#### logger_test.php (10 tests)
Tests the core data logging functions for meetings and attendees.

| Test | Purpose |
|------|---------|
| `test_log_meeting_creates_new_record` | **Core functionality** - New meeting records are created |
| `test_log_meeting_updates_existing_record` | **Data integrity** - Existing records updated, not duplicated |
| `test_log_meeting_returns_false_when_bnx_missing` | **Dependency** - Graceful failure without BNX |
| `test_log_meeting_stores_json_metadata` | **Data format** - JSON metadata preserved |
| `test_log_meeting_user_creates_new_record` | **Core functionality** - Attendee records created |
| `test_log_meeting_user_updates_existing_record` | **Data integrity** - Attendee records updated |
| `test_log_meeting_user_returns_false_when_meeting_missing` | **Error handling** - Graceful failure |
| `test_log_meeting_user_handles_multiple_attendees` | **Scale** - Multiple attendees per meeting |
| `test_log_meeting_user_stores_engagement_metrics` | **Engagement** - All metrics captured |
| `test_log_meeting_user_links_to_moodle_user` | **User mapping** - Links to Moodle user records |

#### privacy_provider_test.php (9 tests)
Tests GDPR/Privacy API compliance for user data handling.

| Test | Purpose |
|------|---------|
| `test_get_metadata_declares_tables` | **Legal compliance** - Data tables declared |
| `test_get_contexts_for_userid` | **GDPR** - Contexts with user data identified |
| `test_get_users_in_context` | **GDPR** - Users in context enumerated |
| `test_export_user_data` | **GDPR** - User data exportable |
| `test_delete_data_for_all_users_in_context` | **GDPR** - Bulk deletion works |
| `test_delete_data_for_user` | **GDPR** - Individual user deletion works |
| `test_delete_data_for_users` | **GDPR** - Multi-user deletion works |
| `test_export_includes_engagement_metrics` | **Completeness** - All data exported |
| `test_deletion_removes_all_user_records` | **Thoroughness** - No orphaned data |

#### broker_meeting_events_addons_test.php (6 tests)
Tests the webhook handler that processes BigBlueButton events.

| Test | Purpose |
|------|---------|
| `test_class_implements_interface` | **Framework** - Proper addon integration |
| `test_process_action_returns_early_without_bnx` | **Dependency** - Requires BNX enabled |
| `test_process_action_logs_meeting_data` | **Core functionality** - Meeting events logged |
| `test_process_action_logs_attendee_data` | **Core functionality** - Attendee events logged |
| `test_process_action_handles_missing_data` | **Robustness** - Graceful error handling |
| `test_process_action_handles_multiple_attendees` | **Scale** - Batch processing works |

#### meeting_entity_test.php (5 tests)
Tests the Reportbuilder meeting entity definition.

| Test | Purpose |
|------|---------|
| `test_get_default_tables` | **Structure** - Correct database table |
| `test_get_default_entity_title` | **UI** - Proper display title |
| `test_initialise` | **Framework** - Entity initializes correctly |
| `test_get_all_columns` | **Report structure** - All columns defined |
| `test_get_all_filters` | **Report filtering** - Filters available |

#### attendee_entity_test.php (9 tests)
Tests the Reportbuilder attendee entity with engagement columns.

| Test | Purpose |
|------|---------|
| `test_get_default_tables` | **Structure** - Correct database table |
| `test_get_default_entity_title` | **UI** - Proper display title |
| `test_initialise` | **Framework** - Entity initializes correctly |
| `test_get_all_columns` | **Report structure** - Basic columns defined |
| `test_get_engagement_columns` | **Engagement** - All engagement metrics as columns |
| `test_get_all_filters` | **Report filtering** - Filters available |
| `test_role_filter_exists` | **Filtering** - Can filter by role |
| `test_time_columns_exist` | **Attendance** - Join/leave time columns |
| `test_elapsed_time_column_exists` | **Duration** - Session duration column |

#### room_entity_test.php (6 tests)
Tests the Reportbuilder room (BigBlueButton activity) entity.

| Test | Purpose |
|------|---------|
| `test_get_default_tables` | **Structure** - Links to bigbluebuttonbn table |
| `test_get_default_entity_title` | **UI** - Proper display title |
| `test_initialise` | **Framework** - Entity initializes correctly |
| `test_get_all_columns` | **Report structure** - Room columns defined |
| `test_get_all_filters` | **Report filtering** - Filters available |
| `test_name_filter_exists` | **Filtering** - Can filter by room name |

#### attendance_report_test.php (7 tests)
Tests the attendance system report configuration.

| Test | Purpose |
|------|---------|
| `test_report_instantiation` | **Framework** - Report creates successfully |
| `test_report_columns` | **Structure** - Expected columns present |
| `test_report_filters` | **Filtering** - Expected filters present |
| `test_report_downloadable` | **Export** - Report can be downloaded |
| `test_report_empty_data` | **Edge case** - Works with no data |
| `test_can_view` | **Access** - View permission check |
| `test_report_with_bigbluebuttonbnid_parameter` | **Context** - Filters to specific activity |

#### engagement_report_test.php (8 tests)
Tests the engagement system report configuration.

| Test | Purpose |
|------|---------|
| `test_report_instantiation` | **Framework** - Report creates successfully |
| `test_report_columns` | **Structure** - Expected columns present |
| `test_report_filters` | **Filtering** - Expected filters present |
| `test_report_downloadable` | **Export** - Report can be downloaded |
| `test_report_empty_data` | **Edge case** - Works with no data |
| `test_can_view` | **Access** - View permission check |
| `test_report_with_bigbluebuttonbnid_parameter` | **Context** - Filters to specific activity |
| `test_engagement_metrics_columns` | **Engagement** - All metrics columns present |

#### navigation_override_addon_test.php (11 tests)
Tests the navigation addon that adds report links.

| Test | Purpose |
|------|---------|
| `test_class_instantiation` | **Framework** - Class creates successfully |
| `test_implements_interface` | **Framework** - Proper addon interface |
| `test_override_settings_navigation_method_exists` | **API** - Required method present |
| `test_requires_bnx_plugin` | **Dependency** - Checks BNX enabled |
| `test_checks_meetingevents_config` | **Config** - Requires meetingevents enabled |
| `test_checks_viewreports_capability` | **Security** - Capability check present |
| `test_attendance_report_url_format` | **Navigation** - Correct URL generated |
| `test_engagement_report_url_format` | **Navigation** - Correct URL generated |
| `test_adds_navigation_nodes` | **UI** - Links added to navigation |
| `test_excludes_recordings_only_instances` | **Context** - No reports for recordings-only |
| `test_completion_validate_for_managers` | **Admin** - Completion validation link |

### Behat Features

The plugin includes 38 Behat scenarios across 6 feature files for end-to-end browser testing:

#### bnx_dependency.feature (3 scenarios)
Tests the BNX parent plugin dependency behavior.

| Scenario | Purpose |
|----------|---------|
| `Data Hub plugin is listed when BNX is enabled` | **Admin UI** - Plugin visible in management |
| `Data Hub reports are accessible when BNX is enabled` | **Integration** - Reports work with BNX |
| `Standard BigBlueButton activity works without BNX` | **Compatibility** - Core BBB unaffected |

#### navigation.feature (5 scenarios)
Tests report link visibility in activity navigation.

| Scenario | Purpose |
|----------|---------|
| `Teacher can see report links in activity navigation when meeting events enabled` | **UI** - Links appear for teachers |
| `Teacher can navigate to attendance report from activity` | **Navigation** - Attendance link works |
| `Teacher can navigate to engagement report from activity` | **Navigation** - Engagement link works |
| `Report links not visible when meeting events disabled` | **Config** - Links hidden when disabled |
| `Student cannot see report links in activity navigation` | **Security** - Students don't see reports |

#### attendance_report.feature (5 scenarios)
Tests the attendance report functionality.

| Scenario | Purpose |
|----------|---------|
| `Teacher can view attendance report with meeting data` | **Core functionality** - Report displays data |
| `Teacher can view attendance report with no meeting data` | **Edge case** - Empty report works |
| `Attendance report shows correct user roles` | **Data accuracy** - Moderator/Viewer shown |
| `Attendance report shows multiple meetings` | **Multi-session** - All meetings displayed |
| `Students that did not attend are not shown` | **Data accuracy** - Only attendees listed |

#### engagement_report.feature (8 scenarios)
Tests the engagement report and metrics display.

| Scenario | Purpose |
|----------|---------|
| `Teacher can view engagement report with engagement data` | **Core functionality** - Report displays data |
| `Teacher can view engagement report with no data` | **Edge case** - Empty report works |
| `Engagement report shows chat activity` | **Metric** - Chat count displayed |
| `Engagement report shows talk time metrics` | **Metric** - Talk time displayed |
| `Engagement report shows poll participation` | **Metric** - Poll votes displayed |
| `Engagement report shows raised hand activity` | **Metric** - Hand raises displayed |
| `Engagement report shows emoji reactions` | **Metric** - Emoji count displayed |
| `Engagement report shows engagement scores` | **Metric** - Calculated scores displayed |

#### permissions.feature (11 scenarios)
Tests capability-based access control.

| Scenario | Purpose |
|----------|---------|
| `Editing teacher can view attendance report` | **Access** - Teachers authorized |
| `Editing teacher can view engagement report` | **Access** - Teachers authorized |
| `Manager can view attendance report` | **Access** - Managers authorized |
| `Manager can view engagement report` | **Access** - Managers authorized |
| `Student does not see attendance report link` | **Security** - Students denied |
| `Student does not see engagement report link` | **Security** - Students denied |
| `Admin can view attendance report` | **Access** - Admins authorized |
| `Admin can view engagement report` | **Access** - Admins authorized |
| `Non-editing teacher without capability does not see report links` | **Security** - Capability enforced |
| `Guest user cannot access attendance report` | **Security** - Login required |
| `Guest user cannot access engagement report` | **Security** - Login required |

#### data_storage.feature (6 scenarios)
Tests data persistence and integrity.

| Scenario | Purpose |
|----------|---------|
| `Meeting data is stored correctly` | **Data integrity** - Meetings saved |
| `Attendee data is stored correctly` | **Data integrity** - Attendees saved |
| `Multiple attendees can be stored for a single meeting` | **Scale** - Multi-attendee support |
| `Multiple meetings can be stored for an activity` | **Scale** - Multi-session support |
| `Engagement metrics are stored correctly` | **Data integrity** - All metrics saved |
| `Activity without meetings shows empty report` | **Edge case** - No data handled |

#### Running Behat Tests

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

#### Behat Step Definitions

The plugin includes custom step definitions in `tests/behat/behat_bbbext_bnx_datahub.php`:

| Step | Description |
|------|-------------|
| `the following meeting data exists for BigBlueButton activity "..."` | Creates meeting records for testing |
| `the following attendee data exists for meeting "..."` | Creates attendee records with engagement data |
| `I navigate to the attendance report for "..."` | Navigates to the attendance report page |
| `I navigate to the engagement report for "..."` | Navigates to the engagement report page |
| `I should see the attendance report` | Verifies attendance report page is displayed |
| `I should see the engagement report` | Verifies engagement report page is displayed |
| `I should see attendee "..." in the report` | Verifies attendee appears in report |
| `I should not see attendee "..." in the report` | Verifies attendee does not appear |
| `meeting data should exist for BigBlueButton activity "..."` | Verifies meeting data in database |
| `attendee data should exist for meeting "..."` | Verifies attendee data in database |
| `no meeting data should exist for BigBlueButton activity "..."` | Verifies no meeting data exists |
| `a BNX record exists for BigBlueButton activity "..."` | Creates prerequisite BNX record |

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
