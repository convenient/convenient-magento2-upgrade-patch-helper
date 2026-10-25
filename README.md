# Fork Notice

This is a fork of https://github.com/idhlagency/ampersand-magento2-upgrade-patch-helper with ongoing bugfixes, quality of life improvements, and feature development. When upstream pull requests start activating again the fixes here can be delivered.

All changes can be seen here https://github.com/convenient/convenient-magento2-upgrade-patch-helper/compare/19c4fbb...long-overdue-maintenance

## Fork Notice - Bugfixes

- Fixed Symfony Console autoloading conflicts to support a wider array of Magento versions.
- Improved Hyvä compatibility module resolution for template and JavaScript overrides.
- Improved Hyvä theme fallback detection when store configuration is unavailable.

## Fork Notice - New Checks

See [Checks available](docs/CHECKS_AVAILABLE.md) for detailed reporting behaviour, examples and review guidance.

| Level | Check name | Description |
| --- | --- | --- |
| INFO | System Configuration | Added, removed and changed fields. |
| INFO | Content Security Policy | Added, removed and changed whitelist entries. |
| INFO / WARN | REST WebAPI | Added, removed and changed routes, with warnings for overlapping customisations. |
| INFO / WARN | GraphQL | Added, removed and changed queries and mutations, with warnings for overlapping customisations. |
| WARN | Hyvä CMS Components | Added, removed and changed components with overlapping declarations. |
| WARN | Hyvä CMS JSON Includes | Added, removed and changed include fields that overlap with custom component definitions, plus references to removed fragments. |

## Fork Notice - Improvements

- Added agent guidance and skills for implementing and maintaining upgrade checks.
- Added scanning of custom Magento area codes for preferences and plugins.
- Refactored check-specific parsing into dedicated classes.
- Expanded unit and functional coverage, including installations without Hyvä, and removed the Magento 2.2 fixture.
- Updated the latest no-DB fixtures to Magento 2.4.10-alpha1 on PHP 8.5.
- Updated Docker PHP builds and disabled session upload progress by default.
- Documentation updates

# ampersand-magento2-upgrade-patch-helper 

For Magento, Adobe Commerce, and Hyvä upgrades, this tool cuts through the noise and highlights what matters.

It compares the old and new `vendor/` directories, then flags only the customisations you’ve overridden so you’re not stuck reviewing hundreds of files you didn’t touch.

## What It Detects

This tool covers all major Magento customisations and extension points
- Admin panel store configurations
- Content Security Policy declarations
- REST Web API and GraphQL schema changes
- PHP preferences and plugins
- Template, layout, and JS overrides
- Queue consumers
- Hyvä CMS components
- Database schema and data patches

For a detailed breakdown of each check, an example, and the recommended actions please see [docs/CHECKS_AVAILABLE.md](./docs/CHECKS_AVAILABLE.md)

## Analyse a patch or upgrade

Use this during any upgrade, patch, or module update to identify exactly what needs review.

**Run locally only**. See [docs/USAGE.md](./docs/USAGE.md) for a full walkthrough.

```bash
# Prepare your original vendor directory
cd /path/to/magento2/
composer install
mv vendor/ vendor_orig/

# perform your patch/update/upgrade
# you can update a module, apply an isolated .patch file, or perform a full magento upgrade

# generate the patch to analyse
diff -ur -N vendor_orig/ vendor/ > vendor.patch

# install this tool
cd -
git clone https://github.com/convenient/convenient-magento2-upgrade-patch-helper # todo use the fork url for now, revert to upstream url for merging
cd convenient-magento2-upgrade-patch-helper
composer install

# run this tool
php bin/patch-helper.php analyse /path/to/magento2/
```

This will output a grid of files that need to be reviewed and possibly updated to match the changes defined in the newly generated `vendor_files_to_check.patch`.

```text
+-------+--------------------------+----------------------------------------------------------------------------------------------+---------------------------------------------------------------------------------------------+
| Level | Type                     | File                                                                                         | To Check                                                                                    |
+-------+--------------------------+----------------------------------------------------------------------------------------------+---------------------------------------------------------------------------------------------+
| WARN  | DB schema added          | vendor/ampersand/upgrade-patch-helper-test-module/src/module/etc/db_schema.xml               | sales_order                                                                                 |
| WARN  | DB schema changed        | vendor/ampersand/upgrade-patch-helper-test-module/src/module/etc/db_schema.xml               | customer_entity                                                                             |
| WARN  | DB schema removed        | vendor/ampersand/upgrade-patch-helper-test-module-to-be-removed/src/module/etc/db_schema.xml | catalog_category_entity                                                                     |
| WARN  | DB schema removed        | vendor/ampersand/upgrade-patch-helper-test-module/src/module/etc/db_schema.xml               | wishlist                                                                                    |
| WARN  | DB schema target changed | vendor/magento/module-wishlist/etc/db_schema.xml                                             | app/code/Ampersand/Test/etc/db_schema.xml (wishlist_item)                                   |
| WARN  | GraphQL Mutation added   | vendor/acme/module-api/etc/schema.graphqls                                                   | app/code/Ampersand/Test/etc/schema.graphqls (Mutation addToWishlist)                        |
| WARN  | GraphQL Mutation changed | vendor/acme/module-api/etc/schema.graphqls                                                   | app/code/Ampersand/Test/etc/schema.graphqls (Mutation updateWishlistItem)                   |
| WARN  | GraphQL Mutation removed | vendor/acme/module-api/etc/schema.graphqls                                                   | app/code/Ampersand/Test/etc/schema.graphqls (Mutation removeFromWishlist)                   |
| WARN  | GraphQL Query added      | vendor/acme/module-api/etc/schema.graphqls                                                   | app/code/Ampersand/Test/etc/schema.graphqls (Query wishlistItems)                           |
| WARN  | GraphQL Query changed    | vendor/acme/module-api/etc/schema.graphqls                                                   | app/code/Ampersand/Test/etc/schema.graphqls (Query customerWishlist)                        |
| WARN  | GraphQL Query removed    | vendor/acme/module-api/etc/schema.graphqls                                                   | app/code/Ampersand/Test/etc/schema.graphqls (Query sharedWishlist)                          |
| WARN  | Hyva CMS Include added   | vendor/acme/module-cms/etc/hyva_cms/content-fields.json                                      | app/code/Ampersand/Test/etc/hyva_cms/components.json (category_intro)                       |
| WARN  | Hyva CMS Include changed | vendor/acme/module-cms/etc/hyva_cms/content-fields.json                                      | app/code/Ampersand/Test/etc/hyva_cms/components.json (article_content)                      |
| WARN  | Hyva CMS Include removed | vendor/acme/module-cms/etc/hyva_cms/content-fields.json                                      | app/code/Ampersand/Test/etc/hyva_cms/components.json (product_summary)                      |
| WARN  | Hyva CMS added           | vendor/acme/module-cms/etc/hyva_cms/components.json                                          | app/code/Ampersand/Test/etc/hyva_cms/components.json (category_intro)                       |
| WARN  | Hyva CMS changed         | vendor/acme/module-cms/etc/hyva_cms/components.json                                          | app/code/Ampersand/Test/etc/hyva_cms/components.json (product_summary)                      |
| WARN  | Hyva CMS removed         | vendor/acme/module-cms/etc/hyva_cms/components.json                                          | app/code/Ampersand/Test/etc/hyva_cms/components.json (article_teaser)                       |
| WARN  | Override (phtml/js/html) | vendor/magento/module-catalog/view/frontend/layout/catalog_category_view.xml                 | app/design/frontend/Ampersand/theme/Magento_Catalog/layout/catalog_category_view.xml        |
| WARN  | Override (phtml/js/html) | vendor/magento/module-checkout/view/frontend/templates/cart/form.phtml                       | app/design/frontend/Ampersand/theme/Magento_Checkout/templates/cart/form.phtml              |
| WARN  | Override (phtml/js/html) | vendor/magento/module-checkout/view/frontend/web/js/model/place-order.js                     | app/design/frontend/Ampersand/theme/Magento_Checkout/web/js/model/place-order.js            |
| WARN  | Override (phtml/js/html) | vendor/magento/module-customer/view/frontend/email/password_reset_confirmation.html          | app/design/frontend/Ampersand/theme/Magento_Customer/email/password_reset_confirmation.html |
| WARN  | Override (phtml/js/html) | vendor/magento/module-sales/view/frontend/layout/sales_order_print.xml                       | app/design/frontend/Ampersand/theme/Magento_Sales/layout/sales_order_print.xml              |
| WARN  | Override (phtml/js/html) | vendor/magento/module-ui/view/base/web/templates/grid/masonry.html                           | app/design/frontend/Ampersand/theme/Magento_Ui/web/templates/grid/masonry.html              |
| WARN  | Plugin                   | vendor/magento/framework/Stdlib/Cookie/PhpCookieManager.php                                  | Ampersand\Test\Plugin\PhpCookieManager::beforeSetPublicCookie                               |
| WARN  | Plugin                   | vendor/magento/module-adobe-ims/Model/UserProfile.php                                        | Ampersand\Test\Plugin\AdobeImsUserProfile::afterGetUpdatedAt                                |
| WARN  | Plugin                   | vendor/magento/module-adobe-ims/Model/UserProfile.php                                        | Ampersand\Test\Plugin\AdobeImsUserProfile::aroundGetUpdatedAt                               |
| WARN  | Preference               | vendor/magento/framework/Locale/Format.php                                                   | Ampersand\Test\Model\Locale\Format                                                          |
| WARN  | Preference               | vendor/magento/module-advanced-pricing-import-export/Model/Export/AdvancedPricing.php        | Ampersand\Test\Model\Admin\Export\AdvancedPricing                                           |
| WARN  | Preference               | vendor/magento/module-weee/Model/Total/Quote/Weee.php                                        | Ampersand\Test\Model\Frontend\Total\Quote\Weee                                              |
| WARN  | Preference               | vendor/magento/module-weee/Model/Total/Quote/Weee.php                                        | Ampersand\Test\Model\Total\Quote\Weee                                                       |
| WARN  | REST WebAPI added        | vendor/acme/module-api/etc/webapi.xml                                                        | vendor/acme/module-custom-api/etc/webapi.xml (POST /V1/upgrade-helper/added)                |
| WARN  | REST WebAPI changed      | vendor/acme/module-api/etc/webapi.xml                                                        | vendor/acme/module-custom-api/etc/webapi.xml (GET /V1/upgrade-helper/changed)               |
| WARN  | REST WebAPI removed      | vendor/acme/module-api/etc/webapi.xml                                                        | vendor/acme/module-custom-api/etc/webapi.xml (GET /V1/upgrade-helper/removed)               |
| WARN  | Redundant Override       | vendor/ampersand/some-nice-theme/Magento_Ui/web/templates/redundant.html                     | app/design/frontend/Ampersand/theme/Magento_Ui/web/templates/redundant.html                 |
+-------+--------------------------+----------------------------------------------------------------------------------------------+---------------------------------------------------------------------------------------------+
WARN count: 34
INFO count: 381 (to view re-run this tool with --show-info)
You should review the above 34 items alongside ./dev/instances/magentom24nodb/vendor_files_to_check.patch
```
