<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('internal user command creates a role-specific account', function () {
    $this->artisan('user:create', [
        'name' => 'Project Analyst',
        'email' => 'PA@example.com',
        'role' => 'pa',
    ])
        ->expectsQuestion('Password (minimum 12 characters)', 'secure-test-password')
        ->expectsOutput('Created PA account for pa@example.com.')
        ->assertExitCode(0);

    $user = User::query()->where('email', 'pa@example.com')->firstOrFail();

    expect($user->role)->toBe(User::ROLE_PA)
        ->and(Hash::check('secure-test-password', $user->password))->toBeTrue();
});
