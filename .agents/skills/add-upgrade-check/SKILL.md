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

## Magento contract grounding

Before designing the check, inspect the authoritative Magento 2.4.8 source under `dev/vanilla/vendor/magento/` for the requested behaviour. Choose the relevant XSD plus reader/converter for XML, or the responsible core service/interface for PHP behaviour. Use that source to determine valid input, effective identity and merge rules, meaningful attributes/text, and Magento normalisation.

Keep the helper's own compact parsed arrays and lookups; do not copy Magento runtime classes or require Magento bootstrapping for unit tests. The core source establishes what the helper's array represents. Normal-behaviour fixtures should be valid for that contract; reserve malformed or unsupported inputs for explicitly named error-path tests.

If `dev/vanilla` is absent or incomplete, stop and ask the user to either run:

```bash
composer create-project --repository=https://mirror.mage-os.org/ magento/project-community-edition:2.4.8 ./dev/vanilla --no-interaction --no-plugins --ignore-platform-reqs
```

or explicitly authorise the agent to run that exact command. Do not infer Magento behaviour from memory or substitute a different reference version/source.

Use 2.4.8 as the default. Only inspect a matching already-running `uphelper-*` fixture's `vendor/magento/` source when a relevant supported-version fixture fails, differs, or raises a concrete compatibility concern. Do not start, rebuild, or install a fixture only to inspect source. Reconcile any observed difference across the affected supported versions, and record the local source paths plus any container/version evidence in the implementation handoff.

Identify what evidence the check actually needs: the changed patch entry, upgraded `vendor/`, frozen `vendor_orig/`, Magento/project context, or a justified combination. Decide its warning, information, or ignored outcome from the task's reporting requirement rather than copying a neighbouring check's severity.

## Implement and document

Add only the stable integration required by the check: a type constant if it represents a distinct report type, validator construction/dispatch, and the appropriate result bucket. Use the existing path and Magento-context helpers rather than duplicating bootstrap or path-resolution logic.

Add a focused unit test with matching fixture data in the established `dev/phpunit` structure. Add a concise user-facing entry to `docs/CHECKS_AVAILABLE.md`: lead with a single direct sentence stating what the changed file has added, removed, or changed, then include only the evidence or review action needed to use the result. Match the compact style of nearby check entries. Do not document internal comparison keys, merge or parser mechanics, test rationale, converter behavior, or implementation commentary unless users need it to interpret a report row correctly. Update scoped functional tests or expected output only when the new behavior needs that coverage.

## Functional fixtures

Use a Docker fixture only when relevant and only if the matching `uphelper-<ENV>` container is already running. Do not start, stop, install, recreate, bootstrap, or rebuild Docker/Magento environments.

Before running a full functional matrix, use `docker ps` to identify the available `uphelper-*` fixtures and ask the user for explicit approval to run them serially. Do not infer approval from authorisation to run a focused fixture. Run only the approved fixtures, one at a time.

When a report golden file differs, inspect the failure-only artifact saved under `dev/phpunit/functional/actual_output/` rather than copying PHPUnit's terminal diff, which may truncate large reports. A new INFO check can also change default-report golden files through their INFO summary count, even when `--show-info` is absent.

For an existing fixture file, use the supported fast iteration route from `dev/`:

```bash
make docker-sync-fixtures ENV=<ENV> SYNC_PATHS='dev/Test*/path/to/file'
make docker-test ENV=<ENV> PHPUNIT_ARGS='--filter <focused-test>'
```

The sync helper copies explicit existing or new `dev/Test*/` source files into the upgraded fixture only, never `vendor_orig/`, then clears filesystem-generated/cache directories and regenerates `vendor.patch`. A new synced file therefore appears as an addition. Do not replace it with package symlinks or broad copies: that breaks the frozen before/after comparison and can discard intentional upgraded-side fixture mutations.

Before synchronising, check `dev/Docker/setup-magento-with-diff.sh` for a deliberate upgraded-side mutation of the same destination. A source sync replaces that mutation; preserve or change the setup behavior intentionally rather than silently erasing the fixture condition. Ask the user if this requires a rebuilt fixture.

If a new file must be tested as a changed entry in an already-running fixture, explain that normal `SYNC_PATHS` creates an addition because it updates upgraded `vendor/` only. Prompt the user for explicit authority before seeding the baseline file into the matching `vendor_orig/` package path. Only after that authority is given, seed the exact file, sync it into upgraded `vendor/`, apply the planned upgraded-side mutation, and regenerate `vendor.patch`. This is a one-off fixture setup, not a normal sync capability: do not overwrite an existing original-side file or generalise it into the sync helper.

Stop and ask the user if the required container is unavailable or stale, or if the change needs a new Composer package/module, Composer metadata, registration, module enablement, or a rebuilt fixture. Report any functional validation that could not run as unrun; do not claim it passed.
