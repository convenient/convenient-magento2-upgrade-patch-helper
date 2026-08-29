# Agent guidance

## Purpose

This is a Magento 2 upgrade review helper. It analyses a `vendor.patch` produced from an upgraded Magento project and reports local customisations that may be affected by the upgrade.

Read [README.md](README.md) for the end-user workflow, [docs/CHECKS_AVAILABLE.md](docs/CHECKS_AVAILABLE.md) for the full check reference, and [docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md) for diagnostics.

## Analysis model

Run the helper against a local Magento project after the upgrade has completed. Keep all of the following available in that project:

- The upgraded `vendor/` directory.
- The pre-upgrade `vendor_orig/` directory.
- A unified patch generated with `diff -ur -N vendor_orig/ vendor/ > vendor.patch`.
- A Magento installation that can be bootstrapped; database-backed theme configuration improves theme analysis, although no-database cases are supported.

The current `analyse` command expects that unified patch as `vendor.patch` at the Magento project root. The helper boots the target Magento installation to understand its modules, DI configuration, themes and fallback resolution, and schema relationships. It combines that live configuration with the patch and vendor trees; it is not a patch-only path matcher.

The report identifies upgrade impacts that may need review. Refer to [docs/CHECKS_AVAILABLE.md](docs/CHECKS_AVAILABLE.md) for the complete, current list of checks and their recommended actions. Treat `WARN` items as targeted review work; use `--show-info` and `--show-ignore` for the broader report.

## Local development and tests

Run root checks with:

```bash
composer test:static
composer test:phpstan
composer test:unit
```

Functional tests use Docker fixtures that construct a Magento version-pair upgrade, generate the patch, and run the matching PHPUnit group. From `dev/`:

```bash
make docker-up docker-install ENV=m24nodb
make docker-test ENV=m24nodb
make docker-test ENV=m24nodb PHPUNIT_ARGS='--filter testNameGoesHere'
```

The available environments and their Magento/PHP combinations are defined in `dev/Docker/docker-compose.yml`. CI runs static analysis and unit tests first, then runs the Docker functional matrix. Do not assume an individual functional environment represents every supported version pair.

When running a command directly inside an already-running `uphelper-*` fixture, use Bash rather than `sh` and source `/root/.bashrc` first, as `dev/Docker/run-phpunit-functional-tests.sh` does. This initializes `phpenv` and makes the selected `php` executable available.

When a functional report golden-file assertion fails, inspect the failure-only artifact at `dev/phpunit/functional/actual_output/<expected-filename>.actual`. This directory is ignored by Git; use its complete report when updating golden files rather than PHPUnit's terminal diff.

For an existing, already-running fixture, make a targeted source-file change visible in its upgraded tree with:

```bash
make -C dev docker-sync-fixtures ENV=m24 SYNC_PATHS='dev/TestVendorModule/src/module/Model/Example.php'
```

The helper maps only explicitly named paths under direct `dev/Test*/` fixtures, preserves the immutable `vendor_orig/` snapshot, clears filesystem-generated/cache directories, and regenerates `vendor.patch`. Do not symlink test package sources into both vendor trees: shared files would invalidate the frozen before/after comparison and can overwrite deliberate upgraded-side fixture mutations. The sync route is for existing files only; new packages/modules, Composer or registration changes, module enablement, and rebuilds require explicit human authorisation. Never start, stop, install, or rebuild Docker/Magento fixtures unless the user asks.

## Guardrails and debugging

- Work only on local development copies; do not run this workflow against production or production data.
- Do not delete `vendor_orig/` or manually regenerate `vendor.patch` while investigating analysis results. `docker-sync-fixtures` is the deliberate development-fixture exception: it regenerates the patch only after an explicitly requested source-file sync.
- Use `--filter "filename" -vvv` to trace one changed file.
- Use `--php-strict-errors -vvv` to turn PHP warnings/notices into actionable exception traces.
- `--auto-theme-update` modifies local theme files; review the resulting changes and any `.rej` files before keeping them.

## Compatibility

The helper must support the PHP and Magento versions declared by the project. Because it boots the target Magento application, changes must remain compatible with the target's dependency environment. In particular, Symfony Console classes used by the helper are deliberately loaded before Magento bootstrapping to avoid mixing incompatible Console versions.
