@extends('layouts.frontLayout.front-design')

@section('title', 'Two-Factor Authentication')

@section('content')
    <div class="front-account-page">
        <div class="container-fluid front-account-container">
            <nav class="front-taza-breadcrumb front-ui" aria-label="Breadcrumb">
                <a href="{{ route('frontend.home') }}"><i class="fa-solid fa-house" aria-hidden="true"></i> Home</a>
                <span>/</span><a href="{{ route('front.account') }}">My Account</a><span>/</span>
                <span aria-current="page">Two-Factor Authentication</span>
            </nav>
            <header class="front-account-heading"><h1>Two-Factor Authentication</h1><span></span><p>Enable an extra verification step to keep your account secure.</p></header>
            <div class="row g-4 align-items-start front-account-layout">
                <aside class="col-12 col-lg-3">@include('frontend.user-account.partials.sidebar')</aside>
                <div class="col-12 col-lg-9">
                    <section class="front-account-card front-account-security-card">
                        <header>
                            <div><h2>Two-Factor Authentication</h2><p>Choose a verification method for sign-in security.</p></div>
                            <span class="front-account-status {{ $setting->is_enabled ? '' : 'is-off' }}">{{ $setting->is_enabled ? 'Active' : 'Inactive' }}</span>
                        </header>
                        @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
                        @if ($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

                        @if ($setting->is_enabled)
                            <div class="front-account-security-enabled"><i class="fa-solid fa-circle-check"></i><div><strong>Two-Factor Authentication is Active</strong><p>Current method: {{ $setting->method === 'phone' ? 'Phone' : 'Email' }}</p>@if ($setting->verified_at)<small>Verified: {{ $setting->verified_at->format('d-m-Y') }}</small>@endif</div></div>
                            <div class="front-account-two-factor-actions">
                                <section>
                                    <h3>Change Method</h3>
                                    <form method="post" action="{{ route('front.account.two-factor-authentication.change-method.send') }}" class="front-account-security-form">
                                        @csrf
                                        <label class="front-account-method-option {{ $setting->method === 'email' ? 'is-disabled' : '' }}">
                                            <input type="radio" name="method" value="email" @disabled($setting->method === 'email')>
                                            <i class="fa-regular fa-envelope"></i><span><strong>Email</strong><small>{{ $setting->method === 'email' ? 'Already active' : 'Select as the new method' }}</small></span>
                                        </label>
                                        <label class="front-account-method-option is-disabled">
                                            <input type="radio" name="method" value="phone" disabled>
                                            <i class="fa-solid fa-mobile-screen"></i><span><strong>Phone</strong><small>Coming soon</small></span>
                                        </label>
                                        <button class="front-auth-submit" type="submit">Change Method</button>
                                    </form>
                                </section>
                                <section>
                                    <h3>Disable Two-Factor Authentication</h3>
                                    <p>Confirm your current password and email code before disabling.</p>
                                    <form method="post" action="{{ route('front.account.two-factor-authentication.disable.send') }}" class="front-account-security-form">
                                        @csrf
                                        <div class="front-auth-field"><label for="disable-current-password">Current Password</label><div class="front-auth-control front-auth-control--password">
                                            <input id="disable-current-password" name="current_password" type="password" autocomplete="current-password" required>
                                            <i class="fa-solid fa-lock front-auth-field__icon"></i>
                                            <button class="front-auth-password-toggle" type="button" data-front-password-toggle aria-controls="disable-current-password" aria-pressed="false" aria-label="Show Password" data-show-label="Show Password" data-hide-label="Hide Password"><i class="fa-regular fa-eye"></i></button>
                                        </div></div>
                                        <button class="front-account-danger-action" type="submit">Disable Two-Factor Authentication</button>
                                    </form>
                                </section>
                            </div>
                        @else
                            <form method="post" action="{{ route('front.account.two-factor-authentication.send') }}" class="front-account-security-form">
                                @csrf
                                <label class="front-account-method-option">
                                    <input type="radio" name="method" value="email" checked>
                                    <i class="fa-regular fa-envelope"></i><span><strong>Email</strong><small>A verification code will be sent to your registered email.</small></span>
                                </label>
                                <label class="front-account-method-option is-disabled">
                                    <input type="radio" name="method" value="phone" disabled>
                                    <i class="fa-solid fa-mobile-screen"></i><span><strong>Phone</strong><small>Coming soon</small></span>
                                </label>
                                <button class="front-auth-submit" type="submit">Enable <i class="fa-solid fa-arrow-left"></i></button>
                            </form>
                        @endif
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
