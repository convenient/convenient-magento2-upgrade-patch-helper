<?php

namespace Ampersand\PatchHelper\Helper\Magento2Instance;

use Ampersand\PatchHelper\Patchfile\Sanitiser;

class DatabaseSchemaDefinitions
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    /** @var string[] */
    private $xmlFiles;

    /** @var array<string, array<int, string>> */
    private $thirdPartyAlterations = [];

    /** @var array<string, string> */
    private $primaryDefinitions = [];

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string[] $xmlFiles
     */
    public function __construct($objectManager, array $xmlFiles)
    {
        $this->objectManager = $objectManager;
        $this->xmlFiles = $xmlFiles;
    }

    /**
     * Prepare the db schema xml data so we have a map of tables to their primary definitions, and alterations
     * @return void
     */
    public function prepare()
    {
        /*
         * Get a list of all db_schema.xml files
         */
        $allDbSchemaFiles = array_unique(array_filter($this->xmlFiles, function ($potentialDbSchema) {
            if (!str_ends_with($potentialDbSchema, '/etc/db_schema.xml')) {
                return false; // This has to be a db_schema.xml file
            }
            if (str_ends_with($potentialDbSchema, '/magento2-base/app/etc/db_schema.xml')) {
                return false; // Ignore base db schema copied into project
            }
            if (str_ends_with($potentialDbSchema, '/magento2-ee-base/app/etc/db_schema.xml')) {
                return false; // Ignore base db schema copied into project
            }
            foreach (['/tests/', '/dev/tools/'] as $dir) {
                if (str_contains($potentialDbSchema, $dir)) {
                    return false;
                }
            }
            return true;
        }));

        $rootDir = $this->objectManager->get(\Magento\Framework\Filesystem\DirectoryList::class)->getRoot();

        /*
         * Read all the db_schema files and record the file->table associations, as well as identifying primary keys
         */
        $tablesAndTheirSchemas = [];
        foreach ($allDbSchemaFiles as $dbSchemaFile) {
            $contents = file_get_contents($dbSchemaFile);
            $contents = Sanitiser::stripCommentsFromXml($contents);
            $xml = simplexml_load_string($contents);
            foreach ($xml->table as $table) {
                unset($table->comment);
                $tableXml = $table->asXML();
                $tableName = (string) $table->attributes()->name;
                $tablesAndTheirSchemas[$tableName][] =
                    [
                        'file' => sanitize_filepath($rootDir, $dbSchemaFile),
                        'definition' => $tableXml,
                        'is_primary' => (str_contains(strtolower($tableXml), 'xsi:type="primary"'))
                    ];
            }
            unset($xml, $table, $tableXml, $tableName, $dbSchemaFile);
        }
        ksort($tablesAndTheirSchemas);

        /*
         * Work through this list and figure out which schema are the primary definition of a table, separate them out
         */
        $tablesWithTooFewPrimaryDefinitions = $tablesWithTooManyPrimaryDefinitions = [];
        foreach ($tablesAndTheirSchemas as $tableName => $schemaDatas) {
            $primarySchemas = array_values(array_filter($schemaDatas, function ($schema) {
                return $schema['is_primary'];
            }));
            $thirdPartyAlterationSchemas = array_values(array_filter($schemaDatas, function ($schema) {
                return !$schema['is_primary'] && !str_starts_with($schema['file'], 'vendor/magento/');
            }));
            $magentoAlterationSchemas = array_values(array_filter($schemaDatas, function ($schema) {
                return !$schema['is_primary'] && str_starts_with($schema['file'], 'vendor/magento/');
            }));
            foreach ($thirdPartyAlterationSchemas as $schema) {
                $this->thirdPartyAlterations[$tableName][] = $schema['file'];
            }

            if (count($primarySchemas) <= 0) {
                $tablesWithTooFewPrimaryDefinitions[$tableName] = $schemaDatas;
                if (!empty($magentoAlterationSchemas)) {
                    // We have a magento definition for this keyless table, assume it's the base
                    $this->primaryDefinitions[$tableName] = $magentoAlterationSchemas[0]['file'];
                }
            }
            if (count($primarySchemas) === 1) {
                $this->primaryDefinitions[$tableName] =  $primarySchemas[0]['file'];
            }
            if (count($primarySchemas) > 1) {
                /*
                 * TODO work out what to do when too many primary key tables are defined
                 *
                 * currently they won't be reported as warnings
                 */
                $tablesWithTooManyPrimaryDefinitions[$tableName] = $schemaDatas;
            }
            unset($primarySchemas, $thirdPartyAlterationSchemas, $magentoAlterationSchemas, $tableName, $schemaDatas);
        }
    }

    /** @return array<string, array<int, string>> */
    public function getThirdPartyAlterations()
    {
        return $this->thirdPartyAlterations;
    }

    /** @return array<string, string> */
    public function getPrimaryDefinitions()
    {
        return $this->primaryDefinitions;
    }
}
