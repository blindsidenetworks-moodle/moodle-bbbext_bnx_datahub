# BNX Data Hub Administrator Guide

## Supported release line

DataHub `1.2-beta.1` supports Moodle 5.3 and requires BNX 1.3 or later
(minimum version code `2026110500`). Both plugins are beta releases; use
matching Moodle 5.3-compatible builds.

## Install and prerequisites

1. Install and configure `mod_bigbluebuttonbn`.
2. Install and enable the compatible BNX release.
3. Deploy DataHub to `mod/bigbluebuttonbn/extension/bnx_datahub/` and complete
   Moodle's upgrade.
4. Enable `meetingevents_enabled` in the parent BigBlueButton settings.
5. Check Site administration > Server > Status for the DataHub prerequisite
   checks: BNX enabled, meeting events enabled, and no BN Reports conflict.

DataHub never enables the parent meeting-events setting. It reports missing
prerequisites so an administrator can make the change deliberately.

## Reports and permissions

When prerequisites are satisfied, BigBlueButton activity navigation provides
the **Dashboard +** link. The dashboard summarizes activity attendance and
engagement across sessions, and provides a selectable session overview with
learner participation details. Attendance and engagement Report Builder pages
remain available as detailed reports from the dashboard. Report access requires
`bbbext/bnx_datahub:viewreports`. When Moodle first registers this capability,
it clones existing role permissions for `moodle/course:manageactivities`,
including custom role assignments and context overrides. Review these grants
for roles that should not see attendance and engagement data. Subsequent changes
to the source capability do not automatically update existing report grants.
The capability is marked as a personal-data risk.

Moodle refreshes capability metadata during a plugin upgrade. Deployments that
replace files without a plugin version change do not automatically receive the
updated risk flag; a cache purge alone does not update the stored metadata.

The dashboard and built-in activity reports show all attendees for the activity,
regardless of group mode or the viewer's group membership. In separate-groups
courses, a viewer with report access can therefore see learners in other groups
even without `moodle/site:accessallgroups`. Limit report access to trusted roles.
The built-in attendance and engagement pages accessed without an activity ID
show site-wide data and require the report capability at system context.

Custom Report Builder reports use separate Moodle permissions and audiences.
DataHub datasources can include site-wide data, including excluded sessions,
without the DataHub report capability. Restrict report editing and review
conditions and audiences; see [Data, Privacy, and Security](DATA_AND_PRIVACY.md).

The reports use Moodle Report Builder. They display session attendance and
engagement collected from BigBlueButton meeting-event callbacks. Use Moodle's
normal report filters and downloads; DataHub does not expose a separate
external API for reports.

Users with `bbbext/bnx_datahub:manageexclusions` can exclude a selected session
from the dashboard after confirmation, and restore it from **Excluded sessions**.
The capability defaults to roles that can manage activities. Excluded sessions
are omitted from dashboard summaries and built-in attendance and engagement
reports, including downloads. Custom Report Builder reports can use the meeting
entity's **Excluded from default reports** column and filter to choose which
sessions to include. Exclusion is manual and does not delete data.

## Operations

DataHub is passive. It records parent broker callbacks only; it does not alter
meeting join, presentation, guest, or server behavior. If records are absent,
verify that the BigBlueButton server is sending events, the parent module is
configured to receive them, BNX is enabled, and the activity actually has a
BNX record.

DataHub reacts to BNX state changes by adjusting only its own enabled state.
Re-enable DataHub after resolving a BNX prerequisite through Plugins overview.

## Legacy BN Reports migration

Use the migration command only when migrating an existing BN Reports install:

```bash
php public/mod/bigbluebuttonbn/extension/bnx_datahub/cli/migrate_bnreports.php
```

Review the result and leave the legacy reporting sidecar disabled after a
successful migration. Running both reporting engines can produce confusing or
duplicate operational data.

## Orphan attendee cleanup

Older DataHub versions may have stored attendee records without a meeting.
Back up the site before cleanup. Preview the number of records without changing
data:

```bash
php public/mod/bigbluebuttonbn/extension/bnx_datahub/cli/cleanup_orphan_attendees.php
```

To delete those records, run the same command with `--apply`. Deletion is
irreversible. The command removes only attendees whose meeting does not exist;
it does not remove meetings or attendees linked to an existing meeting.

## Orphan meeting cleanup

Meetings whose BNX record no longer exists, and their attendees, require a
separate explicit operation. Back up the site and stop concurrent activity or
course deletions before reviewing the preview:

```bash
php public/mod/bigbluebuttonbn/extension/bnx_datahub/cli/cleanup_orphan_attendees.php --orphan-meetings
```

After reviewing both counts, add `--apply` to delete those meetings and their
attendees. This irreversible operation does not delete meetings linked to an
existing BNX record, or attendees linked to those meetings. The default command
without `--orphan-meetings` continues to select only orphan attendees.

## Troubleshooting

| Symptom | Check |
| --- | --- |
| No report links | Confirm DataHub/BNX are enabled, meeting events are enabled, and the viewer has `bbbext/bnx_datahub:viewreports`. |
| Empty reports | Confirm event delivery from BigBlueButton and that the meeting generated broker events. |
| Status check fails | Resolve the named prerequisite in the owning component; DataHub intentionally will not change it for you. |
| DataHub disabled itself | Restore BNX, then re-enable DataHub in Plugins overview. |
| BN Reports conflict | Complete the migration and leave the legacy reporting plugin disabled. |
