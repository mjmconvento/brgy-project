<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

/**
 * Prints the verification link for an account.
 *
 * The escape hatch for when the email does not arrive — Brevo not activated
 * yet, past the daily cap, spam-foldered — or for a local run with
 * `MAIL_MAILER=log`, where the real email lands in `storage/logs/laravel.log`
 * wrapped in a few hundred lines of inlined CSS. This builds the same signed URL
 * directly, so it can be pasted into a browser or sent by hand.
 *
 * Run it with the production APP_KEY and APP_URL for a link that works on the
 * deployed site. It grants nothing that reading the log does not: both need
 * the application key.
 */
class ShowVerificationLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:verification-link {email : The account\'s email address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Print the signed email verification link for an account';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var string $email */
        $email = $this->argument('email');

        $user = User::query()->where('email', $email)->first();

        if (! $user instanceof User) {
            $this->error(sprintf('No account with the address %s.', $email));

            return self::FAILURE;
        }

        if ($user->hasVerifiedEmail()) {
            $this->warn(sprintf('%s is already verified.', $email));
            $this->line('A link is printed anyway; opening it changes nothing.');
        }

        // The same URL Laravel's own VerifyEmail notification builds, with the
        // same lifetime from `auth.verification.expire`.
        $link = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::integer('auth.verification.expire', 60)),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );

        $this->newLine();
        $this->line($link);
        $this->newLine();

        return self::SUCCESS;
    }
}
