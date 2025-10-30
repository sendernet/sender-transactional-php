<?php

namespace SenderNet\Helpers;

class BuildUri
{
    protected array $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function execute(string $path, array $params = []): string
    {
        $paramsString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        $apiPath = !empty($this->options['api_path'])
            ? '/' . trim($this->options['api_path'], '/')
            : '';

        $base = sprintf(
            '%s://%s%s/%s',
            $this->options['protocol'],
            $this->options['host'],
            $apiPath,
            $path
        );

        return $paramsString ? $base.'?'.$paramsString : $base;
    }
}
