<?php

namespace Ampersand\PatchHelper\Patchfile\Entry;

class WebApiRestDefinitions
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
        if (pathinfo($file, PATHINFO_BASENAME) !== 'webapi.xml') {
            return [];
        }

        $filepath = realpath($this->directory . DIRECTORY_SEPARATOR . $file);
        if (!is_file($filepath)) {
            return [];
        }

        $previousUseInternalErrors = libxml_use_internal_errors(true);
        try {
            libxml_clear_errors();
            $xml = simplexml_load_file($filepath, \SimpleXMLElement::class, LIBXML_NOCDATA);
            if ($xml === false) {
                $message = 'Unable to parse XML';
                foreach (libxml_get_errors() as $error) {
                    $message .= sprintf(
                        ' at line %d, column %d: %s',
                        $error->line,
                        $error->column,
                        trim($error->message)
                    );
                }
                throw new \InvalidArgumentException($message);
            }
            $definitions = [];
            foreach ($xml->route as $index => $route) {
                $url = trim((string)$route['url']);
                $method = strtoupper(trim((string)$route['method']));
                if ($url === '' || $method === '') {
                    continue;
                }

                $label = $method . ' ' . $url;
                $definitions[$label] = [
                    'label' => $label,
                    'value' => md5(serialize($this->getRouteData($route)))
                ];
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseInternalErrors);
        }

        ksort($definitions);
        return $definitions;
    }

    /**
     * @return array<string, mixed>
     */
    private function getRouteData(\SimpleXMLElement $route): array
    {
        $service = null;
        if (isset($route->service[0])) {
            $service = [
                'class' => (string)$route->service[0]['class'],
                'method' => (string)$route->service[0]['method']
            ];
        }
        $resources = [];
        if (isset($route->resources[0])) {
            foreach ($route->resources[0]->resource as $resource) {
                $reference = (string)$resource['ref'];
                if ($reference !== '') {
                    $resources[$reference] = true;
                }
            }
        }

        $parameters = [];
        $inputArraySizeLimit = null;
        if (isset($route->data[0])) {
            $data = $route->data[0];
            foreach ($data->parameter as $parameter) {
                $name = (string)$parameter['name'];
                if ($name === '') {
                    continue;
                }

                $parameters[$name] = [
                    'force' => isset($parameter['force'])
                        ? filter_var((string)$parameter['force'], FILTER_VALIDATE_BOOLEAN)
                        : false,
                    'value' => (string)$parameter === 'null' ? null : (string)$parameter
                ];
            }
            if (isset($data['input-array-size-limit'])) {
                $inputArraySizeLimit = (int)$data['input-array-size-limit'];
            }
        }

        $definition = [
            'secure' => isset($route['secure']) ? (bool)trim((string)$route['secure']) : false,
            'service' => $service,
            'resources' => $resources,
            'parameters' => $parameters,
            'input-array-size-limit' => $inputArraySizeLimit
        ];
        recur_ksort($definition);
        return $definition;
    }
}
