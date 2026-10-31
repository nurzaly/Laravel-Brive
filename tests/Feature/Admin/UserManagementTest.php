<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    seedRolesAndPermissions();
});

function newUserPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'operator',
    ], $overrides);
}

describe('access', function () {
    it('redirects guests to the login page', function () {
        $this->get(route('user.index'))->assertRedirect(route('login'));
    });

    it('forbids users without the update user permission from updating users', function () {
        $operator = userWithRole('operator');
        $target = userWithRole('operator');

        $this->actingAs($operator)
            ->put(route('user.update', $target), newUserPayload(['email' => $target->email]))
            ->assertForbidden();
    });

    it('does not crash for a user without any role', function () {
        $user = User::factory()->create();
        $user->givePermissionTo('read user');

        $this->actingAs($user)->get(route('user.index'))->assertOk();
    });
});

describe('index', function () {
    it('hides superadmin accounts from admins, including in search results', function () {
        $admin = userWithRole('admin');
        userWithRole('superadmin', ['name' => 'Boss Besar', 'email' => 'besar@example.com']);
        userWithRole('operator', ['name' => 'Boss Kecil', 'email' => 'kecil@example.com']);

        $this->actingAs($admin)
            ->get(route('user.index', ['search' => 'Boss']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Index')
                ->where('users.total', 1)
                ->where('users.data.0.email', 'kecil@example.com')
                ->where('roles', fn ($roles) => collect($roles)->pluck('name')->doesntContain('superadmin'))
            );
    });

    it('shows superadmin accounts to a superadmin', function () {
        $superadmin = userWithRole('superadmin');
        userWithRole('superadmin', ['name' => 'Boss Besar']);
        userWithRole('operator', ['name' => 'Boss Kecil']);

        $this->actingAs($superadmin)
            ->get(route('user.index', ['search' => 'Boss']))
            ->assertInertia(fn (Assert $page) => $page->where('users.total', 2));
    });
});

describe('store', function () {
    it('lets an admin create an operator', function () {
        $this->actingAs(userWithRole('admin'))
            ->post(route('user.store'), newUserPayload())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'New User created successfully.');

        expect(User::whereEmail('new@example.com')->first()->hasRole('operator'))->toBeTrue();
    });

    it('prevents an admin from assigning the superadmin role', function () {
        $this->actingAs(userWithRole('admin'))
            ->post(route('user.store'), newUserPayload(['role' => 'superadmin']))
            ->assertSessionHasErrors('role');

        expect(User::whereEmail('new@example.com')->exists())->toBeFalse();
    });

    it('rejects a role that does not exist', function () {
        $this->actingAs(userWithRole('superadmin'))
            ->post(route('user.store'), newUserPayload(['role' => 'ghost']))
            ->assertSessionHasErrors('role');
    });

    it('lets a superadmin create another superadmin', function () {
        $this->actingAs(userWithRole('superadmin'))
            ->post(route('user.store'), newUserPayload(['role' => 'superadmin']))
            ->assertSessionHasNoErrors();

        expect(User::whereEmail('new@example.com')->first()->hasRole('superadmin'))->toBeTrue();
    });
});

describe('update', function () {
    it('lets an admin update an operator', function () {
        $target = userWithRole('operator');

        $this->actingAs(userWithRole('admin'))
            ->put(route('user.update', $target), newUserPayload(['name' => 'Renamed', 'email' => $target->email, 'password' => null, 'password_confirmation' => null]))
            ->assertSessionHasNoErrors();

        expect($target->fresh()->name)->toBe('Renamed');
    });

    it('forbids an admin from updating a superadmin', function () {
        $superadmin = userWithRole('superadmin');

        $this->actingAs(userWithRole('admin'))
            ->put(route('user.update', $superadmin), newUserPayload(['email' => $superadmin->email, 'role' => 'admin']))
            ->assertForbidden();

        expect($superadmin->fresh()->hasRole('superadmin'))->toBeTrue();
    });

    it('prevents an admin from promoting themselves to superadmin', function () {
        $admin = userWithRole('admin');

        $this->actingAs($admin)
            ->put(route('user.update', $admin), newUserPayload(['email' => $admin->email, 'role' => 'superadmin']))
            ->assertSessionHasErrors('role');

        expect($admin->fresh()->hasRole('superadmin'))->toBeFalse();
    });
});

describe('destroy', function () {
    it('lets an admin delete an operator', function () {
        $target = userWithRole('operator');

        $this->actingAs(userWithRole('admin'))->delete(route('user.destroy', $target));

        $this->assertModelMissing($target);
    });

    it('forbids an admin from deleting a superadmin', function () {
        $superadmin = userWithRole('superadmin');

        $this->actingAs(userWithRole('admin'))
            ->delete(route('user.destroy', $superadmin))
            ->assertForbidden();

        $this->assertModelExists($superadmin);
    });

    it('forbids users from deleting their own account here', function () {
        $admin = userWithRole('admin');

        $this->actingAs($admin)->delete(route('user.destroy', $admin))->assertForbidden();

        $this->assertModelExists($admin);
    });

    it('skips superadmins and the current user when an admin bulk deletes', function () {
        $admin = userWithRole('admin');
        $superadmin = userWithRole('superadmin');
        $operator = userWithRole('operator');

        $this->actingAs($admin)->post(route('user.destroy-bulk'), [
            'id' => [$admin->id, $superadmin->id, $operator->id],
        ]);

        $this->assertModelExists($admin);
        $this->assertModelExists($superadmin);
        $this->assertModelMissing($operator);
    });
});
