<?php

namespace Ampersand\PatchHelper\Patchfile\Entry;

class ContentSecurityDefinitions
{
    /** @var string */
    private $directory;

    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    /**
     * @return array<string, array{label: string, value: string}>
     */
    public function getDefinitions(string $file)
    {
        if (pathinfo($file, PATHINFO_BASENAME) !== 'csp_whitelist.xml') {
            return [];
        }

        $filepath = realpath($this->directory . DIRECTORY_SEPARATOR . $file);
        if (!is_file($filepath)) {
            return [];
        }

        $previousUseInternalErrors = libxml_use_internal_errors(true);
        try {
            libxml_clear_errors();
            $xml = simplexml_load_file($filepath);
            if ($xml === false) {
                $message = 'Unable to parse XML';
                $errorMessages = [];
                foreach (libxml_get_errors() as $error) {
                    $errorMessages[] = sprintf(
                        ' at line %d, column %d: %s',
                        $error->line,
                        $error->column,
                        trim($error->message)
                    );
                }
                if ($errorMessages !== []) {
                    $message .= implode(';', $errorMessages);
                }
                throw new \InvalidArgumentException($message);
            }

            $definitions = [];
            foreach ($xml->policies->policy as $policy) {
                $policyId = (string)$policy['id'];
                if ($policyId === '') {
                    continue;
                }
                foreach ($policy->values as $values) {
                    foreach ($values->value as $value) {
                        $valueId = (string)$value['id'];
                        if ($valueId === '') {
                            continue;
                        }

                        $declaration = $policyId . '/' . $valueId;
                        $valueText = (string)$value;
                        if ((string)$value['type'] === 'host') {
                            $identity = serialize([$policyId, 'host', $valueId]);
                            $effectiveValue = $valueText;
                        } else {
                            $identity = serialize([$policyId, 'hash', $valueText]);
                            $effectiveValue = (string)$value['algorithm'];
                        }
                        $definitions[$identity] = ['label' => $declaration, 'value' => $effectiveValue];
                    }
                }
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseInternalErrors);
        }

        ksort($definitions);
        return $definitions;
    }
}
