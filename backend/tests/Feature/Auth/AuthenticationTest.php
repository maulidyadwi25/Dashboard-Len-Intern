<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/dashboard');
    $this->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('data.role', User::ROLE_PA);
});

test('guests cannot read project APIs or submit progress', function () {
    $this->getJson('/api/projects')->assertUnauthorized();
    $this->getJson('/api/user')->assertUnauthorized();
    $this->putJson('/api/projects/1/wbs/1.1.1/progress', [
        'percent' => 10,
        'comment' => 'Unauthenticated update.',
    ])->assertUnauthorized();
});

test('public registration is unavailable', function () {
    $this->postJson('/register', [
        'name' => 'Public User',
        'email' => 'public@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();
});

test('users without a progress role cannot update WBS progress', function () {
    $user = User::factory()->create(['role' => 'XX']);

    $this->actingAs($user)
        ->putJson('/api/projects/1/wbs/1.1.1/progress', ['percent' => 10])
        ->assertForbidden();
});

test('sheet cron requires a configured secret', function () {
    config(['services.cron.secret' => null]);

    $this->getJson('/api/cron/sync-sheets')
        ->assertServiceUnavailable();

    config(['services.cron.secret' => 'configured-secret']);

    $this->getJson('/api/cron/sync-sheets?secret=wrong-secret')
        ->assertUnauthorized();
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $response->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect('/dashboard');

    $this->withHeader('Origin', 'http://localhost:5173')
        ->postJson('/api/logout')
        ->assertOk();

    $this->assertGuest('web');
});

test('users are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});
