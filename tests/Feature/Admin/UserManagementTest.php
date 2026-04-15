<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the user management page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('superadmin');

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Users/Index')
            ->has('items')
            ->has('filters')
        );
});

it('soft deletes a user from the admin page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('superadmin');
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $user))
        ->assertRedirect();

    expect($user->fresh()->deleted_at)->not->toBeNull();
});

it('restores a soft deleted user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('superadmin');
    $user = User::factory()->create();
    $user->delete();

    $this->actingAs($admin)
        ->put(route('admin.users.restore', $user))
        ->assertRedirect();

    expect($user->fresh()->deleted_at)->toBeNull();
});
