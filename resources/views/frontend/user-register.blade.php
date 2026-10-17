@extends('layouts.frontLayout.front-design')

@section('title', 'Registration')
@section('meta_description', 'Create a new Thrive user account')

@section('content')
    <div class="front-auth-page front-auth-page--register" dir="ltr">
        <div class="container-fluid front-auth-page__container">
            <nav class="front-taza-breadcrumb front-ui" aria-label="Breadcrumb">
                <a href="{{ route('frontend.home') }}"><i class="fa-solid fa-house" aria-hidden="true"></i> Home</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">Registration</span>
            </nav>

            <div class="front-auth-card">
                <aside class="front-auth-card__aside">
                    <div class="front-auth-brand">
                        @if ($generalSetting?->logo)
                            <img src="{{ asset($generalSetting->logo) }}" alt="{{ $generalSetting->app_name ?? 'Thrive' }}">
                        @else
                            <strong>Thrive</strong>
                        @endif
                    </div>
                    <h2>Register Today</h2>
                    <p>Create your account to access articles, subscriptions, and personalized features.</p>
                    <ul class="front-auth-benefits">
                        <li><span><i class="fa-regular fa-file-lines" aria-hidden="true"></i></span><div><strong>Access Articles</strong><small>Read quality, trusted articles.</small></div></li>
                        {{-- Temporarily hidden - feature retained for future use: Weekly Magazine. --}}
                        <li><span><i class="fa-regular fa-bell" aria-hidden="true"></i></span><div><strong>Latest Updates</strong><small>Stay informed about important news and articles.</small></div></li>
                    </ul>
                    <div class="front-auth-illustration" aria-hidden="true">
                        <i class="fa-regular fa-user"></i>
                        <span class="front-auth-illustration__line"></span>
                        <span class="front-auth-illustration__line front-auth-illustration__line--short"></span>
                    </div>
                </aside>

                <section class="front-auth-card__content">
                    <header class="front-auth-heading">
                        <h1>Registration</h1>
                        <span aria-hidden="true"></span>
                        <p>Create Your Account</p>
                    </header>

                    <form class="front-auth-form" method="post" action="{{ route('front.register.store') }}" data-front-register-form>
                        @csrf
                        @if (session('success'))
                            <div class="alert alert-success" role="status">{{ session('success') }}</div>
                        @endif
                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert">
                                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                            </div>
                        @endif
                        <div class="row g-3">
                            <div class="col-12 col-md-6 front-auth-field">
                                <label for="register-first-name">First Name <span aria-hidden="true">*</span></label>
                                <div class="front-auth-control">
                                    <input id="register-first-name" name="first_name" value="{{ old('first_name') }}" type="text" autocomplete="given-name" placeholder="Enter your first name" required>
                                    <i class="fa-regular fa-user front-auth-field__icon" aria-hidden="true"></i>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 front-auth-field">
                                <label for="register-last-name">Last Name <span aria-hidden="true">*</span></label>
                                <div class="front-auth-control">
                                    <input id="register-last-name" name="last_name" value="{{ old('last_name') }}" type="text" autocomplete="family-name" placeholder="Enter your last name" required>
                                    <i class="fa-regular fa-user front-auth-field__icon" aria-hidden="true"></i>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 front-auth-field">
                                <label for="register-email">Email Address <span aria-hidden="true">*</span></label>
                                <div class="front-auth-control">
                                    <input id="register-email" name="email" value="{{ old('email') }}" type="email" autocomplete="email" inputmode="email" placeholder="Enter your email address" required>
                                    <i class="fa-regular fa-envelope front-auth-field__icon" aria-hidden="true"></i>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 front-auth-field">
                                <label for="register-phone">Phone Number <span aria-hidden="true">*</span></label>
                                <div class="front-auth-control">
                                    <input id="register-phone" name="phone" value="{{ old('phone') }}" type="tel" autocomplete="tel" inputmode="tel" placeholder="Enter your phone number" required>
                                    <i class="fa-solid fa-phone front-auth-field__icon" aria-hidden="true"></i>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 front-auth-field">
                                <label for="register-password">Password <span aria-hidden="true">*</span></label>
                                <div class="front-auth-control front-auth-control--password">
                                    <input id="register-password" name="password" type="password" autocomplete="new-password" minlength="8" placeholder="Enter your password" required>
                                    <i class="fa-solid fa-lock front-auth-field__icon" aria-hidden="true"></i>
                                    <button class="front-auth-password-toggle" type="button" data-front-password-toggle aria-controls="register-password" aria-pressed="false" aria-label="Show password" data-show-label="Show password" data-hide-label="Hide password"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                                </div>
                                <small>Minimum 8 characters</small>
                            </div>
                            <div class="col-12 col-md-6 front-auth-field">
                                <label for="register-password-confirmation">Confirm Password <span aria-hidden="true">*</span></label>
                                <div class="front-auth-control front-auth-control--password">
                                    <input id="register-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" placeholder="Confirm your password" required>
                                    <i class="fa-solid fa-lock front-auth-field__icon" aria-hidden="true"></i>
                                    <button class="front-auth-password-toggle" type="button" data-front-password-toggle aria-controls="register-password-confirmation" aria-pressed="false" aria-label="Show password" data-show-label="Show password" data-hide-label="Hide password"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="front-register-captcha">
                            {!! app('captcha')->display(['data-callback' => 'frontRegisterCaptchaComplete', 'data-expired-callback' => 'frontRegisterCaptchaReset', 'data-error-callback' => 'frontRegisterCaptchaReset']) !!}
                        </div>

                        <button class="front-auth-submit" type="submit" disabled>Create Account <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
                        <p class="front-auth-switch">Already have an account? <a href="{{ route('front.login') }}">Log In</a></p>
                    </form>
                </section>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.frontRegisterCaptchaComplete = function () {
            window.frontRegisterCaptchaReady = true;
            window.dispatchEvent(new Event('front-register-captcha'));
        };
        window.frontRegisterCaptchaReset = function () {
            window.frontRegisterCaptchaReady = false;
            window.dispatchEvent(new Event('front-register-captcha'));
        };
    </script>
    {!! app('captcha')->renderJs('en') !!}
@endpush
