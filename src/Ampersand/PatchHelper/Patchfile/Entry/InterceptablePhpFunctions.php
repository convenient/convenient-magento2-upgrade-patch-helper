<?php

namespace Ampersand\PatchHelper\Patchfile\Entry;

use Ampersand\PatchHelper\Exception\PluginDetectionException;

class InterceptablePhpFunctions
{
    /** @var string */
    private $filePath;

    /** @var string[] */
    private $newFileContents;

    /** @var string[] */
    private $originalFileContents;

    /** @var array<string, array<int, string>> */
    private $modifiedLines;

    /**
     * @param string[] $newFileContents
     * @param string[] $originalFileContents
     * @param array<string, array<int, string>> $modifiedLines
     */
    public function __construct(
        string $filePath,
        array $newFileContents,
        array $originalFileContents,
        array $modifiedLines
    ) {
        $this->filePath = $filePath;
        $this->newFileContents = $newFileContents;
        $this->originalFileContents = $originalFileContents;
        $this->modifiedLines = $modifiedLines;
    }

    /**
     * @return string[]
     * @throws PluginDetectionException
     */
    public function getFunctions()
    {
        $affectedFunctions = [];
        foreach ($this->modifiedLines['new'] as $lineNumber => $expectedLine) {
            $affectedFunctions[] = $this->getAffectedFunction($lineNumber, $this->newFileContents, $expectedLine);
        }
        foreach ($this->modifiedLines['original'] as $lineNumber => $expectedLine) {
            $affectedFunctions[] = $this->getAffectedFunction($lineNumber, $this->originalFileContents, $expectedLine);
        }

        $functions = [];
        foreach (array_unique(array_filter($affectedFunctions)) as $affectedFunction) {
            $affectedFunction = substr($affectedFunction, 0, strpos($affectedFunction, '('));
            $affectedFunction = explode('function', $affectedFunction);
            if (isset($affectedFunction[0]) && isset($affectedFunction[1])) {
                $visibility = trim($affectedFunction[0]);
                $name = trim($affectedFunction[1]);
                if (in_array($visibility, ['public'])) {
                    $functions[strtolower($name)] = $name;
                }
            }
        }
        return $functions;
    }

    /**
     * @param int $lineNumber
     * @param string[] $fileContents
     * @param string $expectedLineContents
     * @return bool|string
     * @throws PluginDetectionException
     */
    private function getAffectedFunction(int $lineNumber, array $fileContents, string $expectedLineContents)
    {
        $actualLine = $fileContents[$lineNumber - 1];
        if (strcmp($expectedLineContents, $actualLine) !== 0) {
            throw new PluginDetectionException(
                "$this->filePath - on line $lineNumber - $expectedLineContents does not equal $actualLine"
            );
        }
        return $this->scanAboveForFunctionDeclaration($fileContents, $lineNumber - 1);
    }

    /**
     * @param string[] $fileContents
     * @param int $lineNumber
     * @return bool|string
     */
    private function scanAboveForFunctionDeclaration(array $fileContents, int $lineNumber)
    {
        $line = trim($fileContents[$lineNumber]);
        $phpLinesToSkip = ['use', '#', '//', '/*', '/**', '*', '*/'];
        foreach ($phpLinesToSkip as $lineToSkip) {
            if (str_starts_with($line, $lineToSkip)) {
                return false;
            }
        }

        for ($i = $lineNumber; $i >= 0; $i--) {
            $potentialFunctionDeclaration = trim($fileContents[$i]);
            if (str_contains($potentialFunctionDeclaration, 'function')) {
                foreach ($phpLinesToSkip as $lineToSkip) {
                    if (str_starts_with($potentialFunctionDeclaration, $lineToSkip)) {
                        continue 2;
                    }
                }
                return $potentialFunctionDeclaration;
            }
        }
        return false;
    }
}
