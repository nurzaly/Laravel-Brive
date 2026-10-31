<?php

use App\Models\Permission;
use App\Models\Role;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    seedRolesAndPermissions();

    // A non-superadmin role that is allowed to manage roles.
    Role::create(['name' => 'manager'])
        ->givePermissionTo(['create role', 'read role', 'update role', 'delete role']);
});

function permissionIds(array $names): array
{
    return Permission::whereIn('name', $names)->pluck('id')->all();
}

it('hides the superadmin role and permission-management permissions from non-superadmins', function () {
    $this->actingAs(userWithRole('manager'))
        ->get(route('role.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Role/Index')
            ->where('roles.data', fn ($roles) => collect($roles)->pluck('name')->doesntContain('superadmin'))
            ->where('permissions', fn ($perms) => collect($perms)->pluck('name')->doesntContain('create permission'))
        );
});

it('forbids a non-superadmin from updating the superadmin role', function () {
    $superadminRole = Role::findByName('superadmin');

    $this->actingAs(userWithRole('manager'))
        ->put(route('role.update', $superadminRole), [
            'name' => 'hacked',
            'permissions' => permissionIds(['read user']),
        ])
        ->assertForbidden();

    expect($superadminRole->fresh()->name)->toBe('superadmin');
});

it('forbids a non-superadmin from deleting the superadmin role', function () {
    $this->actingAs(userWithRole('manager'))
        ->delete(route('role.destroy', Role::findByName('superadmin')))
        ->assertForbidden();

    expect(Role::where('name', 'superadmin')->exists())->toBeTrue();
});

it('skips the superadmin role when a non-superadmin bulk deletes roles', function () {
    $temp = Role::create(['name' => 'temporary']);

    $this->actingAs(userWithRole('manager'))->post(route('role.destroy-bulk'), [
        'id' => [Role::findByName('superadmin')->id, $temp->id],
    ]);

    expect(Role::where('name', 'superadmin')->exists())->toBeTrue()
        ->and(Role::where('name', 'temporary')->exists())->toBeFalse();
});

it('prevents a non-superadmin from granting permission-management permissions', function () {
    $response = $this->actingAs(userWithRole('manager'))
        ->post(route('role.store'), [
            'name' => 'sneaky',
            'permissions' => [
                Permission::findByName('read user')->id,
                Permission::findByName('create permission')->id,
            ],
        ]);

    $errors = session('errors')?->getBag('default')->keys() ?? [];
    expect($errors)->toContain('permissions.1')->not->toContain('permissions.0');

    expect(Role::where('name', 'sneaky')->exists())->toBeFalse();
});

it('lets a superadmin grant permission-management permissions', function () {
    $this->actingAs(userWithRole('superadmin'))
        ->post(route('role.store'), [
            'name' => 'auditor',
            'permissions' => permissionIds(['read permission', 'create permission']),
        ])
        ->assertSessionHasNoErrors();

    expect(Role::findByName('auditor')->hasPermissionTo('create permission'))->toBeTrue();
});
