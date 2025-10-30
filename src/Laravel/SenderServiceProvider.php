<?php

namespace SenderNet\Laravel;

use Illuminate\Mail\MailManager;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use SenderNet\Laravel\Commands\SenderInstallCommand;
use SenderNet\SenderNet;

class SenderServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->make(MailManager::class)->extend('sender', function (array $config) {
            $senderConfig = $this->app['config']->get('sender', []);

            $options = array_filter([
                'api_key' => Arr::get($senderConfig, 'api_key'),
                'host' => Arr::get($senderConfig, 'host'),
                'protocol' => Arr::get($senderConfig, 'protocol'),
                'api_path' => Arr::get($senderConfig, 'api_path'),
                'timeout' => Arr::get($senderConfig, 'timeout'),
                'debug' => Arr::get($senderConfig, 'debug'),
            ], fn($value, $key) => $value !== null && ($key === 'api_path' || $value !== ''), ARRAY_FILTER_USE_BOTH);

            $sendernet = new SenderNet($options);

            return new SenderTransport($sendernet);
        });

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/sender.php' => config_path('sender.php'),
            ], 'sender-config');

            $this->commands([
                SenderInstallCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/sender.php', 'sender');
    }
}
