<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Forgot Password | {{ $generalSetting?->app_name ?? 'Digital Magazine' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/backend-images/logo/Thrive-with-Naila-Wellness-Emblem-removebg-preview.png') }}?v=20261014">

    @vite(['resources/sass/admin/login.scss', 'resources/js/admin/login.js'])
</head>

<body>
    <main class="admin-login-page">
        <div class="admin-login-decoration admin-login-decoration-top" aria-hidden="true"></div>
        <div class="admin-login-decoration admin-login-decoration-bottom" aria-hidden="true"></div>

        <section class="admin-login-card" aria-labelledby="admin-forgot-password-heading">
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
                <h1 id="admin-forgot-password-heading">Forgot Password?</h1>
                <p>Enter your email and we will send you a secure password-reset link.</p>
            </header>

            @if (session('status'))
                <div class="alert alert-success" role="status">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('admin.password.email') }}" novalidate>
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group has-validation">
                        <span class="input-group-text" aria-hidden="true">
                            <svg viewBox="0 0 24 24" role="img">
                                <path d="M4 6.5h16v11H4zM4.5 7l7.5 6 7.5-6" />
                            </svg>
                        </span>
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                            name="email" value="{{ old('email') }}" placeholder="Enter your email address"
                            autocomplete="email" autofocus required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary admin-login-submit">
                    <span>Send Reset Link</span>
                    <span aria-hidden="true">&rarr;</span>
                </button>
            </form>

            <footer class="admin-login-footer">
                <a href="{{ route('admin.login') }}">Back to Login</a>
            </footer>
        </section>
    </main>
</body>

</html>
