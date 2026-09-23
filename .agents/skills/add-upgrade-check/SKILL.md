---
name: add-upgrade-check
description: "Add a new analyser/report check to this Magento upgrade patch helper, including integration, unit coverage, functional fixture data and concise documentation. Not for fixes to an existing check or read-only reviews."
---

# Add Upgrade Check

Read the repository [AGENTS.md](../../../AGENTS.md) first. It owns the shared Magento reference, architecture, testing and Docker approval rules; this skill does not grant additional permissions.

## Discover the integration

Trace the closest existing check through its implementation, unit tests and fixture data. Inspect `src/Ampersand/PatchHelper/Helper/PatchOverrideValidator.php` for construction, dispatch and result buckets, plus the relevant report constants and the matching section of `docs/CHECKS_AVAILABLE.md`.

Establish the requested detection rule, severity and evidence needed: patch entry, upgraded `vendor/`, frozen `vendor_orig/`, Magento context or a justified combination. Do not copy a neighbouring check's reporting contract by assumption.

## Ground in Magento source

Before implementing Magento-semantic behaviour, read [dev/MAGENTO_REFERENCE.md](../../../dev/MAGENTO_REFERENCE.md), then inspect `dev/vanilla/vendor/magento/`, the local Magento 2.4.8 reference. For XML, read the relevant schema and reader/converter; otherwise inspect the responsible core implementation. Establish valid inputs, identity, meaningful values and normalisation. Distinguish source declaration identity, effective configuration and report labels.

Follow [Magento source investigation](../../../dev/MAGENTO_REFERENCE.md#source-investigation) for missing-reference and cross-version handling. Existing helper tests are not proof of Magento behaviour.

## Implement and cover

Add only the required integration: a distinct report constant when needed, validator dispatch and the appropriate result bucket. Reuse existing context/path helpers and compact parsed arrays. Keep substantial check-specific parsing in classes under `Patchfile/Entry/`, with generic `Entry.php` delegating, as described in [Check implementation and documentation](../../../AGENTS.md#check-implementation-and-documentation).

Add focused, DAMP unit tests and representative before/after changes to the existing test-module fixtures. Update fixture setup to reproduce the upgraded-side mutations and exercise them through the existing broad functional suites and goldens, not new per-case functional tests. Before changing or operating functional fixtures, read [Development workflow](../../../dev/README.md) for fixture sync, exact baseline-seeding authority and complete `.actual` artifacts.

Add a concise user-facing check entry: what it reports and what to review, without internal implementation commentary.

## Validate and hand off

Run the focused unit test, then the full unit, static and PHPStan checks unless the user restricts validation. Run relevant existing functional suites within the authorised scope and saved command approvals. Refresh Composer's classmap after adding classes.

Explain legitimate report/golden differences, name the Magento source inspected and distinguish passed checks from unrun validation. Keep read-only requests read-only and follow [Task authorisation and execution permissions](../../../AGENTS.md#task-authorisation-and-execution-permissions). Before relying on or managing Codex-specific saved approvals, read [dev/CODEX.md](../../../dev/CODEX.md); do not recreate those procedures here.
