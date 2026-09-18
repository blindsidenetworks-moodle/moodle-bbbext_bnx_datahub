# BNX Data Hub Data, Privacy, and Security

## Stored analytics data

DataHub records BigBlueButton meeting callbacks in two tables:

| Table | Data |
| --- | --- |
| `bbbext_bnx_datahub_meeting` | Activity linkage, BigBlueButton meeting IDs, name, start/end time, duration, metadata, and raw event payload. |
| `bbbext_bnx_datahub_meeting_user` | User linkage where known, display name, role, join/leave time, duration, engagement counters, score, and raw event payload. |

The attendee table may represent a guest without a Moodle user ID. It is still
activity-context analytics data and must be treated as potentially personal
information in operational exports and database access.

## Access control

Report access requires `bbbext/bnx_datahub:viewreports` in course context.
The navigation addon hides links from unauthorized users, but report endpoints
must remain capability-protected. Do not expose meeting or attendee rows through
an unauthenticated endpoint or grant this capability to learner roles by
default.

## Moodle Privacy API

The privacy provider declares its tables and implements context discovery,
user-list discovery, export, and deletion for user-associated attendance and
engagement records. Any schema change that adds user-linked data must update:

1. `classes/privacy/provider.php` metadata and export/delete behavior;
2. language strings describing the new data; and
3. `tests/privacy_provider_test.php`.

## Retention and deletion

DataHub does not impose a separate retention scheduler. Site administrators
should apply their Moodle retention, backup, and privacy-erasure policies to
the courses containing BigBlueButton activities. Verify the effect of course
and activity deletion in the deployed Moodle version as part of operational
acceptance testing.

Raw callback data can contain more information than a rendered report. Limit
direct database access, protect backups, and avoid copying raw payloads into
logs or support requests unless they have been reviewed and sanitized.

## Migration safety

The optional BN Reports migration is intended for one-time legacy data transfer.
Run it in a maintenance window, back up the site first, validate record counts,
and leave the old reporting sidecar disabled after success. The DataHub sidecar
does not change parent or BNX configuration as part of this process.
