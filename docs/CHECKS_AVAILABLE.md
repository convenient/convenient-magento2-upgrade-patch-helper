# Checks Available

- [IGNR - Ignored Warnings](#ignr---ignored-warnings)
- [WARN - Preference](#warn---preference)
- [WARN - Preference Removed](#warn---preference-removed)
- [WARN - Plugin](#warn---plugin)
- [WARN - Plugin Enabled](#warn---plugin-enabled)
- [WARN - Plugin Disabled](#warn---plugin-disabled)
- [WARN - Override (phtml/js/html)](#warn---override-phtmljshtml)
- [WARN - Redundant Override](#warn---redundant-override)
- [WARN - DB schema added](#warn---db-schema-added)
- [WARN - DB schema removed](#warn---db-schema-removed)
- [WARN - DB schema changed](#warn---db-schema-changed)
- [WARN - DB schema target changed](#warn---db-schema-target-changed)
- [WARN - REST WebAPI added](#warn---rest-webapi-added)
- [WARN - REST WebAPI removed](#warn---rest-webapi-removed)
- [WARN - REST WebAPI changed](#warn---rest-webapi-changed)
- [WARN - REST WebAPI target changed](#warn---rest-webapi-target-changed)
- [WARN - GraphQL Query added](#warn---graphql-query-added)
- [WARN - GraphQL Query removed](#warn---graphql-query-removed)
- [WARN - GraphQL Query changed](#warn---graphql-query-changed)
- [WARN - GraphQL Query target changed](#warn---graphql-query-target-changed)
- [WARN - GraphQL Mutation added](#warn---graphql-mutation-added)
- [WARN - GraphQL Mutation removed](#warn---graphql-mutation-removed)
- [WARN - GraphQL Mutation changed](#warn---graphql-mutation-changed)
- [WARN - GraphQL Mutation target changed](#warn---graphql-mutation-target-changed)
- [INFO - Queue consumer added](#info---queue-consumer-added)
- [INFO - Queue consumer removed](#info---queue-consumer-removed)
- [INFO - Queue consumer changed](#info---queue-consumer-changed)
- [INFO - System config added](#info---system-config-added)
- [INFO - System config removed](#info---system-config-removed)
- [INFO - System config changed](#info---system-config-changed)
- [INFO - CSP added](#info---csp-added)
- [INFO - CSP removed](#info---csp-removed)
- [INFO - CSP changed](#info---csp-changed)
- [INFO - REST WebAPI added](#info---rest-webapi-added)
- [INFO - REST WebAPI removed](#info---rest-webapi-removed)
- [INFO - REST WebAPI changed](#info---rest-webapi-changed)
- [INFO - GraphQL Query added](#info---graphql-query-added)
- [INFO - GraphQL Query removed](#info---graphql-query-removed)
- [INFO - GraphQL Query changed](#info---graphql-query-changed)
- [INFO - GraphQL Mutation added](#info---graphql-mutation-added)
- [INFO - GraphQL Mutation removed](#info---graphql-mutation-removed)
- [INFO - GraphQL Mutation changed](#info---graphql-mutation-changed)
- [INFO - DB schema added](#info---db-schema-added)
- [INFO - DB schema removed](#info---db-schema-removed)
- [INFO - DB schema changed](#info---db-schema-changed)
- [INFO - Setup Patch Data](#info---setup-patch-data)
- [INFO - Setup Patch Schema](#info---setup-patch-schema)
- [INFO - Setup Script](#info---setup-script)

## IGNR - Ignored Warnings

These can be seen by running the tool with `--show-ignore`

Any `WARN` level item can be flagged as ignored (`IGNR`) if the file that changed, that initiated the check, was deemed to have not actually changed in any meaningful way. 

For example
- Changes to comments
- Changes to php doc block (copyright headers etc)
- Change to whitespace

These kinds of changes do not really require that we check for overrides or proceed to go through an actionable list of items to review. 

Therefore, every `WARN` item can also be an `IGNR` item provided the file that triggered the check was not really changed.

## WARN - Preference
A preference exists for a class which was modified as part of this upgrade 

Example: 

```
+-------+------------+---------------------------------------------------------------------------------------+---------------------------------------------------+
| Level | Type       | File                                                                                  | To Check                                          |
+-------+------------+---------------------------------------------------------------------------------------+---------------------------------------------------+
| WARN  | Preference | vendor/magento/module-advanced-pricing-import-export/Model/Export/AdvancedPricing.php | Ampersand\Test\Model\Admin\Export\AdvancedPricing |
+-------+------------+---------------------------------------------------------------------------------------+---------------------------------------------------+
```

You have a preference `Ampersand\Test\Model\Admin\Export\AdvancedPricing` on `Magento\AdvancedPricingImportExport\Model\Export\AdvancedPricing`. 

The upgrade has changed `Magento\AdvancedPricingImportExport\Model\Export\AdvancedPricing` so you need to check `Ampersand\Test\Model\Admin\Export\AdvancedPricing` to see if it needs amended to be compatible.

## WARN - Preference Removed

A preference exists for a class which no longer exists after this upgrade

Example:

```
+-------+--------------------+--------------------------------------------------------------------------------------------------------+-----------------------------------------------------+
| Level | Type               | File                                                                                                   | To Check                                            |
+-------+--------------------+--------------------------------------------------------------------------------------------------------+-----------------------------------------------------+
| WARN  | Preference Removed | vendor/ampersand/upgrade-patch-helper-test-module/src/module/Model/ToPreferenceAndExtendAndDelete.php  | Ampersand\Test\Model\ToPreferenceAndExtendAndDelete |
+-------+--------------------+--------------------------------------------------------------------------------------------------------+-----------------------------------------------------+
```

You have a preference `Ampersand\Test\Model\ToPreferenceAndExtendAndDelete` on `Ampersand\TestVendor\Model\ToPreferenceAndExtendAndDelete`.

The upgrade has removed `Ampersand\TestVendor\Model\ToPreferenceAndExtendAndDelete` so you need to rework or remove `Ampersand\Test\Model\ToPreferenceAndExtendAndDelete`.

## WARN - Plugin
A plugin exists on function which was modified as part of this upgrade. 

Example:

```
+-------+--------+-------------------------------------------------------+--------------------------------------------------------------+
| Level | Type   | File                                                  | To Check                                                     |
+-------+--------+-------------------------------------------------------+--------------------------------------------------------------+
| WARN  | Plugin | vendor/magento/module-adobe-ims/Model/UserProfile.php | Ampersand\Test\Plugin\AdobeImsUserProfile::afterGetUpdatedAt |
+-------+--------+-------------------------------------------------------+--------------------------------------------------------------+
```

You have a plugin `Ampersand\Test\Plugin\AdobeImsUserProfile::afterGetUpdatedAt` and the core `Magento\AdobeIms\Model\UserProfile::getUpdatedAt` function has changed. 

Check the changes to the core function to see if your plugin is still compatible. Sometimes plugins are used by developers to fix core behaviour, and it may no longer be necessary.

## WARN - Plugin Enabled
A plugin exists on function which was added to the codebase as part of this upgrade. You have new plugin logic firing where previously you did not.

Example:

```
+-------+------------------+-------------------------------------------------------+--------------------------------------------------------------+
| Level | Type             | File                                                  | To Check                                                     |
+-------+------------------+-------------------------------------------------------+--------------------------------------------------------------+
| WARN  | Plugin Enabled   | vendor/magento/module-adobe-ims/Model/UserProfile.php | Ampersand\Test\Plugin\AdobeImsUserProfile::afterGetUpdatedAt |
+-------+------------------+-------------------------------------------------------+--------------------------------------------------------------+
```

You have a plugin `Ampersand\Test\Plugin\AdobeImsUserProfile::afterGetUpdatedAt` and the core `Magento\AdobeIms\Model\UserProfile::getUpdatedAt` function has been added, so the plugin becomes active.

## WARN - Plugin Disabled
A plugin exists on function which was added to the codebase as part of this upgrade. You have new plugin logic firing where previously you did not.

Example:

```
+-------+------------------+-------------------------------------------------------+--------------------------------------------------------------+
| Level | Type             | File                                                  | To Check                                                     |
+-------+------------------+-------------------------------------------------------+--------------------------------------------------------------+
| WARN  | Plugin Disabled  | vendor/magento/module-adobe-ims/Model/UserProfile.php | Ampersand\Test\Plugin\AdobeImsUserProfile::afterGetUpdatedAt |
+-------+------------------+-------------------------------------------------------+--------------------------------------------------------------+
```

You have a plugin `Ampersand\Test\Plugin\AdobeImsUserProfile::afterGetUpdatedAt` and the core `Magento\AdobeIms\Model\UserProfile::getUpdatedAt` function has been deleted, so the plugin becomes deactivated. You have customisations which are no longer firing.

## WARN - Override (phtml/js/html)	    
There is a `phtml`/`html`/`xml`/`js` extension or override in place for a file which was modified as part of this upgrade. 

Example:

```
+-------+--------------------------+------------------------------------------------------------------------+--------------------------------------------------------------------------------+
| Level | Type                     | File                                                                   | To Check                                                                       |
+-------+--------------------------+------------------------------------------------------------------------+--------------------------------------------------------------------------------+
| WARN  | Override (phtml/js/html) | vendor/magento/module-checkout/view/frontend/templates/cart/form.phtml | app/design/frontend/Ampersand/theme/Magento_Checkout/templates/cart/form.phtml |
+-------+--------------------------+------------------------------------------------------------------------+--------------------------------------------------------------------------------+
```

You have an override `app/design/frontend/Ampersand/theme/Magento_Checkout/templates/cart/form.phtml` which replaces `vendor/magento/module-checkout/view/frontend/templates/cart/form.phtml`. 

If the upgrade changes `vendor/magento/module-checkout/view/frontend/templates/cart/form.phtml` you will get this warning.  

Check the changes in the core file with your override/extension, it may be that some changes need to be ported across. 

## WARN - Redundant override

A file that was modified now matches an override of it in the codebase. It is very likely this override can just be removed as it is redundant. 

This check is done after identifying the file as a [WARN - Override (phtml/js/html)](#warn---override-phtmljshtml), but has been converted to a "redundant override" after further analysis of the file contents.

There is some fuzzy matching going on here to ignore things that don't "really" matter, when comparing the files
- Comments
- Whitespace
- `.phtml` files also
  - Ignore differences between short and long echo
  - Ignore trailing `;` at end of single php lines

Example:

```
+-------+--------------------------+-------------------------------------------------------------------------------------------------------------------+----------------------------------------------------------------------------------------------------------------------+
| Level | Type                     | File                                                                                                              | To Check                                                                                                             |
+-------+--------------------------+-------------------------------------------------------------------------------------------------------------------+----------------------------------------------------------------------------------------------------------------------+
| WARN  | Redundant Override       | vendor/ampersand/upgrade-patch-helper-test-hyva-theme-stub/theme/Magento_Checkout/templates/cart/redundant.phtml  | vendor/ampersand/upgrade-patch-helper-test-hyva-theme-extended/theme/Magento_Checkout/templates/cart/redundant.phtml |
+-------+--------------------------+-------------------------------------------------------------------------------------------------------------------+----------------------------------------------------------------------------------------------------------------------+
```

You have an override `vendor/ampersand/upgrade-patch-helper-test-hyva-theme-extended/theme/Magento_Checkout/templates/cart/redundant.phtml` which replaces `vendor/ampersand/upgrade-patch-helper-test-hyva-theme-stub/theme/Magento_Checkout/templates/cart/redundant.phtml`.

Those two files are now identical in functionality, and it is probable that the override can just be removed.

## WARN - DB schema added	    
A third-party `db_schema.xml` affecting the highlighted table has been added. 

This is promoted from an `INFO` to a `WARN` because it is a non-magento extension customising a table defined in a different `db_schema.xml`. 

Example: 

```
+-------+-----------------+--------------------------------------------------------------------------------+-------------+
| Level | Type            | File                                                                           | To Check    |
+-------+-----------------+--------------------------------------------------------------------------------+-------------+
| WARN  | DB schema added | vendor/ampersand/upgrade-patch-helper-test-module/src/module/etc/db_schema.xml | sales_order |
+-------+-----------------+--------------------------------------------------------------------------------+-------------+
```

A new `vendor/ampersand/upgrade-patch-helper-test-module/src/module/etc/db_schema.xml` added in this upgrade modifies a table it does not "own" (ownership of a table is calculated by seeing which `db_schema.xml` defines the primary key). In this example it modifies the `sales_order` table.

You may want to review the table being modified in case this third party code is not taking into account the size of popular tables like `customer_entity` or `sales_order`.

## WARN - DB schema removed	    
A third-party `db_schema.xml` affecting the highlighted table has been removed. 

This is promoted from an `INFO` to a `WARN` because it is a non-magento extension customising a table defined in a different `db_schema.xml`.

Example:

```
+-------+-------------------+--------------------------------------------------------------------------------+----------+
| Level | Type              | File                                                                           | To Check |
+-------+-------------------+--------------------------------------------------------------------------------+----------+
| WARN  | DB schema removed | vendor/ampersand/upgrade-patch-helper-test-module/src/module/etc/db_schema.xml | wishlist |
+-------+-------------------+--------------------------------------------------------------------------------+----------+
```

A schema definition was removed from `vendor/ampersand/upgrade-patch-helper-test-module/src/module/etc/db_schema.xml`, it previously modified a table it does not "own" (ownership of a table is calculated by seeing which `db_schema.xml` defines the primary key). In this example a modification to `wishlist` was removed.

You may want to review the table being modified and verify that this schema modification is desired.

## WARN - DB schema changed	    
A third-party `db_schema.xml` affecting the highlighted table has been changed.

This is promoted from an `INFO` to a `WARN` because it is a non-magento extension customising a table defined in a different `db_schema.xml`.

Example:

```
+-------+-------------------+--------------------------------------------------------------------------------+-----------------+
| Level | Type              | File                                                                           | To Check        |
+-------+-------------------+--------------------------------------------------------------------------------+-----------------+
| WARN  | DB schema changed | vendor/ampersand/upgrade-patch-helper-test-module/src/module/etc/db_schema.xml | customer_entity |
+-------+-------------------+--------------------------------------------------------------------------------+-----------------+
```

A schema definition that previously existed in `vendor/ampersand/upgrade-patch-helper-test-module/src/module/etc/db_schema.xml` was altered, this will modify a table this module does not "own" (ownership of a table is calculated by seeing which `db_schema.xml` defines the primary key).  In this example some existing modification to the `customer_entity` table was altered.

You may want to review the table being modified and verify that this schema modification is desired.

## WARN - DB schema target changed
A `db_schema.xml` which holds the main definition of a table has changed, highlighted are any third-party `db_schema.xml` which may need reviewing based on these changes. 

Example
```
+-------+--------------------------+--------------------------------------------------+-----------------------------------------------------------+
| Level | Type                     | File                                             | To Check                                                  |
+-------+--------------------------+--------------------------------------------------+-----------------------------------------------------------+
| WARN  | DB schema target changed | vendor/magento/module-wishlist/etc/db_schema.xml | app/code/Ampersand/Test/etc/db_schema.xml (wishlist_item) |
+-------+--------------------------+--------------------------------------------------+-----------------------------------------------------------+
```

Your project has `app/code/Ampersand/Test/etc/db_schema.xml` which makes changes to `wishlist_item` for some custom functionality. 

During a magento upgrade the core `vendor/magento/module-wishlist/etc/db_schema.xml` make changes to `wishlist_item`. 

You now have a possible issue where the third party custom code may be conflicting with the core definition. Review the changes in the main definition alongside your customisation to see if it is still compatible or necessary.

## WARN - REST WebAPI added

A third-party `webapi.xml` route has been added.

This is promoted from an `INFO` to a `WARN` because this non-core Magento module adds a route declaration whose HTTP method and URL are also declared in a different `webapi.xml`. Magento merges those declarations into one effective route configuration, so the addition may affect that combined route.

Example:

```
+-------+-------------------+----------------------------------------+-----------------------------------------------------------+
| Level | Type              | File                                   | To Check                                                  |
+-------+-------------------+----------------------------------------+-----------------------------------------------------------+
| WARN  | REST WebAPI added | vendor/example/module-a/etc/webapi.xml | vendor/example/module-b/etc/webapi.xml (POST /V1/example) |
+-------+-------------------+----------------------------------------+-----------------------------------------------------------+
```

A new route in `vendor/example/module-a/etc/webapi.xml` has the same `POST /V1/example` identity as `vendor/example/module-b/etc/webapi.xml`. Magento merges those declarations into one route configuration.

For example, this upgraded-side addition produces the source change; the other module's matching `POST /V1/example` declaration is unchanged:

```diff
--- vendor_orig/example/module-a/etc/webapi.xml
+++ vendor/example/module-a/etc/webapi.xml
@@ -8,4 +8,9 @@
+    <route url="/V1/example" method="POST">
+        <service class="Ampersand\\Example\\Api\\ExampleInterface" method="save"/>
+        <resources><resource ref="anonymous"/></resources>
+    </route>
 </routes>
```

The existing contributor is `vendor/example/module-b/etc/webapi.xml`:

```xml
<route url="/V1/example" method="POST">
    <service class="Example\ModuleB\Api\ExampleInterface" method="save"/>
    <resources><resource ref="anonymous"/></resources>
</route>
```

You may want to review the combined route declaration and verify that its service mapping, authorization, and request data are compatible.

## WARN - REST WebAPI removed

A third-party `webapi.xml` route has been removed.

This is promoted from an `INFO` to a `WARN` because this non-core Magento module removes a route declaration whose HTTP method and URL are also declared in a different `webapi.xml`. Magento merged those declarations into one effective route configuration, so the removal may affect the route that remains.

Example:

```
+-------+---------------------+------------------------------------------+----------------------------------------------------------------+
| Level | Type                | File                                     | To Check                                                       |
+-------+---------------------+------------------------------------------+----------------------------------------------------------------+
| WARN  | REST WebAPI removed | vendor/example/module-foo/etc/webapi.xml | vendor/example/module-bar/etc/webapi.xml (GET /V1/example/:id) |
+-------+---------------------+------------------------------------------+----------------------------------------------------------------+
```

A route removed from `vendor/example/module-foo/etc/webapi.xml` previously had the same `GET /V1/example/:id` identity as `vendor/example/module-bar/etc/webapi.xml`. Magento merged those declarations into one route configuration.

For example, this upgraded-side removal produces the source change; another module still declares `GET /V1/example/:id`:

```diff
--- vendor_orig/example/module-foo/etc/webapi.xml
+++ vendor/example/module-foo/etc/webapi.xml
@@ -8,4 +8,0 @@
-    <route url="/V1/example/:id" method="GET">
-        <service class="Example\\Foo\\Api\\ExampleInterface" method="get"/>
-        <resources><resource ref="anonymous"/></resources>
-    </route>
```

The remaining contributor is `vendor/example/module-bar/etc/webapi.xml`:

```xml
<route url="/V1/example/:id" method="GET">
    <service class="Example\Bar\Api\ExampleInterface" method="get"/>
    <resources><resource ref="anonymous"/></resources>
</route>
```

You may want to review the remaining route declaration and verify the removal is intended and compatible.

## WARN - REST WebAPI changed

A third-party `webapi.xml` route has been changed.

This is promoted from an `INFO` to a `WARN` because this non-core Magento module changes a route declaration whose HTTP method and URL are also declared in a different `webapi.xml`. Magento merges those declarations into one effective route configuration, so the change may affect that combined route.

Example:

```
+-------+---------------------+------------------------------------------+----------------------------------------------------------------+
| Level | Type                | File                                     | To Check                                                       |
+-------+---------------------+------------------------------------------+----------------------------------------------------------------+
| WARN  | REST WebAPI changed | vendor/example/module-foo/etc/webapi.xml | vendor/example/module-bar/etc/webapi.xml (GET /V1/example/:id) |
+-------+---------------------+------------------------------------------+----------------------------------------------------------------+
```

A route in `vendor/example/module-foo/etc/webapi.xml` changed while `vendor/example/module-bar/etc/webapi.xml`, a separate third-party module, declares the same `GET /V1/example/:id` identity. Magento merges those declarations into one route configuration.

For example, this upgraded-side declaration change produces the source change; `vendor/example/module-bar/etc/webapi.xml` still contributes the same route identity:

```diff
--- vendor_orig/example/module-foo/etc/webapi.xml
+++ vendor/example/module-foo/etc/webapi.xml
@@ -8,1 +8,1 @@
-    <route url="/V1/example/:id" method="GET">
+    <route url="/V1/example/:id" method="GET" secure="true">
```

The unchanged contributor is `vendor/example/module-bar/etc/webapi.xml`:

```xml
<route url="/V1/example/:id" method="GET">
    <service class="Example\Bar\Api\ExampleInterface" method="get"/>
    <resources><resource ref="anonymous"/></resources>
</route>
```

You may want to review the combined route declaration and verify that its service mapping, authorization, and request data remain compatible.

## WARN - REST WebAPI target changed

A Magento route was added, removed, or changed while a third-party `webapi.xml` contributes to the same route.

Example:

```
+-------+----------------------------+--------------------------------------------+------------------------------------------------------------------+
| Level | Type                       | File                                       | To Check                                                         |
+-------+----------------------------+--------------------------------------------+------------------------------------------------------------------+
| WARN  | REST WebAPI target changed | vendor/magento/module-store/etc/webapi.xml | app/code/Vendor/Module/etc/webapi.xml (GET /V1/store/storeViews) |
+-------+----------------------------+--------------------------------------------+------------------------------------------------------------------+
```

Your project has `app/code/Vendor/Module/etc/webapi.xml`, which contributes to `GET /V1/store/storeViews` alongside Magento's route declaration.

During the upgrade `vendor/magento/module-store/etc/webapi.xml` changed that route. Magento merges both declarations, so the core change may conflict with or be affected by the third-party contribution.

For example, this core change produces the source change while the app-code declaration remains in place:

```diff
--- vendor_orig/magento/module-store/etc/webapi.xml
+++ vendor/magento/module-store/etc/webapi.xml
@@ -42,1 +42,1 @@
-    <route url="/V1/store/storeViews" method="GET">
+    <route url="/V1/store/storeViews" method="GET" secure="true">
```

```xml
<!-- app/code/Vendor/Module/etc/webapi.xml (unchanged) -->
<route url="/V1/store/storeViews" method="GET">
    <service class="Vendor\Module\Api\StoreViewInterface" method="getList"/>
    <resources><resource ref="anonymous"/></resources>
</route>
```

Review the Magento change alongside the third-party declaration to verify it is still compatible or necessary.

## WARN - GraphQL Query added

A `Query` field in a third-party `schema.graphqls` file has been added.

This is promoted from an `INFO` to a `WARN` because another `schema.graphqls` file defines the same `Query` field.

Example:
```
+-------+---------------------+------------------------------------------------+------------------------------------------------------------------------------+
| Level | Type                | File                                           | To Check                                                                     |
+-------+---------------------+------------------------------------------------+------------------------------------------------------------------------------+
| WARN  | GraphQL Query added | vendor/acme/module-loyalty/etc/schema.graphqls | app/code/Acme/LoyaltyCustomization/etc/schema.graphqls (Query loyaltyPoints) |
+-------+---------------------+------------------------------------------------+------------------------------------------------------------------------------+
```

A new `Query loyaltyPoints` field in `vendor/acme/module-loyalty/etc/schema.graphqls` matches the project customisation in `app/code/Acme/LoyaltyCustomization/etc/schema.graphqls`:

```diff
--- vendor_orig/acme/module-loyalty/etc/schema.graphqls
+++ vendor/acme/module-loyalty/etc/schema.graphqls
 type Query {
+    loyaltyPoints: LoyaltyPoints @resolver(class: "Acme\\Loyalty\\Model\\Resolver\\LoyaltyPoints")
 }
```

```graphql
# app/code/Acme/LoyaltyCustomization/etc/schema.graphqls (unchanged)
type Query {
    loyaltyPoints: LoyaltyPoints @cache(cacheIdentity: "Acme\\LoyaltyCustomization\\Model\\LoyaltyPointsIdentity")
}
```

Review both field definitions, including their resolver, arguments, return type/nullability, cache and directives. This check identifies matching field definitions; it does not calculate the final schema Magento exposes.

## WARN - GraphQL Query removed

A `Query` field in a third-party `schema.graphqls` file has been removed.

This is promoted from an `INFO` to a `WARN` because another `schema.graphqls` file still defines the same `Query` field.

Example:
```
+-------+-----------------------+------------------------------------------------------------+-----------------------------------------------------------------------------------------+
| Level | Type                  | File                                                       | To Check                                                                                |
+-------+-----------------------+------------------------------------------------------------+-----------------------------------------------------------------------------------------+
| WARN  | GraphQL Query removed | vendor/example/module-customer-support/etc/schema.graphqls | app/code/Example/CustomerSupportCustomization/etc/schema.graphqls (Query customerNotes) |
+-------+-----------------------+------------------------------------------------------------+-----------------------------------------------------------------------------------------+
```

```diff
--- vendor_orig/example/module-customer-support/etc/schema.graphqls
+++ vendor/example/module-customer-support/etc/schema.graphqls
 type Query {
-    customerNotes: [CustomerNote!]! @resolver(class: "Example\\CustomerSupport\\Model\\Resolver\\CustomerNotes")
 }
```

The removed `Query customerNotes` field in `vendor/example/module-customer-support/etc/schema.graphqls` previously matched the project customisation in `app/code/Example/CustomerSupportCustomization/etc/schema.graphqls`:

```graphql
# app/code/Example/CustomerSupportCustomization/etc/schema.graphqls (unchanged)
type Query {
    customerNotes: [CustomerNote!]! @resolver(class: "Example\\CustomerSupportCustomization\\Model\\Resolver\\CustomerNotes")
}
```

Review the remaining field definition and verify that the removal is intended and compatible with its resolver, arguments and callers.

## WARN - GraphQL Query changed

A `Query` field in a third-party `schema.graphqls` file has been changed.

This is promoted from an `INFO` to a `WARN` because another `schema.graphqls` file defines the same `Query` field.

Example:
```
+-------+-----------------------+----------------------------------------------------------+-----------------------------------------------------------------------------------------+
| Level | Type                  | File                                                     | To Check                                                                                |
+-------+-----------------------+----------------------------------------------------------+-----------------------------------------------------------------------------------------+
| WARN  | GraphQL Query changed | vendor/acme/module-customer-segments/etc/schema.graphqls | app/code/Acme/CustomerSegmentsCustomization/etc/schema.graphqls (Query customerSegment) |
+-------+-----------------------+----------------------------------------------------------+-----------------------------------------------------------------------------------------+
```

The changed `Query customerSegment` field in `vendor/acme/module-customer-segments/etc/schema.graphqls` switches resolver class while the project customisation in `app/code/Acme/CustomerSegmentsCustomization/etc/schema.graphqls` still defines the same field:

```diff
--- vendor_orig/acme/module-customer-segments/etc/schema.graphqls
+++ vendor/acme/module-customer-segments/etc/schema.graphqls
-    customerSegment: CustomerSegment @resolver(class: "Acme\\CustomerSegments\\Model\\Resolver\\CustomerSegment")
+    customerSegment: CustomerSegment @resolver(class: "Acme\\CustomerSegments\\Model\\Resolver\\CustomerSegmentResolver")
```

```graphql
# app/code/Acme/CustomerSegmentsCustomization/etc/schema.graphqls (unchanged)
type Query {
    customerSegment: CustomerSegment @resolver(class: "Acme\\CustomerSegmentsCustomization\\Model\\Resolver\\CustomerSegment")
}
```

Review both resolver classes and the field's arguments, return type/nullability, cache and directives.

## WARN - GraphQL Query target changed

A Magento `Query` field has changed while a third-party `schema.graphqls` file defines the same field.

Example:
```
+-------+------------------------------+----------------------------------------------------------+----------------------------------------------------------------------------------+
| Level | Type                         | File                                                     | To Check                                                                         |
+-------+------------------------------+----------------------------------------------------------+----------------------------------------------------------------------------------+
| WARN  | GraphQL Query target changed | vendor/magento/module-store-graph-ql/etc/schema.graphqls | app/code/Vendor/StoreConfigCustomization/etc/schema.graphqls (Query storeConfig) |
+-------+------------------------------+----------------------------------------------------------+----------------------------------------------------------------------------------+
```

Your project has `app/code/Vendor/StoreConfigCustomization/etc/schema.graphqls`, which customises `Query storeConfig`:

```diff
--- vendor_orig/magento/module-store-graph-ql/etc/schema.graphqls
+++ vendor/magento/module-store-graph-ql/etc/schema.graphqls
-    storeConfig : StoreConfig @resolver(class: "Magento\\StoreGraphQl\\Model\\Resolver\\StoreConfigResolver")
+    storeConfig : StoreConfig! @resolver(class: "Magento\\StoreGraphQl\\Model\\Resolver\\StoreConfigResolver")
```

```graphql
# app/code/Vendor/StoreConfigCustomization/etc/schema.graphqls (unchanged)
type Query {
    storeConfig: StoreConfig @cache(cacheIdentity: "Vendor\\StoreConfigCustomization\\Model\\StoreConfigIdentity")
}
```

During the upgrade `vendor/magento/module-store-graph-ql/etc/schema.graphqls` changed `Query storeConfig`. Review the Magento change alongside the third-party field definition to verify that it is still compatible or necessary. 

## WARN - GraphQL Mutation added

A `Mutation` field in a third-party `schema.graphqls` file has been added.

This is promoted from an `INFO` to a `WARN` because another `schema.graphqls` file defines the same `Mutation` field.

Example:
```
+-------+------------------------+-----------------------------------------------------+--------------------------------------------------------------------------------------+
| Level | Type                   | File                                                | To Check                                                                             |
+-------+------------------------+-----------------------------------------------------+--------------------------------------------------------------------------------------+
| WARN  | GraphQL Mutation added | vendor/acme/module-gift-message/etc/schema.graphqls | app/code/Acme/GiftMessageCustomization/etc/schema.graphqls (Mutation addGiftMessage) |
+-------+------------------------+-----------------------------------------------------+--------------------------------------------------------------------------------------+
```

A new `Mutation addGiftMessage` field in `vendor/acme/module-gift-message/etc/schema.graphqls` matches the project customisation in `app/code/Acme/GiftMessageCustomization/etc/schema.graphqls`:

```diff
--- vendor_orig/acme/module-gift-message/etc/schema.graphqls
+++ vendor/acme/module-gift-message/etc/schema.graphqls
 type Mutation {
+    addGiftMessage(input: GiftMessageInput!): GiftMessageOutput @resolver(class: "Acme\\GiftMessage\\Model\\Resolver\\AddGiftMessage")
 }
```

```graphql
# app/code/Acme/GiftMessageCustomization/etc/schema.graphqls (unchanged)
type Mutation {
    addGiftMessage(input: GiftMessageInput!): GiftMessageOutput @cache(cacheIdentity: "Acme\\GiftMessageCustomization\\Model\\GiftMessageIdentity")
}
```

Review both field definitions, including their resolver, arguments, return type/nullability, cache, directives and callers.

## WARN - GraphQL Mutation removed

A `Mutation` field in a third-party `schema.graphqls` file has been removed.

This is promoted from an `INFO` to a `WARN` because another `schema.graphqls` file still defines the same `Mutation` field.

Example:
```
+-------+--------------------------+---------------------------------------------------+----------------------------------------------------------------------------------------+
| Level | Type                     | File                                              | To Check                                                                               |
+-------+--------------------------+---------------------------------------------------+----------------------------------------------------------------------------------------+
| WARN  | GraphQL Mutation removed | vendor/example/module-rewards/etc/schema.graphqls | app/code/Example/RewardsCustomization/etc/schema.graphqls (Mutation applyRewardPoints) |
+-------+--------------------------+---------------------------------------------------+----------------------------------------------------------------------------------------+
```

The removed `Mutation applyRewardPoints` field in `vendor/example/module-rewards/etc/schema.graphqls` previously matched the project customisation in `app/code/Example/RewardsCustomization/etc/schema.graphqls`:

```diff
--- vendor_orig/example/module-rewards/etc/schema.graphqls
+++ vendor/example/module-rewards/etc/schema.graphqls
 type Mutation {
-    applyRewardPoints(input: ApplyRewardPointsInput!): RewardPointsOutput @resolver(class: "Example\\Rewards\\Model\\Resolver\\ApplyRewardPoints")
 }
```

```graphql
# app/code/Example/RewardsCustomization/etc/schema.graphqls (unchanged)
type Mutation {
    applyRewardPoints(input: ApplyRewardPointsInput!): RewardPointsOutput @resolver(class: "Example\\RewardsCustomization\\Model\\Resolver\\ApplyRewardPoints")
}
```

Review the remaining field definition and verify that the removal is intended and compatible with its resolver, arguments and callers.

## WARN - GraphQL Mutation changed

A `Mutation` field in a third-party `schema.graphqls` file has been changed.

This is promoted from an `INFO` to a `WARN` because another `schema.graphqls` file defines the same `Mutation` field.

Example:
```
+-------+--------------------------+-----------------------------------------------------+----------------------------------------------------------------------------------------+
| Level | Type                     | File                                                | To Check                                                                               |
+-------+--------------------------+-----------------------------------------------------+----------------------------------------------------------------------------------------+
| WARN  | GraphQL Mutation changed | vendor/acme/module-store-credit/etc/schema.graphqls | app/code/Acme/StoreCreditCustomization/etc/schema.graphqls (Mutation applyStoreCredit) |
+-------+--------------------------+-----------------------------------------------------+----------------------------------------------------------------------------------------+
```

The changed `Mutation applyStoreCredit` field in `vendor/acme/module-store-credit/etc/schema.graphqls` switches resolver class while the project customisation in `app/code/Acme/StoreCreditCustomization/etc/schema.graphqls` still defines the same field:

```diff
--- vendor_orig/acme/module-store-credit/etc/schema.graphqls
+++ vendor/acme/module-store-credit/etc/schema.graphqls
-    applyStoreCredit(input: ApplyStoreCreditInput!): StoreCreditOutput @resolver(class: "Acme\\StoreCredit\\Model\\Resolver\\ApplyStoreCredit")
+    applyStoreCredit(input: ApplyStoreCreditInput!): StoreCreditOutput @resolver(class: "Acme\\StoreCredit\\Model\\Resolver\\ApplyStoreCreditV2")
```

```graphql
# app/code/Acme/StoreCreditCustomization/etc/schema.graphqls (unchanged)
type Mutation {
    applyStoreCredit(input: ApplyStoreCreditInput!): StoreCreditOutput @resolver(class: "Acme\\StoreCreditCustomization\\Model\\Resolver\\ApplyStoreCredit")
}
```

Review both resolver classes and the field's arguments, return type/nullability, cache, directives and callers.

## WARN - GraphQL Mutation target changed

A Magento `Mutation` field has changed while a third-party `schema.graphqls` file defines the same field.

Example:
```
+-------+---------------------------------+-------------------------------------------------------------+-------------------------------------------------------------------------------------------------+
| Level | Type                            | File                                                        | To Check                                                                                        |
+-------+---------------------------------+-------------------------------------------------------------+-------------------------------------------------------------------------------------------------+
| WARN  | GraphQL Mutation target changed | vendor/magento/module-customer-graph-ql/etc/schema.graphqls | app/code/Vendor/CustomerTokenCustomization/etc/schema.graphqls (Mutation generateCustomerToken) |
+-------+---------------------------------+-------------------------------------------------------------+-------------------------------------------------------------------------------------------------+
```

Your project has `app/code/Vendor/CustomerTokenCustomization/etc/schema.graphqls`, which customises `Mutation generateCustomerToken`:

```diff
--- vendor_orig/magento/module-customer-graph-ql/etc/schema.graphqls
+++ vendor/magento/module-customer-graph-ql/etc/schema.graphqls
-    generateCustomerToken(email: String!, password: String!): CustomerToken @resolver(class: "\\Magento\\CustomerGraphQl\\Model\\Resolver\\GenerateCustomerToken")
+    generateCustomerToken(email: String!, password: String!): CustomerToken! @resolver(class: "\\Magento\\CustomerGraphQl\\Model\\Resolver\\GenerateCustomerToken")
```

```graphql
# app/code/Vendor/CustomerTokenCustomization/etc/schema.graphqls (unchanged)
type Mutation {
    generateCustomerToken(email: String!, password: String!): CustomerToken @resolver(class: "Vendor\\CustomerTokenCustomization\\Model\\Resolver\\GenerateCustomerToken")
}
```

During the upgrade `vendor/magento/module-customer-graph-ql/etc/schema.graphqls` changed `Mutation generateCustomerToken`. Review the Magento change alongside the third-party field definition to verify that it is still compatible or necessary. 

## INFO - Queue consumer added	    
A queue consumer has been added. 

Example:
```
+-------+----------------------+----------------------------------------------------------------+------------------------------+
| Level | Type                 | File                                                           | To Check                     |
+-------+----------------------+----------------------------------------------------------------+------------------------------+
| INFO  | Queue consumer added | vendor/magento/module-inventory-indexer/etc/queue_consumer.xml | inventory.indexer.sourceItem |
+-------+----------------------+----------------------------------------------------------------+------------------------------+
```

If you manually manage `cron_consumers_runner/consumers` in your `app/etc/config.php` you may want to add `inventory.indexer.sourceItem`.

## INFO - Queue consumer removed	    
A queue consumer has been removed.

Example:

```
+-------+------------------------+------------------------------------------------------+---------------------------------+
| Level | Type                   | File                                                 | To Check                        |
+-------+------------------------+------------------------------------------------------+---------------------------------+
| INFO  | Queue consumer removed | vendor/magento/module-catalog/etc/queue_consumer.xml | product_action_attribute.update |
+-------+------------------------+------------------------------------------------------+---------------------------------+
```

If you manually manage `cron_consumers_runner/consumers` in your `app/etc/config.php` you may want to remove `product_action_attribute.update`.

## INFO - Queue consumer changed	    
A queue consumer has been changed. 

```
+-------+------------------------+------------------------------------------------------------+-----------------+
| Level | Type                   | File                                                       | To Check        |
+-------+------------------------+------------------------------------------------------------+-----------------+
| INFO  | Queue consumer changed | vendor/magento/module-import-export/etc/queue_consumer.xml | exportProcessor |
+-------+------------------------+------------------------------------------------------------+-----------------+
```

Often no action is needed, this is just information that something in the definition of `exportProcessor` has changed.

## INFO - System config added

An `etc/adminhtml/system.xml` file or referenced system fragment has had configuration paths added.

```
+-------+---------------------+--------------------------------------------------------+--------------------------+
| Level | Type                | File                                                   | To Check                 |
+-------+---------------------+--------------------------------------------------------+--------------------------+
| INFO  | System config added | vendor/magento/module-example/etc/adminhtml/system.xml | example/settings/enabled |
+-------+---------------------+--------------------------------------------------------+--------------------------+
```

Review deployment configuration, custom code, and integrations that may need to set or consume the new value.

## INFO - System config removed

An `etc/adminhtml/system.xml` file or referenced system fragment has had configuration paths removed.

```
+-------+-----------------------+--------------------------------------------------------+--------------------------+
| Level | Type                  | File                                                   | To Check                 |
+-------+-----------------------+--------------------------------------------------------+--------------------------+
| INFO  | System config removed | vendor/magento/module-example/etc/adminhtml/system.xml | example/settings/enabled |
+-------+-----------------------+--------------------------------------------------------+--------------------------+
```

Review deployment configuration, custom code, and integrations that may still write or read this value.

## INFO - System config changed

An `etc/adminhtml/system.xml` file or referenced system fragment has had configuration paths changed.

```
+-------+-----------------------+--------------------------------------------------------+--------------------------+
| Level | Type                  | File                                                   | To Check                 |
+-------+-----------------------+--------------------------------------------------------+--------------------------+
| INFO  | System config changed | vendor/magento/module-example/etc/adminhtml/system.xml | example/settings/enabled |
+-------+-----------------------+--------------------------------------------------------+--------------------------+
```

The check ignores `label`, `comment`, `sortOrder`, and `translate` changes. A changed `config_path` is reported as a removal and an addition.

## INFO - CSP added

An `etc/csp_whitelist.xml` declaration has been added.

Example:

```
+-------+-----------+-----------------------------------------------------+-----------------------+
| Level | Type      | File                                                | To Check              |
+-------+-----------+-----------------------------------------------------+-----------------------+
| INFO  | CSP added | vendor/magento/module-example/etc/csp_whitelist.xml | script-src/analytics  |
+-------+-----------+-----------------------------------------------------+-----------------------+
```

Review CSP customisations and integrations that may need the newly allowed source.

## INFO - CSP removed

An `etc/csp_whitelist.xml` declaration has been removed.

Example:

```
+-------+-------------+-----------------------------------------------------+-----------------------+
| Level | Type        | File                                                | To Check              |
+-------+-------------+-----------------------------------------------------+-----------------------+
| INFO  | CSP removed | vendor/magento/module-example/etc/csp_whitelist.xml | script-src/legacy     |
+-------+-------------+-----------------------------------------------------+-----------------------+
```

Review CSP customisations and integrations that may still depend on the removed source.

## INFO - CSP changed

An `etc/csp_whitelist.xml` declaration has changed.

Example:

```
+-------+-------------+-----------------------------------------------------+-----------------------+
| Level | Type        | File                                                | To Check              |
+-------+-------------+-----------------------------------------------------+-----------------------+
| INFO  | CSP changed | vendor/magento/module-example/etc/csp_whitelist.xml | script-src/checkout   |
+-------+-------------+-----------------------------------------------------+-----------------------+
```

Review the upgrade patch for the previous and replacement definitions, then check CSP customisations and integrations for compatibility.

## INFO - REST WebAPI added

A REST route has been added in `etc/webapi.xml`.

Example:

```
+-------+-------------------+------------------------------------------------+--------------------------+
| Level | Type              | File                                           | To Check                 |
+-------+-------------------+------------------------------------------------+--------------------------+
| INFO  | REST WebAPI added | vendor/magento/module-example/etc/webapi.xml  | POST /V1/example         |
+-------+-------------------+------------------------------------------------+--------------------------+
```

Review integrations that may use or need to authorize the new endpoint.

## INFO - REST WebAPI removed

A REST route has been removed from `etc/webapi.xml`.

Example:

```
+-------+---------------------+------------------------------------------------+--------------------------+
| Level | Type                | File                                           | To Check                 |
+-------+---------------------+------------------------------------------------+--------------------------+
| INFO  | REST WebAPI removed | vendor/magento/module-example/etc/webapi.xml  | GET /V1/example/:id      |
+-------+---------------------+------------------------------------------------+--------------------------+
```

Review integrations that may still call the removed endpoint.

## INFO - REST WebAPI changed

A REST route keeps the same URL and HTTP method but its declaration has changed.

Example:

```
+-------+---------------------+------------------------------------------------+--------------------------+
| Level | Type                | File                                           | To Check                 |
+-------+---------------------+------------------------------------------------+--------------------------+
| INFO  | REST WebAPI changed | vendor/magento/module-example/etc/webapi.xml  | GET /V1/example/:id      |
+-------+---------------------+------------------------------------------------+--------------------------+
```

Review the before and after route declarations, including service mapping, authorization, and request data handling, for affected integrations.

## INFO - GraphQL Query added

A root `Query` field has been added in `etc/schema.graphqls`.

Example:

```
+-------+---------------------+----------------------------------------------------+----------------+
| Level | Type                | File                                               | To Check       |
+-------+---------------------+----------------------------------------------------+----------------+
| INFO  | GraphQL Query added | vendor/magento/module-example/etc/schema.graphqls | Query products |
+-------+---------------------+----------------------------------------------------+----------------+
```

This identifies a new public GraphQL query declaration.

## INFO - GraphQL Query removed

A root `Query` field has been removed from `etc/schema.graphqls`.

Example:

```
+-------+-----------------------+----------------------------------------------------+----------------+
| Level | Type                  | File                                               | To Check       |
+-------+-----------------------+----------------------------------------------------+----------------+
| INFO  | GraphQL Query removed | vendor/magento/module-example/etc/schema.graphqls | Query products |
+-------+-----------------------+----------------------------------------------------+----------------+
```

This identifies a removed public GraphQL query declaration.

## INFO - GraphQL Query changed

A root `Query` field keeps the same name but its contract has changed.

Example:

```
+-------+-----------------------+----------------------------------------------------+----------------+
| Level | Type                  | File                                               | To Check       |
+-------+-----------------------+----------------------------------------------------+----------------+
| INFO  | GraphQL Query changed | vendor/magento/module-example/etc/schema.graphqls | Query products |
+-------+-----------------------+----------------------------------------------------+----------------+
```

This identifies a change to its arguments, return type, nullability, directives, or a type declaration reachable from the field in the same changed schema file.

## INFO - GraphQL Mutation added

A root `Mutation` field has been added in `etc/schema.graphqls`.

Example:

```
+-------+------------------------+----------------------------------------------------+-------------------------+
| Level | Type                   | File                                               | To Check                |
+-------+------------------------+----------------------------------------------------+-------------------------+
| INFO  | GraphQL Mutation added | vendor/magento/module-example/etc/schema.graphqls | Mutation createCustomer |
+-------+------------------------+----------------------------------------------------+-------------------------+
```

This identifies a new public GraphQL mutation declaration.

## INFO - GraphQL Mutation removed

A root `Mutation` field has been removed from `etc/schema.graphqls`.

Example:

```
+-------+--------------------------+----------------------------------------------------+-------------------------+
| Level | Type                     | File                                               | To Check                |
+-------+--------------------------+----------------------------------------------------+-------------------------+
| INFO  | GraphQL Mutation removed | vendor/magento/module-example/etc/schema.graphqls | Mutation createCustomer |
+-------+--------------------------+----------------------------------------------------+-------------------------+
```

This identifies a removed public GraphQL mutation declaration.

## INFO - GraphQL Mutation changed

A root `Mutation` field keeps the same name but its contract has changed.

Example:

```
+-------+--------------------------+----------------------------------------------------+-------------------------+
| Level | Type                     | File                                               | To Check                |
+-------+--------------------------+----------------------------------------------------+-------------------------+
| INFO  | GraphQL Mutation changed | vendor/magento/module-example/etc/schema.graphqls | Mutation createCustomer |
+-------+--------------------------+----------------------------------------------------+-------------------------+
```

This identifies a change to its arguments, return type, nullability, directives, or a type declaration reachable from the field in the same changed schema file. 

## INFO - DB schema added	    
A `db_schema.xml` affecting the highlighted table has been added.  

Example:

```
+-------+--------------------------+---------------------------------------------------------+------------------------+
| Level | Type                     | File                                                    | To Check               |
+-------+--------------------------+---------------------------------------------------------+------------------------+
| INFO  | DB schema added          | vendor/magento/module-admin-adobe-ims/etc/db_schema.xml | admin_adobe_ims_webapi |
+-------+--------------------------+---------------------------------------------------------+------------------------+
```

Often no action is needed, this is information that some table definition for `admin_adobe_ims_webapi` was added within `vendor/magento/module-admin-adobe-ims/etc/db_schema.xml`.

## INFO - DB schema removed	    
A `db_schema.xml` affecting the highlighted table has been removed. 

Example:

```
+-------+--------------------+-----------------------------------------------------------------------------------------------+--------------------+
| Level | Type               | File                                                                                          | To Check           |
+-------+--------------------+-----------------------------------------------------------------------------------------------+--------------------+
| INFO  | DB schema removed  | vendor/ampersand/upgrade-patch-helper-test-module-to-be-removed/src/module/etc/db_schema.xml  | some_removed_table |
+-------+--------------------+-----------------------------------------------------------------------------------------------+--------------------+
```

Often no action is needed, this is information that some table definition for `some_removed_table` was removed within `vendor/ampersand/upgrade-patch-helper-test-module-to-be-removed/src/module/etc/db_schema.xml`.

## INFO - DB schema changed	    
A `db_schema.xml` affecting the highlighted table has been changed. 

Example:

```
+-------+-------------------+-------------------------------------------------+-------------+
| Level | Type              | File                                            | To Check    |
+-------+-------------------+-------------------------------------------------+-------------+
| INFO  | DB schema changed | vendor/magento/module-captcha/etc/db_schema.xml | captcha_log |
+-------+-------------------+-------------------------------------------------+-------------+
```

Often no action is needed, this is information that some table definition for `captcha_log` was changed within `vendor/magento/module-captcha/etc/db_schema.xml`.

## INFO - Setup Patch Data

A data patch has been added or changed.

Example:

```
+-------+-------------------+---------------------------------------------------------------------------------------------------+-------------------------------------------------------+
| Level | Type              | File                                                                                              | To Check                                              |
+-------+-------------------+---------------------------------------------------------------------------------------------------+-------------------------------------------------------+
| INFO  | Setup Patch Data  | vendor/ampersand/upgrade-patch-helper-test-module/src/module/Setup/Patch/Data/SomeDataChanges.php | Ampersand\TestVendor\Setup\Patch\Data\SomeDataChanges |
+-------+-------------------+---------------------------------------------------------------------------------------------------+-------------------------------------------------------+
```

Often no action is needed. A setup data patch `Ampersand\TestVendor\Setup\Patch\Data\SomeDataChanges` has been added/changed. You may want to have a look at the code to see what it is doing.

## INFO - Setup Patch Schema

A schema patch has been added or changed.

Example:

```
+-------+--------------------+--------------------------------------------------------------------------------------------------------+-----------------------------------------------------------+
| Level | Type               | File                                                                                                   | To Check                                                  |
+-------+--------------------+--------------------------------------------------------------------------------------------------------+-----------------------------------------------------------+
| INFO  | Setup Patch Schema | vendor/ampersand/upgrade-patch-helper-test-module/src/module/Setup/Patch/Schema/SomeSchemaChanges.php  | Ampersand\TestVendor\Setup\Patch\Schema\SomeSchemaChanges |
+-------+--------------------+--------------------------------------------------------------------------------------------------------+-----------------------------------------------------------+
```

Often no action is needed. A setup schema patch `Ampersand\TestVendor\Setup\Patch\Schema\SomeSchemaChanges` has been added/changed. You may want to have a look at the code to see what it is doing.

## INFO - Setup Script

A legacy style setup script (`InstallSchema`, `InstallData`, `UpgradeData`, `UpgradeSchema`) has been added or changed.

Example:

```
+-------+--------------+--------------------------------------------------------------------------------------+------------------------------------------+
| Level | Type         | File                                                                                 | To Check                                 |
+-------+--------------+--------------------------------------------------------------------------------------+------------------------------------------+
| INFO  | Setup Script | vendor/ampersand/upgrade-patch-helper-test-module/src/module/Setup/InstallSchema.php | Ampersand\TestVendor\Setup\InstallSchema |
+-------+--------------+--------------------------------------------------------------------------------------+------------------------------------------+
```

Often no action is needed. A setup script `Ampersand\TestVendor\Setup\InstallSchema` has been added/changed. You may want to have a look at the code to see what it is doing.
