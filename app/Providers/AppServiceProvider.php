<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RepositoryServiceProvider::class);
    }

    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        app()->setLocale(config('app.locale', 'en'));

        $this->registerInsecureSmtpTransport();
    }

    /**
     * Override the default 'smtp' transport so that, when MAIL_VERIFY_PEER=false,
     * the TLS certificate is not verified. This is required for internal mail
     * servers that present self-signed or untrusted certificates (otherwise
     * sending fails with "certificate verify failed").
     */
    private function registerInsecureSmtpTransport(): void
    {
        Mail::extend('smtp', function (array $config) {
            $factory = new EsmtpTransportFactory();

            $scheme = ($config['encryption'] ?? null) === 'tls'
                ? (((int) ($config['port'] ?? 587)) === 465 ? 'smtps' : 'smtp')
                : 'smtp';

            $transport = $factory->create(new Dsn(
                $scheme,
                $config['host'] ?? '127.0.0.1',
                $config['username'] ?? null,
                $config['password'] ?? null,
                isset($config['port']) ? (int) $config['port'] : null,
                $config
            ));

            // Disable certificate verification when explicitly requested.
            if (($config['verify_peer'] ?? true) === false) {
                $stream = $transport->getStream();
                if ($stream instanceof SocketStream) {
                    $stream->setStreamOptions([
                        'ssl' => [
                            'verify_peer'       => false,
                            'verify_peer_name'  => false,
                            'allow_self_signed' => true,
                        ],
                    ]);
                }
            }

            return $transport;
        });
    }
}
