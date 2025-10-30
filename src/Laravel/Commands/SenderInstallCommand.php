<?php

namespace SenderNet\Laravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SenderInstallCommand extends Command
{
    protected $signature = 'sender:install';

    protected $description = 'Install and configure Sender for Laravel';

    public function handle(): int
    {
        $this->components->info('Sender Laravel Installation');
        $this->newLine();

        $this->publishConfiguration();
        $this->configureEnvironment();
        $this->updateMailConfiguration();

        $this->newLine();
        $this->components->info('Sender has been installed successfully!');
        $this->newLine();
        $this->line('Next steps:');
        $this->line('  1. Set your SENDER_API_KEY in .env file');
        $this->line('  2. Configure SENDER_API_HOST if using custom endpoint');
        $this->line('  3. Send test email: Mail::raw("Test", fn($m) => $m->to("test@example.com"));');
        $this->newLine();

        return self::SUCCESS;
    }

    protected function publishConfiguration(): void
    {
        $this->components->task('Publishing configuration', function () {
            $this->call('vendor:publish', [
                '--tag' => 'sender-config',
                '--force' => false,
            ]);
        });
    }

    protected function configureEnvironment(): void
    {
        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            $this->components->warn('.env file not found');
            return;
        }

        $envContents = File::get($envPath);

        if (str_contains($envContents, 'SENDER_API_KEY')) {
            $this->components->info('SENDER_API_KEY already exists in .env');
            return;
        }

        $this->components->task('Adding Sender configuration to .env', function () use ($envPath, $envContents) {
            $senderConfig = "\n# Sender Mail Configuration\n";
            $senderConfig .= "SENDER_API_KEY=\n";
            $senderConfig .= "SENDER_API_HOST=api.sender.net\n";
            $senderConfig .= "SENDER_API_PROTO=https\n";
            $senderConfig .= "SENDER_API_PATH=v2\n";
            $senderConfig .= "SENDER_TIMEOUT=30\n";
            $senderConfig .= "SENDER_DEBUG=false\n";

            File::put($envPath, $envContents . $senderConfig);
        });
    }

    protected function updateMailConfiguration(): void
    {
        if (config('mail.default') === 'sender') {
            $this->components->info('Mail driver already set to sender');
            return;
        }

        if ($this->components->confirm('Set sender as default mail driver?', true)) {
            $envPath = base_path('.env');
            $envContents = File::get($envPath);

            $this->components->task('Updating MAIL_MAILER', function () use ($envPath, $envContents) {
                $updated = preg_replace(
                    '/MAIL_MAILER=.*/',
                    'MAIL_MAILER=sender',
                    $envContents
                );

                if ($updated === $envContents) {
                    $updated .= "\nMAIL_MAILER=sender\n";
                }

                File::put($envPath, $updated);
            });
        }
    }
}
