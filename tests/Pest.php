<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Pest closure-based tests in tests/Feature use the app TestCase and a fresh
| database. Class-based (PHPUnit-style) tests keep their own setup.
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
 * Seed the default permissions and roles (superadmin, admin, operator).
 */
function seedRolesAndPermissions(): void
{
    test()->seed([PermissionSeeder::class, RoleSeeder::class]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
}

/**
 * Create a verified user with the given role.
 */
function userWithRole(string $role, array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->assignRole($role);

    return $user;
}
