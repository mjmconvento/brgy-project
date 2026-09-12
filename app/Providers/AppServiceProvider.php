<?php

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Brevo is not one of Laravel's built-in mail transports, so it is
        // registered here — the pattern Laravel's mail documentation prescribes,
        // using Brevo as its own worked example. The closure only runs when the
        // `brevo` mailer is actually resolved, so local (Mailpit) and test
        // (array) runs never build the transport or need the key.
        //
        // `brevo+api` matters. The bare `brevo` scheme is accepted by the factory
        // but routes to the SMTP transport on port 465, which Render's free tier
        // blocks; it then fails with "Password is not set", an error that says
        // nothing about the real mistake.
        //
        // A `Dsn` object rather than a DSN string on purpose: the string form goes
        // through `parse_url()`, so an API key containing `/`, `:` or `@` would
        // silently mis-parse.
        //
        // The client carries an explicit timeout because a hung outbound call
        // otherwise holds a php-fpm worker for the default 100 seconds — and the
        // Render pool is sized to very few workers (docker/php/render-fpm.conf).
        Mail::extend('brevo', fn (): TransportInterface => (new BrevoTransportFactory(
            null,
            HttpClient::create(['timeout' => 10]),
        ))->create(new Dsn(
            'brevo+api',
            'default',
            Config::string('services.brevo.key'),
        )));
    }
}
