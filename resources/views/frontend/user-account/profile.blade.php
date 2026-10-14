@extends('layouts.frontLayout.front-design')

@section('title', 'Profile')
@section('meta_description', 'Update your Thrive profile information.')

@section('content')
    <div class="front-account-page">
        <div class="container-fluid front-account-container">
            <nav class="front-taza-breadcrumb front-ui" aria-label="Breadcrumb">
                <a href="{{ route('frontend.home') }}"><i class="fa-solid fa-house" aria-hidden="true"></i> Home</a>
                <span aria-hidden="true">/</span><a href="{{ route('front.account') }}">My Account</a>
                <span aria-hidden="true">/</span><span aria-current="page">Profile</span>
            </nav>

            <header class="front-account-heading">
                <h1>Profile</h1><span aria-hidden="true"></span>
                <p>Update your name, email, and profile image here.</p>
            </header>

            <div class="row g-4 align-items-start front-account-layout">
                <aside class="col-12 col-lg-3">@include('frontend.user-account.partials.sidebar')</aside>
                <div class="col-12 col-lg-9">
                    <section class="front-account-card front-account-profile-card">
                        <header><h2>Profile</h2><p>Your latest information will appear in your account.</p></header>

                        @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                        @endif

                        <form class="front-account-profile-form" method="post" enctype="multipart/form-data"
                            action="{{ route('front.account.profile.update') }}" data-front-profile-form>
                            @csrf
                            @method('PATCH')
                            <div class="front-account-profile-image">
                                <img src="{{ $user->frontendProfileImageUrl() }}" alt="{{ $user->name }}"
                                    data-front-profile-preview data-default-src="{{ asset('images/frontend-images/users/user-avatar.png') }}">
                                <div>
                                    <label class="front-account-image-picker" for="profile-image">Choose Image</label>
                                    <input id="profile-image" name="profile_image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-front-profile-input>
                                    @if ($user->hasCustomFrontendProfileImage())
                                        <button class="front-account-image-remove" type="button" data-front-profile-remove>Remove Image</button>
                                    @endif
                                    <input name="remove_profile_image" type="hidden" value="0" data-front-profile-remove-value>
                                    <small>JPG, JPEG, PNG, or WEBP up to 2 MB.</small>
                                </div>
                            </div>

                            <div class="front-auth-field"><label for="profile-name">Name</label><div class="front-auth-control">
                                <input id="profile-name" name="name" type="text" value="{{ old('name', $user->name) }}" maxlength="255" required>
                                <i class="fa-regular fa-user front-auth-field__icon" aria-hidden="true"></i>
                            </div></div>
                            <div class="front-auth-field"><label for="profile-email">Email</label><div class="front-auth-control">
                                <input id="profile-email" name="email" type="email" value="{{ old('email', $user->email) }}" autocomplete="email" dir="ltr" required>
                                <i class="fa-regular fa-envelope front-auth-field__icon" aria-hidden="true"></i>
                            </div></div>
                            <div class="front-auth-field"><label for="profile-phone">Phone Number</label><div class="front-auth-control">
                                <input id="profile-phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" maxlength="255" autocomplete="tel" dir="ltr" required>
                                <i class="fa-solid fa-phone front-auth-field__icon" aria-hidden="true"></i>
                            </div></div>
                            <button class="front-auth-submit" type="submit">Update Profile <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
                        </form>
                    </section>

                </div>
            </div>
        </div>
    </div>
@endsection
