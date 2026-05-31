<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMail extends Command
{
    protected $signature = 'mail:test {to : Recipient email address}';
    protected $description = 'Send a test email to verify SMTP configuration';

    public function handle(): int
    {
        $to = $this->argument('to');

        $this->info('── Mail configuration ──────────────────────────');
        $this->line('Mailer      : ' . config('mail.default'));
        $this->line('Host        : ' . config('mail.mailers.smtp.host'));
        $this->line('Port        : ' . config('mail.mailers.smtp.port'));
        $this->line('Encryption  : ' . config('mail.mailers.smtp.encryption'));
        $this->line('Verify peer : ' . (config('mail.mailers.smtp.verify_peer') ? 'true' : 'false (insecure)'));
        $this->line('From        : ' . config('mail.from.address'));
        $this->newLine();

        $this->info("Sending test email to {$to} ...");

        try {
            Mail::raw('This is a test email from the Appreciation Platform. If you received this, SMTP is working.', function ($m) use ($to) {
                $m->to($to)->subject('Appreciation Platform — SMTP Test');
            });

            $this->info('✓ Email sent successfully.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('✗ Failed: ' . $e->getMessage());
            $this->newLine();
            if (str_contains($e->getMessage(), 'certificate verify failed')) {
                $this->warn('TLS certificate could not be verified. Set MAIL_VERIFY_PEER=false in .env, then run: php artisan config:clear');
            }
            return self::FAILURE;
        }
    }
}
