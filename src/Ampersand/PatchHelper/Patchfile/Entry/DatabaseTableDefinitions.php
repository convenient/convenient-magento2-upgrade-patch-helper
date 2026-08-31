<?php

namespace Ampersand\PatchHelper\Patchfile\Entry;

class DatabaseTableDefinitions
{
    /** @var string */
    private $directory;

    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getDefinitions(string $file)
    {
        if (pathinfo($file, PATHINFO_BASENAME) !== 'db_schema.xml') {
            return [];
        }

        $filepath = realpath($this->directory . DIRECTORY_SEPARATOR . $file);
        if (!is_file($filepath)) {
            return [];
        }

        $xml = simplexml_load_file($filepath);
        $schemaData = json_decode(json_encode((array)$xml), true);
        $tables = [];
        recur_ksort($schemaData['table']);
        $tablesToProcess = $schemaData['table'];
        if (isset($schemaData['table']['@attributes'])) {
            $tablesToProcess = [$schemaData['table']];
        }

        foreach ($tablesToProcess as $tableDefinition) {
            $tableName = $tableDefinition['@attributes']['name'];
            $tables[$tableName] = $tableDefinition;
            $tables[$tableName]['amp_upgrade_hash'] = md5(serialize($tableDefinition));
        }
        return $tables;
    }
}
