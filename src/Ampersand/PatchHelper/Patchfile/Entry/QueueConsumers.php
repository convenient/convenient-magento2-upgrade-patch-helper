<?php

namespace Ampersand\PatchHelper\Patchfile\Entry;

class QueueConsumers
{
    /** @var array<string, array<int, string>> */
    private $modifiedLines;

    /**
     * @param array<string, array<int, string>> $modifiedLines
     */
    public function __construct(array $modifiedLines)
    {
        $this->modifiedLines = $modifiedLines;
    }

    /**
     * @return string[]
     */
    public function getConsumers(string $modifiedLineType)
    {
        $consumers = [];
        foreach ($this->modifiedLines[$modifiedLineType] as $expectedLine) {
            if (str_contains($expectedLine, '<consumer') && preg_match('/name="([^"]*)"/', $expectedLine, $matches)) {
                $consumers[] = $matches[1];
            }
        }
        return $consumers;
    }
}
