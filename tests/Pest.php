<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests hit the real PostgreSQL schema (see phpunit.xml) inside a
| transaction, so foreign keys and their cascade rules are genuinely exercised.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Act as a signed-in barangay administrator.
 */
function asAdmin(?User $user = null): User
{
    $user ??= User::factory()->create();

    test()->actingAs($user);

    return $user;
}
