# BNX Data Hub Developer Guide

## Role in the BNX family

`bbbext_bnx_datahub` is a passive `bbbext` analytics sidecar. It depends on the
BNX base record for activity linkage and consumes BigBlueButton broker meeting
events supplied by the parent `mod_bigbluebuttonbn` module. It must never alter
parent settings, BNX settings, or meeting behavior.

The current DataHub release line targets the BNX 1.2/Moodle 5.1-5.2 contract.
Treat a Moodle 5.3/BNX 1.3 port as separate compatibility work, including a
review of each parent hook signature and Report Builder API.

## Extension hook and event flow

The implementation class
`bbbext_bnx_datahub\bigbluebuttonbn\broker_meeting_events_addons` receives the
parent broker's event payload. It returns early when BNX is unavailable, then
resolves the activity and delegates persistence to `logger`.

```text
BigBlueButton callback -> parent broker -> DataHub broker addon
  -> logger -> meeting and attendee tables -> Report Builder reports
```

Do not add a parallel HTTP endpoint for callback ingestion. The parent broker
is responsible for authenticating and dispatching BigBlueButton events.

## Persistence services

`logger` is the ingestion facade. Its services isolate table access:

| Service | Responsibility |
| --- | --- |
| `meeting_service` | Upserts a meeting session tied to a BNX activity. |
| `attendance_service` | Upserts attendees and engagement counters for a session. |

Meeting rows live in `bbbext_bnx_datahub_meeting`; attendee rows live in
`bbbext_bnx_datahub_meeting_user`. Preserve the activity-to-BNX-to-meeting
foreign-key chain. Event payloads may be incomplete or repeated, so logger
code must be defensive and idempotent.

## Report Builder integration

DataHub's entities define reusable columns and filters. Datasources combine
those entities for custom reports; the attendance and engagement endpoints use
the system report factory for fixed activity-facing reports. Add a new metric by
updating the schema/upgrade path, ingestion mapping, entity column, datasource,
privacy export, and appropriate tests as one feature.

`navigation_append_addon` adds report links only when prerequisites are met and
the viewer has `bbbext/bnx_datahub:viewreports`. Navigation is a convenience,
not an authorization boundary; endpoints must enforce access themselves.

## Sidecar contract and status checks

DataHub observes `\bbbext_bnx\event\state_changed` and writes only its own
disabled configuration. `lib.php` registers status checks for BNX availability,
the parent meeting-events setting, and legacy BN Reports conflicts. Add a check
when DataHub has a new external prerequisite; do not silently repair it.

## Testing

Run focused Moodle PHPUnit tests with Moodle bootstrap:

```bash
php vendor/bin/phpunit -c phpunit.xml \
  public/mod/bigbluebuttonbn/extension/bnx_datahub/tests/logger_test.php
```

The key suites cover broker ingestion, services, Report Builder entities and
reports, navigation, migrations, prerequisite checks, and the Privacy API.
Behat scenarios in `tests/behat/` cover report visibility, permissions,
navigation, and stored data behavior.

When changing event parsing, add duplicate/out-of-order/missing-field coverage.
When changing reports, test capability denial as well as expected columns and
filters.
