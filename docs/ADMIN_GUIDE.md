# BNX Data Hub Administrator Guide

## Supported release line

DataHub `1.1.1` supports Moodle 5.1, 5.2, and 5.3 and requires the BNX 1.2
contract. BNX 1.3 is Moodle 5.3-only; validate DataHub before deploying it
with that BNX release line.

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
attendance and engagement report links. Report access requires
`bbbext/bnx_datahub:viewreports`, which is granted by default to managers,
editing teachers, and administrators at course context.

The reports use Moodle Report Builder. They display session attendance and
engagement collected from BigBlueButton meeting-event callbacks. Use Moodle's
normal report filters and downloads; DataHub does not expose a separate
external API for reports.

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

## Troubleshooting

| Symptom | Check |
| --- | --- |
| No report links | Confirm DataHub/BNX are enabled, meeting events are enabled, and the viewer has `bbbext/bnx_datahub:viewreports`. |
| Empty reports | Confirm event delivery from BigBlueButton and that the meeting generated broker events. |
| Status check fails | Resolve the named prerequisite in the owning component; DataHub intentionally will not change it for you. |
| DataHub disabled itself | Restore BNX, then re-enable DataHub in Plugins overview. |
| BN Reports conflict | Complete the migration and leave the legacy reporting plugin disabled. |
