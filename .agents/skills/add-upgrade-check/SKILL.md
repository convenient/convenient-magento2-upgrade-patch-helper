---
name: add-upgrade-check
description: "Add a new analysis check to this Magento upgrade patch helper, including its integration, focused unit coverage, documentation, and optional validation against an existing Docker fixture. Use when a request adds a new analyser/report check; do not use for an isolated fix to an existing check."
---

# Add Upgrade Check

Use this skill for a new analyser check in this repository. Keep the implementation driven by the requested detection rule; do not assume a particular Magento subsystem, file format, source of truth, or interpretation of a patch.

## Discover before editing

Read the repository `AGENTS.md`, then trace the closest existing check from its implementation through its unit test and fixture data. Also inspect:

- `src/Ampersand/PatchHelper/PatchOverrideValidator.php`, including construction, dispatch, and result buckets;
- the relevant report type/constants only when the check needs a distinct user-facing type;
- the existing `dev/phpunit` test and fixture layout;
- `docs/CHECKS_AVAILABLE.md`; and
- `dev/Docker/docker-compose.yml` plus the existing fixture setup when functional coverage is relevant.

Identify what evidence the check actually needs: the changed patch entry, upgraded `vendor/`, frozen `vendor_orig/`, Magento/project context, or a justified combination. Decide its warning, information, or ignored outcome from the task's reporting requirement rather than copying a neighbouring check's severity.

## Implement and document

Add only the stable integration required by the check: a type constant if it represents a distinct report type, validator construction/dispatch, and the appropriate result bucket. Use the existing path and Magento-context helpers rather than duplicating bootstrap or path-resolution logic.

Add a focused unit test with matching fixture data in the established `dev/phpunit` structure. Add a complete user-facing entry to `docs/CHECKS_AVAILABLE.md`, including what is detected, the evidence considered, limits, and the recommended review action. Update scoped functional tests or expected output only when the new behavior needs that coverage.

## Functional fixtures

Use a Docker fixture only when relevant and only if the matching `uphelper-<ENV>` container is already running. Do not start, stop, install, recreate, bootstrap, or rebuild Docker/Magento environments.

For an existing fixture file, use the supported fast iteration route from `dev/`:

```bash
make docker-sync-fixtures ENV=<ENV> SYNC_PATHS='dev/Test*/path/to/file'
make docker-test ENV=<ENV> PHPUNIT_ARGS='--filter <focused-test>'
```

The sync helper only updates explicit existing `dev/Test*/` source files in the upgraded fixture, never `vendor_orig/`, then clears filesystem-generated/cache directories and regenerates `vendor.patch`. Do not replace it with package symlinks or broad copies: that breaks the frozen before/after comparison and can discard intentional upgraded-side fixture mutations.

Before synchronising, check `dev/Docker/setup-magento-with-diff.sh` for a deliberate upgraded-side mutation of the same destination. A source sync replaces that mutation; preserve or change the setup behavior intentionally rather than silently erasing the fixture condition. Ask the user if this requires a rebuilt fixture.

Stop and ask the user if the required container is unavailable or stale, or if the change needs a new Composer package/module, Composer metadata, registration, module enablement, or a rebuilt fixture. Report any functional validation that could not run as unrun; do not claim it passed.
