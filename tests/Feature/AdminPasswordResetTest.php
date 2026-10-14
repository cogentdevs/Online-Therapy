<?php

use App\Mail\AdminPasswordResetLinkMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('super-admin', 'web');
    Role::findOrCreate('user', 'web');
    $this->admin = User::factory()->create([
        'email' => 'admin-reset@example.com',
        'password' => 'old-password',
        'is_active' => true,
    ]);
    $this->admin->assignRole('super-admin');
    RateLimiter::clear(adminPasswordResetThrottleKey($this->admin->email));
});

function adminPasswordResetThrottleKey(string $email): string
{
    return 'admin-password-reset:'.hash('sha256', strtolower($email).'|127.0.0.1');
}

test('admin forgot password page and login link use admin routes', function () {
    $this->get(route('admin.password.request'))
        ->assertOk()
        ->assertSee('action="'.route('admin.password.email').'"', false)
        ->assertSee('Back to Login');

    $this->get(route('admin.login'))
        ->assertOk()
        ->assertSee('href="'.route('admin.password.request').'"', false);
});

test('eligible admin receives an admin-specific password reset email', function () {
    Mail::fake();

    $this->post(route('admin.password.email'), ['email' => $this->admin->email])
        ->assertRedirect(route('admin.password.request'))
        ->assertSessionHas('status');

    Mail::assertSent(AdminPasswordResetLinkMail::class, function (AdminPasswordResetLinkMail $mail): bool {
        return $mail->hasTo($this->admin->email)
            && str_contains($mail->resetUrl, '/admin/reset-password/')
            && str_contains($mail->resetUrl, 'email='.urlencode($this->admin->email))
            && $mail->expiresInMinutes === (int) config('auth.passwords.admins.expire');
    });
});

test('unknown and non admin emails receive the same response without an email', function (string $kind) {
    Mail::fake();
    $email = 'unknown@example.com';

    if ($kind === 'frontend-user') {
        $user = User::factory()->create(['email' => 'frontend-only@example.com']);
        $user->assignRole('user');
        $email = $user->email;
    }

    $this->post(route('admin.password.email'), ['email' => $email])
        ->assertRedirect(route('admin.password.request'))
        ->assertSessionHas('status', 'If the email belongs to an eligible admin account, a password reset link has been sent.');

    Mail::assertNothingSent();
})->with(['unknown', 'frontend-user']);

test('admin reset validates password confirmation and minimum password', function (array $changes) {
    $token = Password::broker('admins')->createToken($this->admin);
    $data = [
        'token' => $token,
        'email' => $this->admin->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ];

    $this->post(route('admin.password.update'), array_replace($data, $changes))
        ->assertSessionHasErrors('password');
})->with([
    [['password' => 'short', 'password_confirmation' => 'short']],
    [['password_confirmation' => 'different']],
]);

test('valid admin token resets only the admin password and cannot be reused', function () {
    $token = Password::broker('admins')->createToken($this->admin);
    $data = [
        'token' => $token,
        'email' => $this->admin->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ];

    $this->get(route('admin.password.reset', ['token' => $token, 'email' => $this->admin->email]))
        ->assertOk()
        ->assertSee('Reset Password');

    $this->post(route('admin.password.update'), $data)
        ->assertRedirect(route('admin.login'))
        ->assertSessionHas('status');

    expect(Hash::check('old-password', $this->admin->fresh()->password))->toBeFalse()
        ->and(Hash::check('new-password', $this->admin->fresh()->password))->toBeTrue();

    $this->post(route('admin.password.update'), $data)->assertSessionHasErrors('email');
    $this->post(route('admin.login.submit'), ['email' => $this->admin->email, 'password' => 'new-password'])
        ->assertRedirect(route('admin.dashboard'));
});

test('invalid and expired admin tokens are rejected', function (string $case) {
    $token = Password::broker('admins')->createToken($this->admin);

    if ($case === 'invalid') {
        $token = 'invalid-token';
    } else {
        DB::table('admin_password_reset_tokens')
            ->where('email', $this->admin->email)
            ->update(['created_at' => now()->subMinutes(61)]);
    }

    $this->post(route('admin.password.update'), [
        'token' => $token,
        'email' => $this->admin->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('old-password', $this->admin->fresh()->password))->toBeTrue();
})->with(['invalid', 'expired']);

test('admin and frontend reset tokens remain isolated for a shared account', function () {
    $this->admin->assignRole('user');
    $frontendToken = Password::broker('users')->createToken($this->admin);
    $adminToken = '';
    Mail::fake();

    $this->post(route('admin.password.email'), ['email' => $this->admin->email])->assertSessionHas('status');

    Mail::assertSent(AdminPasswordResetLinkMail::class, function (AdminPasswordResetLinkMail $mail) use (&$adminToken): bool {
        preg_match('#/admin/reset-password/([^?]+)#', $mail->resetUrl, $matches);
        $adminToken = isset($matches[1]) ? urldecode($matches[1]) : '';

        return $adminToken !== '';
    });

    expect(Password::broker('users')->tokenExists($this->admin, $frontendToken))->toBeTrue()
        ->and(Password::broker('admins')->tokenExists($this->admin, $frontendToken))->toBeFalse()
        ->and(Password::broker('admins')->tokenExists($this->admin, $adminToken))->toBeTrue();
});

test('admin reset requests are rate limited', function () {
    Mail::fake();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('admin.password.email'), ['email' => $this->admin->email])->assertSessionHasNoErrors();
    }

    $this->post(route('admin.password.email'), ['email' => $this->admin->email])
        ->assertSessionHasErrors('email');
});
