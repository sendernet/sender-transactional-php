<?php

namespace SenderNet;

use SenderNet\Helpers\Arr;
use SenderNet\Common\HttpLayer;
use SenderNet\Endpoints\Email;
use SenderNet\Exceptions\SenderNetException;

/**
 * Sender Mail PHP SDK - Email-focused SenderNet client
 *
 * Class SenderNet
 * @package SenderNet
 */
class SenderNet
{
    protected array $options;
    protected static array $defaultOptions = [
        'host' => 'api.sender.net',
        'protocol' => 'https',
        'api_path' => 'v2',
        'api_key' => '',
        'timeout' => 30,
        'debug' => false,
    ];

    protected ?HttpLayer $httpLayer;

    public Email $email;

    /**
     * @param  array  $options  Additional options for the SDK
     * @param  HttpLayer  $httpLayer
     * @throws SenderNetException
     */
    public function __construct(array $options = [], ?HttpLayer $httpLayer = null)
    {
        $this->setOptions($options);
        $this->setHttpLayer($httpLayer);
        $this->setEndpoints();
    }

    protected function setEndpoints(): void
    {
        $this->email = new Email($this->httpLayer, $this->options);
    }

    protected function setHttpLayer(?HttpLayer $httpLayer = null): void
    {
        $this->httpLayer = $httpLayer ?: new HttpLayer($this->options);
    }

    /**
     * @throws SenderNetException
     */
    protected function setOptions(array $options): void
    {
        $this->options = self::$defaultOptions;

        foreach ($options as $option => $value) {
            if (array_key_exists($option, $this->options)) {
                $this->options[$option] = $value;
            }
        }

        if (empty(Arr::get($this->options, 'api_key'))) {
            Arr::set($this->options, 'api_key', getenv('SENDER_API_KEY'));
        }

        if (empty(Arr::get($this->options, 'api_key'))) {
            throw new SenderNetException('Please set "api_key" in SDK options.');
        }
    }
}
