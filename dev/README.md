# Running the tests

```
git clone https://github.com/AmpersandHQ/ampersand-magento2-upgrade-patch-helper
cd ampersand-magento2-upgrade-patch-helper
composer install
cd dev
```

Set up your magento versions as per the tests, check `.travis.yml` for the `ENV` groups we use
```
make docker-up docker-install ENV=m24nodb
```

Then run the tests
```
make docker-test ENV=m24nodb
```

To run a specific test
```
make docker-test ENV=m24nodb PHPUNIT_ARGS='--filter testNameGoesHere'
```

If you want to hop into the docker container to view the actual patch files being generated etc you can do so by 

```
docker exec -it uphelper-m24nodb bash
```

You can then run the tool as it is in the tests to debug / play around

```
php /src/bin/patch-helper.php analyse --php-strict-errors --filter "example.xml" /src/dev/instances/magentom24nodb -vvv
```

We have a docker volume which prevents `./dev/instances/magentom24nodb` etc from being synced back to the local host to ensure performance on OSX is acceptable.

## Notes

We use a custom docker container with the necessary php versions installed on it, as we need to composer install old and new magento versions to have these suites running for the diff to be generated properly.

This allows us to run the tests locally and in travis without so much fiddling about.

## Iterating on an existing fixture

`docker-install` deliberately makes a frozen `vendor_orig/` copy before it builds the upgraded `vendor/` tree and applies its fixture-specific mutations. The fixture package directories must therefore not be symlinked into both trees: editing a shared source would erase the before/after comparison, and synchronising a whole package could overwrite those intended upgraded-side mutations.

For a source-file change in an already provisioned fixture, use the targeted sync command from `dev/`. The matching `uphelper-<ENV>` container must already be running; this command never starts, stops, installs, or rebuilds one.

```
make docker-sync-fixtures ENV=m24 SYNC_PATHS='dev/TestVendorModule/src/module/Model/Example.php'
make docker-test ENV=m24 PHPUNIT_ARGS='--filter testMagentoTwoFour'
```

`SYNC_PATHS` and `DELETE_PATHS` accept whitespace-separated, explicitly named files below a direct `dev/Test*/` fixture directory. Package fixtures are mapped using their `composer.json` package name; `dev/TestModule/app/...` is mapped to the instance `app/...` directory. The command copies or removes only the named upgraded-side files, clears that instance's generated code and filesystem cache directories, and regenerates `vendor.patch`. It never writes to `vendor_orig/`, runs Composer, enables modules, installs Magento, or compiles DI.

This supports existing files and new files within an existing fixture package: a new file is copied to the upgraded `vendor/` tree only, so it appears as an addition unless the fixture's original side already contains it. Adding a Composer package or module, changing Composer metadata, registration, or module enablement, or needing a rebuilt fixture remains a human-authorised `docker-install` workflow. Do not sync a path that `setup-magento-with-diff.sh` intentionally mutates unless changing that fixture setup is part of the work.
