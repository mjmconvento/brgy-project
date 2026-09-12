<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Email verification arrived after accounts already existed. Those accounts
     * were created before any link could have been sent, so they are
     * grandfathered in rather than locked out of an application they were
     * already using. Accounts created from here on verify by clicking the link.
     *
     * Runs against the production database from a laptop, like every other
     * migration — see ai_docs/deployment-step-by-step.md §5.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    /**
     * Not reversible: nothing records which rows were grandfathered, and
     * un-verifying every account would lock real users out.
     */
    public function down(): void
    {
        //
    }
};
