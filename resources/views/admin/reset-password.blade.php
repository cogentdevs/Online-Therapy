<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Reset Password | {{ $generalSetting?->app_name ?? 'Digital Magazine' }}</title>
    <x-site-favicon :settings="$generalSetting ?? null" />

    @vite(['resources/sass/admin/login.scss', 'resources/js/admin/login.js'])
</head>

<body>
    <main class="admin-login-page">
        <div class="admin-login-decoration admin-login-decoration-top" aria-hidden="true"></div>
        <div class="admin-login-decoration admin-login-decoration-bottom" aria-hidden="true"></div>

        <section class="admin-login-card" aria-labelledby="admin-reset-password-heading">
            <div class="admin-brand" aria-label="{{ $generalSetting?->app_name ?? 'Digital Magazine' }}">
                @if (!empty($generalSetting?->logo))
                    <img class="admin-brand-logo" src="{{ asset($generalSetting->logo) }}"
                        alt="{{ $generalSetting?->app_name ?? 'Digital Magazine' }} logo">
                @else
                    <span class="admin-brand-mark" aria-hidden="true">DM</span>
                @endif
                <span class="admin-brand-name">{{ $generalSetting?->app_name ?? 'Digital Magazine' }}</span>
                <span class="admin-brand-label">Admin Portal</span>
            </div>

            <header class="admin-login-header">
                <h1 id="admin-reset-password-heading">Set a New Password</h1>
                <p>Choose a new password with at least 8 characters.</p>
            </header>

            @if (! $isValid)
                <div class="alert alert-danger" role="alert">This password-reset link is invalid or has expired.</div>
                <a class="btn btn-primary admin-login-submit" href="{{ route('admin.password.request') }}">
                    <span>Request a New Reset Link</span>
                    <span aria-hidden="true">&rarr;</span>
                </a>
            @else
                <form method="POST" action="{{ route('admin.password.update') }}" novalidate>
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text" aria-hidden="true">
                                <svg viewBox="0 0 24 24" role="img">
                                    <path d="M4 6.5h16v11H4zM4.5 7l7.5 6 7.5-6" />
                                </svg>
                            </span>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                name="email" value="{{ old('email', $email) }}" autocomplete="email" readonly required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    @foreach ([['password', 'New Password', 'new-password'], ['password_confirmation', 'Confirm Password', 'new-password']] as [$name, $label, $autocomplete])
                        <div class="mb-3">
                            <label for="{{ $name }}" class="form-label">{{ $label }}</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" role="img">
                                        <path d="M7 10V8a5 5 0 0 1 10 0v2M5 10h14v10H5z" />
                                    </svg>
                                </span>
                                <input id="{{ $name }}" type="password"
                                    class="form-control @error($name) is-invalid @enderror" name="{{ $name }}"
                                    autocomplete="{{ $autocomplete }}" required>
                                <button class="btn password-toggle" type="button" data-password-toggle
                                    aria-label="Show password" aria-pressed="false">
                                    <svg class="password-eye" viewBox="0 0 24 24" role="img">
                                        <path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5z" />
                                        <circle cx="12" cy="12" r="2.5" />
                                    </svg>
                                </button>
                                @error($name)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @endforeach

                    <button type="submit" class="btn btn-primary admin-login-submit">
                        <span>Reset Password</span>
                        <span aria-hidden="true">&rarr;</span>
                    </button>
                </form>
            @endif

            <footer class="admin-login-footer">
                <a href="{{ route('admin.login') }}">Back to Login</a>
            </footer>
        </section>
    </main>
</body>

</html>
