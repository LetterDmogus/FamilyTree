<?php

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

test('unregistered google user cannot login', function () {
    $googleUser = new SocialiteUser;
    $googleUser->id = '12345';
    $googleUser->name = 'John Doe';
    $googleUser->email = 'unregistered@example.com';

    Socialite::shouldReceive('driver->user')->andReturn($googleUser);

    $response = $this->get('/auth/google/callback');

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors(['email' => 'Akun dengan email ini belum terdaftar. Silakan hubungi administrator.']);

    $this->assertGuest();
});

test('registered google user can login', function () {
    $user = User::factory()->create([
        'email' => 'registered@example.com',
    ]);

    UserProfile::create([
        'user_id' => $user->id,
        'full_name' => $user->name,
        'gender' => 'M',
        'birth_date' => '1990-01-01',
        'is_alive' => true,
    ]);

    $googleUser = new SocialiteUser;
    $googleUser->id = '12345';
    $googleUser->name = 'John Doe';
    $googleUser->email = 'registered@example.com';

    Socialite::shouldReceive('driver->user')->andReturn($googleUser);

    $response = $this->get('/auth/google/callback');

    $response->assertRedirect(config('fortify.home'));
    $this->assertAuthenticatedAs($user);

    expect($user->fresh()->google_id)->toBe('12345');
});
