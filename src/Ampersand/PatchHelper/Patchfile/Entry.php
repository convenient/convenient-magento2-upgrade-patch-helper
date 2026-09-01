<?php

namespace Ampersand\PatchHelper\Patchfile;

use Ampersand\PatchHelper\Exception\PluginDetectionException;
use Ampersand\PatchHelper\Patchfile\Entry\ContentSecurityDefinitions;
use Ampersand\PatchHelper\Patchfile\Entry\DatabaseTableDefinitions;
use Ampersand\PatchHelper\Patchfile\Entry\InterceptablePhpFunctions;
use Ampersand\PatchHelper\Patchfile\Entry\QueueConsumers;
use Ampersand\PatchHelper\Patchfile\Entry\SystemConfigDefinitions;
use Ampersand\PatchHelper\Patchfile\Entry\WebApiRestDefinitions;
use Symfony\Component\Console\Output\OutputInterface as Output;

class Entry
{
    /** @var  string */
    private $directory;

    /** @var string */
    private $newFilePath;

    /** @var  string */
    private $originalFilePath;

    /** @var string|null */
    private $newFileSanitisedContents;

    /** @var  string|null */
    private $originalFileSanitisedContents;

    /** @var SystemConfigDefinitions|null */
    private $systemConfigParser;

    /** @var DatabaseTableDefinitions|null */
    private $databaseTableDefinitions;

    /** @var ContentSecurityDefinitions|null */
    private $contentSecurityDefinitions;

    /** @var WebApiRestDefinitions|null */
    private $webApiRestDefinitions;

    /**
     * @var string[]
     */
    private $lines = [];

    /**
     * @var null|string[]
     */
    private $affectedPhpFunctions;

    /**
     * Entry constructor.
     * @param string $directory
     * @param string $newFilepath
     * @param string $originalFilepath
     */
    public function __construct(string $directory, string $newFilepath, string $originalFilepath)
    {
        $this->directory = $directory;
        $this->newFilePath = $newFilepath;
        $this->originalFilePath = $originalFilepath;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->newFilePath;
    }

    /**
     * @return string
     */
    public function getOriginalPath()
    {
        return $this->originalFilePath;
    }

    /**
     * @param string $string
     * @return void
     */
    public function addLine(string $string)
    {
        $this->lines[] = $string;
    }

    /**
     * We could not get the realpath to the original file, it must have been removed
     *
     * @return bool
     */
    public function fileWasRemoved()
    {
        $path = realpath($this->directory . DIRECTORY_SEPARATOR . $this->newFilePath);
        return !$path;
    }

    /**
     * Detect if your diff shows the file has been added
     *
     * @return bool
     */
    public function fileWasAdded()
    {
        $origPath = realpath($this->directory . DIRECTORY_SEPARATOR . $this->originalFilePath);
        $newPath  = realpath($this->directory . DIRECTORY_SEPARATOR . $this->newFilePath);
        return (!$origPath && $newPath);
    }

    /**
     * This is a redundant override if the override is the same as the vendor file being investigated
     *
     * @param string $filepath
     * @return bool
     */
    public function isRedundantOverride($filepath)
    {
        return !$this->fileWasRemoved() && $this->sanitisedContentsMatch($filepath);
    }

    /**
     * Is the diff actually something that should be ignored
     *
     * eg not
     *   - trailing/leading whitespace
     *   - a comment
     *   - extra newlines
     *
     * @return bool
     */
    public function vendorChangeIsNotMeaningful()
    {
        return $this->fileWasModified() && $this->sanitisedContentsAreTheSame();
    }

    /**
     * Is the diff actually something that should investigated
     *
     * eg not
     *   - trailing/leading whitespace
     *   - a comment
     *   - extra newlines
     *
     * @return bool
     */
    public function vendorChangeIsMeaningful()
    {
        return !$this->vendorChangeIsNotMeaningful();
    }

    /**
     * Detect if the file exists both before and after, if so it was a modification
     *
     * @return bool
     */
    public function fileWasModified()
    {
        $origPath = realpath($this->directory . DIRECTORY_SEPARATOR . $this->originalFilePath);
        $newPath  = realpath($this->directory . DIRECTORY_SEPARATOR . $this->newFilePath);
        return ($origPath && $newPath);
    }

    /**
     * Read the patch file and split into affected chunks
     *
     * @return array<array<int, string>>
     */
    public function getHunks()
    {
        if (!str_starts_with($this->lines[3], '@@')) {
            throw new \InvalidArgumentException("Line 4 of a unified diff should be the hunk " . $this->newFilePath);
        }

        /**
         * Handle "\ No newline at end of file\n" like they do in the git core
         *
         * This prevents it being accidentally treated as a context line
         *
         * @link https://github.com/git/git/commit/82a62015a7b55a56f779b9ddfb98a3b0552d2bb4
         *
         * Filter them out at this stage rather than in addLine they are added to the file so that we can still output
         * the whole
         *
         * patchfile as it was parsed.
         */
        $lines = array_filter($this->lines, function ($line) {
            return !((strlen($line) > 12 && substr($line, 0, 2) === '\ '));
        });

        $hunks = [];
        foreach ($lines as $line) {
            if (str_starts_with($line, '@@') && str_ends_with($line, '@@')) {
                if (isset($chunk)) {
                    $hunks[] = $chunk;
                }
                $chunk = [];
            }
            if (isset($chunk)) {
                $chunk[] = $line;
            }
        }
        if (isset($chunk)) {
            $hunks[] = $chunk;
        }
        return $hunks;
    }

    /**
     * Gather the line numbers (and content) removed from the original file, and added to the new file
     * @param array<array<int, string>> $hunks
     * @return array<string, array<int, string>>
     */
    public function getModifiedLines(array $hunks)
    {
        $modifiedLines = [
            'new' => [],
            'original' => []
        ];

        foreach ($hunks as $chunk) {
            // Get the start / count lines from the hunk meta data
            $hunk = explode(' ', ltrim(rtrim(substr($chunk[0], 2, -2))));
            list($originalStart, $originalCount) = explode(',', substr($hunk[0], 1));
            $originalStart = (int) $originalStart;
            $originalCount = (int) $originalCount;
            list($newStart, $newCount) = explode(',', substr($hunk[1], 1));
            $newStart = (int) $newStart;
            $newCount = (int) $newCount;
            unset($hunk);

            // Strip out any removal lines so we're left with context and addition
            $additionLines = array_values(array_filter($chunk, function ($line) {
                return !str_starts_with($line, '-');
            }));
            // Strip out any addition lines so we're left with context and removal
            $removalLines = array_values(array_filter($chunk, function ($line) {
                return !str_starts_with($line, '+');
            }));

            // Collect addition lines with their associated line number and contents
            foreach ($additionLines as $offset => $additionLine) {
                if (str_starts_with($additionLine, '+')) {
                    $modifiedLines['new'][$newStart + $offset - 1] = substr($additionLine, 1);
                }
            }

            // Collect removal lines with their associated line number and contents
            foreach ($removalLines as $offset => $removalLine) {
                if (str_starts_with($removalLine, '-')) {
                    $modifiedLines['original'][$originalStart + $offset - 1] = substr($removalLine, 1);
                }
            }

            unset($originalStart, $originalCount, $newStart, $newCount);
        }

        return $modifiedLines;
    }

    /**
     * @return string[]
     * @throws PluginDetectionException
     */
    public function getAffectedInterceptablePhpFunctions()
    {
        if (isset($this->affectedPhpFunctions)) {
            return $this->affectedPhpFunctions;
        }

        if (pathinfo($this->newFilePath, PATHINFO_EXTENSION) !== 'php') {
            //Tried to get affected php functions on non php file
            return [];
        }

        if (pathinfo($this->originalFilePath, PATHINFO_EXTENSION) !== 'php') {
            //Tried to get affected php functions on non php file
            return [];
        }

        if ($this->fileWasAdded() || $this->fileWasRemoved()) {
            // We are trying to see if we have a plugin on a file which has only been created/removed
            // We can't do this analysis without the file contents of both before and after so cannot scan
            //
            // These have specific handling within ClassPlugin.php
            return [];
        }
        $this->affectedPhpFunctions = (new InterceptablePhpFunctions(
            $this->newFilePath,
            $this->getFileContents($this->newFilePath),
            $this->getFileContents($this->originalFilePath),
            $this->getModifiedLines($this->getHunks())
        ))->getFunctions();
        return $this->affectedPhpFunctions;
    }

    /**
     * @param string $path
     * @return string[]
     */
    private function getFileContents(string $path)
    {
        $filepath = realpath($path);
        if (!is_file($filepath)) {
            $filepath = realpath($this->directory . DIRECTORY_SEPARATOR . $path);
            if (!is_file($filepath)) {
                throw new \InvalidArgumentException("$path is not a file");
            }
        }

        $contents = explode(PHP_EOL, file_get_contents($filepath));
        return $contents;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return implode(PHP_EOL, $this->lines);
    }

    public function applyToTheme(string $projectDir, string $overrideFile, int $fuzzFactor): ?bool
    {
        $overrideFilePathRelative = sanitize_filepath($projectDir, $overrideFile);

        if (str_starts_with($overrideFilePathRelative, 'vendor/')) {
            return null; // Only attempt to patch local files not vendor overrides which will be in .gitignore
        }

        $tmpPatchFilePath = rtrim($projectDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'tmp.patch';
        $adaptedLines     = [];
        foreach ($this->lines as $line) {
            // Replace files in lines to actually apply the changes to the current theme
            $adaptedLine     = str_replace($this->newFilePath, $overrideFilePathRelative, $line);
            $adaptedLine     = str_replace($this->originalFilePath, $overrideFilePathRelative, $adaptedLine);
            $adaptedLines [] = $adaptedLine;
        }
        file_put_contents($tmpPatchFilePath, implode(PHP_EOL, $adaptedLines));
        $patchCommand = 'patch < ' . $tmpPatchFilePath . ' -p0 -F' . $fuzzFactor . ' --no-backup-if-mismatch -d'
            . rtrim($projectDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $patchResult = 0;
        $patchOutput = '';
        exec($patchCommand, $patchOutput, $patchResult);

        shell_exec('rm ' . $tmpPatchFilePath);

        return $patchResult === 0;
    }

    /**
     * Get Added/Removed Queue Consumers
     *
     * @param string $modifiedLineType
     * @return string[]
     */
    private function getAddedOrRemovedQueueConsumers(string $modifiedLineType = 'new')
    {
        if (pathinfo($this->newFilePath, PATHINFO_BASENAME) !== 'queue_consumer.xml') {
            return [];
        }
        return (new QueueConsumers($this->getModifiedLines($this->getHunks())))
            ->getConsumers($modifiedLineType);
    }

    /**
     * Get Added Queue Consumers
     *
     * @return string[]
     */
    public function getAddedQueueConsumers()
    {
        return $this->getAddedOrRemovedQueueConsumers('new');
    }

    /**
     * Get Removed Queue Consumers
     *
     * @return string[]
     */
    public function getRemovedQueueConsumers()
    {
        return $this->getAddedOrRemovedQueueConsumers('original');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getDatabaseTablesDefinitionsFromOriginalFile()
    {
        return $this->getDatabaseTableDefinitions()->getDefinitions($this->originalFilePath);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getDatabaseTablesDefinitionsFromNewFile()
    {
        return $this->getDatabaseTableDefinitions()->getDefinitions($this->newFilePath);
    }

    /**
     * @param string $file
     * @return array<string, array<string, mixed>>
     */
    /**
     * @return array<string, string>
     */
    public function getSystemConfigDefinitionsFromOriginalFile()
    {
        return $this->getSystemConfigParser()->getDefinitions()['original'];
    }

    /**
     * @return array<string, string>
     */
    public function getSystemConfigDefinitionsFromNewFile()
    {
        return $this->getSystemConfigParser()->getDefinitions()['new'];
    }

    /**
     * @return SystemConfigDefinitions
     */
    private function getSystemConfigParser()
    {
        if ($this->systemConfigParser === null) {
            $this->systemConfigParser = new SystemConfigDefinitions(
                $this->directory,
                $this->newFilePath,
                $this->originalFilePath
            );
        }
        return $this->systemConfigParser;
    }

    /**
     * @return array<string, array{label: string, value: string}>
     */
    public function getContentSecurityDefinitionsFromOriginalFile()
    {
        return $this->getContentSecurityDefinitionParser()->getDefinitions($this->originalFilePath);
    }

    /**
     * @return array<string, array{label: string, value: string}>
     */
    public function getContentSecurityDefinitionsFromNewFile()
    {
        return $this->getContentSecurityDefinitionParser()->getDefinitions($this->newFilePath);
    }

    /**
     * @return array<string, array{label: string, value: string}>
     */
    public function getWebApiRestDefinitionsFromOriginalFile()
    {
        return $this->getWebApiRestDefinitions()->getDefinitions($this->originalFilePath);
    }

    /**
     * @return array<string, array{label: string, value: string}>
     */
    public function getWebApiRestDefinitionsFromNewFile()
    {
        return $this->getWebApiRestDefinitions()->getDefinitions($this->newFilePath);
    }

    /**
     * @return WebApiRestDefinitions
     */
    private function getWebApiRestDefinitions()
    {
        if ($this->webApiRestDefinitions === null) {
            $this->webApiRestDefinitions = new WebApiRestDefinitions($this->directory);
        }
        return $this->webApiRestDefinitions;
    }

    /**
     * @return DatabaseTableDefinitions
     */
    private function getDatabaseTableDefinitions()
    {
        if ($this->databaseTableDefinitions === null) {
            $this->databaseTableDefinitions = new DatabaseTableDefinitions($this->directory);
        }
        return $this->databaseTableDefinitions;
    }

    /**
     * @return ContentSecurityDefinitions
     */
    private function getContentSecurityDefinitionParser()
    {
        if ($this->contentSecurityDefinitions === null) {
            $this->contentSecurityDefinitions = new ContentSecurityDefinitions($this->directory);
        }
        return $this->contentSecurityDefinitions;
    }

    /**
     * @return bool
     */
    public function sanitisedContentsAreTheSame()
    {
        try {
            $origContents = $this->getSanitisedContentsFromOriginalFile();
            $newContents = $this->getSanitisedContentsFromNewFile();
            return strcmp($origContents, $newContents) === 0;
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getSanitisedContentsFromNewFile()
    {
        if (!isset($this->newFileSanitisedContents)) {
            $this->newFileSanitisedContents = $this->getSanitisedFileContents($this->newFilePath);
        }
        return $this->newFileSanitisedContents;
    }

    /**
     * @return string
     */
    public function getSanitisedContentsFromOriginalFile()
    {
        if (!isset($this->originalFileSanitisedContents)) {
            $this->originalFileSanitisedContents = $this->getSanitisedFileContents($this->originalFilePath);
        }
        return $this->originalFileSanitisedContents;
    }

    /**
     * @param string $filepath
     * @return bool
     */
    public function sanitisedContentsMatch($filepath)
    {
        try {
            $filepath = str_replace($this->directory . DIRECTORY_SEPARATOR, '', $filepath);
            $newFileContents = $this->getSanitisedContentsFromNewFile();
            $sanitisedContents = $this->getSanitisedFileContents($filepath);
            return strcmp($newFileContents, $sanitisedContents) === 0;
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * @param string $file
     * @return string
     */
    private function getSanitisedFileContents($file)
    {
        $contents = $this->getFileContents($file);
        $contents = implode(PHP_EOL, $contents);

        switch (pathinfo($file, PATHINFO_EXTENSION)) {
            case 'html':
                $contents = Sanitiser::stripCommentsFromHtml($contents);
                break;
            case 'xml':
                $contents = Sanitiser::stripCommentsFromXml($contents);
                break;
            case 'php':
                $contents = Sanitiser::stripCommentsFromPhp($contents);
                break;
            case 'phtml':
                $contents = Sanitiser::sanitisePhtml($contents);
                break;
            case 'js':
                $contents = Sanitiser::stripCommentsFromJavascript($contents);
                break;
        }

        $contents = Sanitiser::stripWhitespaceAndNewlines($contents);
        return $contents;
    }
}
