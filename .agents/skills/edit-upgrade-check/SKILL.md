---
name: edit-upgrade-check
description: "Fix or extend an existing Magento upgrade-helper analysis check, its parsing and regression coverage. Not for adding a new report check or implementing changes during a read-only review."
---

# Edit Upgrade Check

Read the repository [AGENTS.md](../../../AGENTS.md) first. It owns the shared architecture, testing, fixture and approval rules. A request to review or diagnose does not authorise implementation.

## Establish the regression

Trace the existing check, parser, validator dispatch, unit fixtures and report output. State the current behaviour and intended correction. Add a regression test that fails before the fix where practical; preserve unrelated work and avoid expanding the requested change.

## Ground in Magento source

Before semantic implementation, inspect the relevant code under `dev/vanilla/vendor/magento/`, the local Magento 2.4.8 reference. For XML, read the schema and reader/converter; otherwise inspect the responsible core class or service. Establish valid inputs, effective identity, meaningful values and normalisation, and compare those with the check. Its existing code and tests are not proof of Magento's behaviour.

Distinguish declaration identity, effective configuration and report labels according to the intended reporting contract. Follow [Magento reference source](../../../AGENTS.md#magento-reference-source) for missing-reference and supported-version handling. Purely mechanical refactors do not need fresh semantic investigation.

## Correct and cover

Preserve report categories, intentional ignored changes and useful parser diagnostics unless the requested correction requires changing them. Keep substantial check-specific parsing in named classes under `Patchfile/Entry/`, following `SystemConfigDefinitions.php`, with `Entry.php` delegating. Do not force inheritance or refactor unrelated checks.

Add focused, DAMP unit coverage for distinct regressions and safety branches. Add or update representative before/after test-module fixtures and their setup mutations so existing broad functional suites cover the integration. Do not create case-specific functional tests or separate goldens unless exercising a distinct workflow.

Follow [Local development and tests](../../../AGENTS.md#local-development-and-tests) for safe fixture sync and complete `.actual` output. Establish why changed rows and counts are legitimate before accepting golden updates. Update user documentation only when report interpretation changes, using the concise style in [Check implementation and documentation](../../../AGENTS.md#check-implementation-and-documentation).

## Validate and hand off

Run the focused test, full unit, static and PHPStan checks unless the user restricts validation; refresh Composer's classmap when adding classes. Run relevant existing functional suites only within the authorised scope, using [Task authorisation and execution permissions](../../../AGENTS.md#task-authorisation-and-execution-permissions). For Codex-specific saved approvals, use [dev/CODEX.md](../../../dev/CODEX.md).

Report the correction, Magento source evidence, legitimate report differences, passed checks and validation gaps. Do not weaken approval boundaries to finish validation.
