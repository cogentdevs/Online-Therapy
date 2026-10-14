<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ForgotPasswordRequest;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Mail\AdminPasswordResetLinkMail;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AdminPasswordResetController extends Controller
{
    public function create(): View
    {
        return view('admin.forgot-password');
    }

    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();
        $request->hitRateLimiter();

        $email = Str::lower($request->validated('email'));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user?->canAccessAdmin()) {
            $token = Password::broker('admins')->createToken($user);
            $resetUrl = route('admin.password.reset', ['token' => $token, 'email' => $user->email]);
            $expiresInMinutes = (int) config('auth.passwords.admins.expire', 60);

            Mail::to($user)->send(new AdminPasswordResetLinkMail($user, $resetUrl, $expiresInMinutes));
        }

        return to_route('admin.password.request')->with(
            'status',
            'If the email belongs to an eligible admin account, a password reset link has been sent.'
        );
    }

    public function edit(Request $request, string $token): Response
    {
        $email = $request->query('email');
        $user = is_string($email)
            ? User::query()->whereRaw('LOWER(email) = ?', [Str::lower($email)])->first()
            : null;
        $isValid = $user?->canAccessAdmin() === true
            && Password::broker('admins')->tokenExists($user, $token);

        return response()->view('admin.reset-password', [
            'email' => is_string($email) ? $email : '',
            'token' => $token,
            'isValid' => $isValid,
        ])->header('Cache-Control', 'no-store, private')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function update(ResetPasswordRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password', 'password_confirmation', 'token']);
        $user = User::query()->whereRaw('LOWER(email) = ?', [Str::lower($credentials['email'])])->first();

        if ($user?->canAccessAdmin() !== true) {
            return back()->withErrors(['email' => 'This password reset link is invalid or has expired.']);
        }

        $credentials['email'] = $user->email;
        $status = Password::broker('admins')->reset(
            $credentials,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PasswordReset) {
            return back()->withErrors(['email' => 'This password reset link is invalid or has expired.']);
        }

        return to_route('admin.login')->with('status', 'Your password has been reset. You can now log in.');
    }
}
