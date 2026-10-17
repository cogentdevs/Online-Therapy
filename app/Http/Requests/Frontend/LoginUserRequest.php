<?php

namespace App\Http\Requests\Frontend;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
            'login_source' => ['sometimes', 'string', 'in:homepage'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
            'password.string' => 'Password must be valid text.',
            'remember.boolean' => 'The Remember Me selection is invalid.',
        ];
    }

    public function authenticate(): User
    {
        $key = $this->throttleKey();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many attempts. Please try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        RateLimiter::hit($key, 60);
        $provider = Auth::guard('web')->getProvider();
        $credentials = $this->safe()->only(['email', 'password']);
        $user = $provider->retrieveByCredentials($credentials);
        $hasValidCredentials = $user instanceof User && $provider->validateCredentials($user, $credentials);
        $isFrontendUser = $hasValidCredentials && $user->hasRole('user', 'web');
        $inactive = $isFrontendUser && ! $user->is_active;

        if (! $isFrontendUser || $inactive) {
            throw ValidationException::withMessages([
                'email' => $inactive
                    ? 'Your account has not been activated yet. Please use the activation link in your email.'
                    : 'Invalid email or password.',
            ]);
        }

        RateLimiter::clear($key);

        return $user;
    }

    public function throttleKey(): string
    {
        return 'front-login:'.hash('sha256', Str::lower($this->string('email')->trim()->toString()).'|'.$this->ip());
    }
}
