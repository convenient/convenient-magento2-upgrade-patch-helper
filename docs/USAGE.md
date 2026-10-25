# Usage

All the below should be used on a local setup, never do any of this anywhere near a production website.

It is most reliable if you use this when the instance has been built and is plugged into a database just in case you have theme configuration stored there. Running this without it being plugged into a database could cause some of the theme analysis to be missed.

The report uses three levels:

- **WARN** — Affected customisations that need review and may require code changes.
- **INFO** — Relevant vendor changes for awareness; hidden unless `--show-info` is used.
- **IGNR** — Changes classified as comments, whitespace or otherwise non-functional; hidden unless `--show-ignore` is used.

### Step 1 - Composer update the dependencies then generate a patch

In your project `composer install` and move the original vendor directory to a backup location

```
cd /path/to/magento2/
composer install
mv vendor/ vendor_orig/
```

Update your magento version, third party module, or apply isolated patches. 

```bash
composer install
composer require magento/product-enterprise-edition 2.2.6 --no-update
composer require magento/extension-b2b 1.0.6 --no-update
composer require thirdparty/some-module "^2.0" 
composer update magento/extension-b2b magento/product-enterprise-edition thirdparty/some-module --with-dependencies
```

At this point you may receive errors of incompatible modules, often they are tied to a specific version of magento. Correct the module dependencies and composer require the updated version until you can run `composer install` successfully.

Once you have a completed the composer steps you can create a diff which can be analysed.

```bash
diff -ur -N vendor_orig/ vendor/ > vendor.patch
```

By generating the diff in this manner (as opposed to using `wget https://github.com/magento/magento2/compare/2.1.15...2.1.16.diff`) we can guarantee that all enterprise and magento extensions are also covered in one patch file.

### Step 2 - Parse the patch file

In a clone of this repository you can analyse the project and patch file.

```php
git clone https://github.com/AmpersandHQ/ampersand-magento2-upgrade-patch-helper # todo use the fork url for now
cd ampersand-magento2-upgrade-patch-helper
composer install
php bin/patch-helper.php analyse /path/to/magento2/
```

This will output a grid of files that need to be reviewed and possibly updated to match the changes defined in the newly generated `vendor_files_to_check.patch`.

For those of you who would prefer to work over these results in a GUI rather than a CLI you may want to check out [elgentos/magento2-upgrade-gui](https://github.com/elgentos/magento2-upgrade-gui)

## Additional options

### --show-info

```
php bin/patch-helper.php analyse /path/to/magento2/ --show-info
```

Show all `INFO` level items, this can be a lot more output and give you a broader view of the system changes.

### --show-ignore

```
php bin/patch-helper.php analyse /path/to/magento2/ --show-ignore
```

Show all `IGNR` level items, see https://github.com/AmpersandHQ/ampersand-magento2-upgrade-patch-helper/blob/master/docs/CHECKS_AVAILABLE.md#ignr---ignored-warnings

### --auto-theme-update

```
php bin/patch-helper.php analyse /path/to/magento2/ --auto-theme-update 5
```

For template files the optional argument will automatically apply the changes to the local theme files.

The fuzz factor defines the level of strict comparing. With a fuzz factor of 0 only changes, where all lines of the context match, are applied.
With a factor of 1 the first and the last line of the context is ignored. With a factor of n accordingly the first n and the last n lines.
If a change could not be applied, a .rej file with the remaining changes is automatically created in the folder of the template file.

As it is recommended to check all changes afterwards anyway, a big fuzz factor can be chosen.


### --vendor-namespaces

```
php bin/patch-helper.php analyse /path/to/magento2/ --vendor-namespaces Ampersand,Amazon
```

This option allows you to filter the results to only the defined list of namespaces. Useful when you only care about overrides in your project namespace.


### --sort-by-type

```
php bin/patch-helper.php analyse /path/to/magento2/ --sort-by-type
```

Sorts the output table by the type of override

### --phpstorm-threeway-diff-commands


```
php bin/patch-helper.php analyse /path/to/magento2/ --phpstorm-threeway-diff-commands
```

Also print out a series of threeway diff commands for use in phpstorm
https://www.jetbrains.com/help/phpstorm/command-line-differences-viewer.html

For example
```
Outputting diff commands below
phpstorm diff vendor/ampersand/upgrade-patch-helper-test-module/src/module/Api/ExampleInterface.php app/code/Ampersand/Test/Model/Example.php vendor_orig/ampersand/upgrade-patch-helper-test-module/src/module/Api/ExampleInterface.php
```
