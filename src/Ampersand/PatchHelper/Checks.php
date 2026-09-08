<?php

namespace Ampersand\PatchHelper;

class Checks
{
    public const TYPE_REDUNDANT_OVERRIDE = 'Redundant Override';
    public const TYPE_FILE_OVERRIDE = 'Override (phtml/js/html)';
    public const TYPE_QUEUE_CONSUMER_ADDED = 'Queue consumer added';
    public const TYPE_QUEUE_CONSUMER_REMOVED = 'Queue consumer removed';
    public const TYPE_QUEUE_CONSUMER_CHANGED = 'Queue consumer changed';
    public const TYPE_SYSTEM_CONFIG_ADDED = 'System config added';
    public const TYPE_SYSTEM_CONFIG_REMOVED = 'System config removed';
    public const TYPE_SYSTEM_CONFIG_CHANGED = 'System config changed';
    public const TYPE_CSP_ADDED = 'CSP added';
    public const TYPE_CSP_REMOVED = 'CSP removed';
    public const TYPE_CSP_CHANGED = 'CSP changed';
    public const TYPE_WEBAPI_REST_ADDED = 'REST WebAPI added';
    public const TYPE_WEBAPI_REST_REMOVED = 'REST WebAPI removed';
    public const TYPE_WEBAPI_REST_CHANGED = 'REST WebAPI changed';
    public const TYPE_GRAPHQL_QUERY_ADDED = 'GraphQL Query added';
    public const TYPE_GRAPHQL_QUERY_REMOVED = 'GraphQL Query removed';
    public const TYPE_GRAPHQL_QUERY_CHANGED = 'GraphQL Query changed';
    public const TYPE_GRAPHQL_MUTATION_ADDED = 'GraphQL Mutation added';
    public const TYPE_GRAPHQL_MUTATION_REMOVED = 'GraphQL Mutation removed';
    public const TYPE_GRAPHQL_MUTATION_CHANGED = 'GraphQL Mutation changed';
    public const TYPE_PREFERENCE_REMOVED = 'Preference Removed';
    public const TYPE_PREFERENCE = 'Preference';
    public const TYPE_METHOD_PLUGIN = 'Plugin';
    public const TYPE_METHOD_PLUGIN_DISABLED = 'Plugin Disabled';
    public const TYPE_METHOD_PLUGIN_ENABLED = 'Plugin Enabled';
    public const TYPE_DB_SCHEMA_ADDED = 'DB schema added';
    public const TYPE_DB_SCHEMA_CHANGED = 'DB schema changed';
    public const TYPE_DB_SCHEMA_REMOVED = 'DB schema removed';
    public const TYPE_DB_SCHEMA_TARGET_CHANGED = 'DB schema target changed';
    public const TYPE_SETUP_PATCH_DATA = 'Setup Patch Data';
    public const TYPE_SETUP_PATCH_SCHEMA = 'Setup Patch Schema';
    public const TYPE_SETUP_SCRIPT = 'Setup Script';

    /**
     * @var string[]
     */
    public static $dbSchemaTypes = [
        self::TYPE_DB_SCHEMA_ADDED,
        self::TYPE_DB_SCHEMA_CHANGED,
        self::TYPE_DB_SCHEMA_REMOVED,
        self::TYPE_DB_SCHEMA_TARGET_CHANGED
    ];

    /**
     * @var string[]
     */
    public static $excludeFromThreeWayDiff = [
        self::TYPE_DB_SCHEMA_ADDED,
        self::TYPE_DB_SCHEMA_CHANGED,
        self::TYPE_DB_SCHEMA_REMOVED,
        self::TYPE_DB_SCHEMA_TARGET_CHANGED,
        self::TYPE_SYSTEM_CONFIG_ADDED,
        self::TYPE_SYSTEM_CONFIG_REMOVED,
        self::TYPE_SYSTEM_CONFIG_CHANGED,
        self::TYPE_CSP_ADDED,
        self::TYPE_CSP_REMOVED,
        self::TYPE_CSP_CHANGED,
        self::TYPE_SETUP_PATCH_DATA,
        self::TYPE_SETUP_PATCH_SCHEMA,
        self::TYPE_SETUP_SCRIPT,
        self::TYPE_METHOD_PLUGIN,
        self::TYPE_METHOD_PLUGIN_DISABLED,
        self::TYPE_METHOD_PLUGIN_ENABLED,
    ];
}
