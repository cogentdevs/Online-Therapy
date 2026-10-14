@extends('layouts.frontLayout.front-design')

@section('title', 'Change Password')
@section('meta_description', 'Change your Thrive account password.')

@section('content')
    <div class="front-account-page">
        <div class="container-fluid front-account-container">
            <nav class="front-taza-breadcrumb front-ui" aria-label="Breadcrumb">
                <a href="{{ route('frontend.home') }}"><i class="fa-solid fa-house" aria-hidden="true"></i> Home</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('front.account') }}">My Account</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">Change Password</span>
            </nav>

            <header class="front-account-heading">
                <h1>Change Password</h1>
                <span aria-hidden="true"></span>
                <p>Choose a strong, unique password to protect your account.</p>
            </header>

            <div class="row g-4 align-items-start front-account-layout">
                <aside class="col-12 col-lg-3">
                    @include('frontend.user-account.partials.sidebar')
                </aside>

                <div class="col-12 col-lg-9">
                    <section class="front-account-card front-account-password-card">
                        <header>
                            <h2>Change Password</h2>
                            <p>After confirming your current password, enter a new password with at least 8 characters.</p>
                        </header>

                        @if (session('success'))
                            <div class="alert alert-success" role="status">{{ session('success') }}</div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form class="front-auth-form front-account-password-form" method="post"
                            action="{{ route('front.account.change-password.update') }}" data-front-change-password-form>
                            @csrf
                            @foreach ([
                                ['current_password', 'Current Password', 'current-password'],
                                ['password', 'New Password', 'new-password'],
                                ['password_confirmation', 'Confirm New Password', 'new-password'],
                            ] as [$name, $label, $autocomplete])
                                <div class="front-auth-field">
                                    <label for="account-{{ $name }}">{{ $label }}</label>
                                    <div class="front-auth-control front-auth-control--password">
                                        <input id="account-{{ $name }}" name="{{ $name }}" type="password"
                                            autocomplete="{{ $autocomplete }}" @if ($name !== 'current_password') minlength="8" @endif required>
                                        <i class="fa-solid fa-lock front-auth-field__icon" aria-hidden="true"></i>
                                        <button class="front-auth-password-toggle" type="button" data-front-password-toggle
                                            aria-controls="account-{{ $name }}" aria-pressed="false"
                                            aria-label="Show Password" data-show-label="Show Password"
                                            data-hide-label="Hide Password">
                                            <i class="fa-regular fa-eye" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach

                            <button class="front-auth-submit" type="submit" disabled>
                                Change Password <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                            </button>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
