@extends('layouts.frontLayout.front-design')

@section('title', 'Checkout')
@section('meta_description', 'Selected subscription details and payment method')

@section('content')
    @php
        $currentPrice = $subscriptionProduct->frontend_has_discount
            ? $subscriptionProduct->frontend_discounted_price
            : $subscriptionProduct->frontend_price;
    @endphp

    <main class="subscription-checkout-page" data-subscription-checkout dir="ltr">
        <div class="container-fluid subscription-checkout-page__container">
            <nav class="front-taza-breadcrumb front-ui" aria-label="Breadcrumb">
                <a href="{{ route('frontend.home') }}"><i class="fa-solid fa-house" aria-hidden="true"></i> Home</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('front.subscriptions') }}">Subscriptions</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">Checkout</span>
            </nav>

            <header class="subscription-checkout-page__heading">
                <h1>Checkout</h1>
                <span aria-hidden="true"></span>
                <p>Review your subscription details and complete your payment.</p>
            </header>

            @if (session('success'))
                <div class="alert alert-success subscription-checkout-page__alert" role="alert">{{ session('success') }}</div>
            @endif
            @if ($errors->has('subscription'))
                <div class="alert alert-danger subscription-checkout-page__alert" role="alert">{{ $errors->first('subscription') }}</div>
            @endif
            @isset($paymentResubmission)
                <div class="alert alert-warning subscription-checkout-page__alert" role="alert">
                    <strong>Previous payment was rejected.</strong>
                    <span dir="auto">{{ $paymentResubmission->rejection_reason }}</span>
                    Upload corrected payment evidence below. Your original plan, amount, duration and modules will remain unchanged.
                </div>
            @endisset

            <div class="row g-4 align-items-start subscription-checkout-page__content">
                <div class="col-12 col-lg-5">
                    <article class="subscription-checkout-product">
                        <span class="subscription-checkout-product__kind">{{ $subscriptionProduct->subscription_checkout_kind }}</span>
                        <div class="subscription-checkout-product__icon">
                            <i class="fa-solid {{ $subscriptionProduct->product_for === \App\Models\SubscriptionProduct::FOR_MEMBERSHIP ? 'fa-crown' : 'fa-calendar-check' }}" aria-hidden="true"></i>
                        </div>
                        <h2 dir="auto">{{ $subscriptionProduct->subscription_checkout_name }}</h2>

                        @if ($subscriptionProduct->frontend_has_discount)
                            <div class="subscription-checkout-product__discount">
                                <del dir="ltr">{{ $subscriptionProduct->frontend_currency }} {{ $subscriptionProduct->frontend_price }}</del>
                                <span>{{ $subscriptionProduct->subscription_checkout_discount_label }}</span>
                            </div>
                        @endif

                        <p class="subscription-checkout-product__price" dir="ltr">
                            {{ $subscriptionProduct->frontend_currency }} {{ $currentPrice }}
                        </p>
                        <p class="subscription-checkout-product__duration">Duration: {{ $subscriptionProduct->subscription_checkout_duration }}</p>

                        <div class="subscription-checkout-product__modules">
                            <h3>Included Features</h3>
                            @forelse ($subscriptionProduct->subscriptionTypes as $subscriptionType)
                                <div class="subscription-checkout-product__module">
                                    <i class="fa-solid {{ $subscriptionType->frontend_membership_icon }}" aria-hidden="true"></i>
                                    <span dir="auto">{{ $subscriptionType->name }}</span>
                                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                </div>
                            @empty
                                <p class="subscription-checkout-product__empty">No included features are available for this subscription.</p>
                            @endforelse
                        </div>
                    </article>
                </div>

                <div class="col-12 col-lg-7">
                    <section class="subscription-checkout-summary h-100" aria-labelledby="checkout-summary-heading">
                        <h2 id="checkout-summary-heading">Order Summary</h2>

                        <dl class="subscription-checkout-summary__details">
                            <div><dt>Subscription</dt><dd dir="auto">{{ $subscriptionProduct->subscription_checkout_name }}</dd></div>
                            <div><dt>Price</dt><dd dir="ltr">{{ $subscriptionProduct->frontend_currency }} {{ $currentPrice }}</dd></div>
                            <div><dt>Subtotal</dt><dd dir="ltr">{{ $subscriptionProduct->frontend_currency }} {{ $currentPrice }}</dd></div>
                            <div><dt>Discount</dt><dd dir="ltr">{{ $subscriptionProduct->frontend_currency }} 0</dd></div>
                            <div class="subscription-checkout-summary__total"><dt>Total Amount</dt><dd dir="ltr">{{ $subscriptionProduct->frontend_currency }} {{ $currentPrice }}</dd></div>
                        </dl>

                        <div class="subscription-coupon">
                            <h3>Coupon Code</h3>
                            {{-- Coupon functionality will be implemented in a later checkout step. --}}
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Enter coupon code" aria-label="Coupon Code">
                                <button class="btn btn-outline-primary" type="button" disabled>Apply Coupon</button>
                            </div>
                        </div>

                        <form method="POST" action="{{ isset($paymentResubmission) ? route('front.account.subscriptions.resubmit.store', $paymentResubmission) : route('front.subscriptions.checkout.store', $subscriptionProduct) }}" enctype="multipart/form-data">
                            @csrf
                            <fieldset class="subscription-payment-methods" @disabled($paymentAccounts->isEmpty())>
                                <legend>Select a Payment Account</legend>
                                @forelse ($paymentAccounts as $paymentAccount)
                                    <label class="subscription-payment-option">
                                        <input type="radio" name="payment_account_id" value="{{ $paymentAccount->id }}" @checked((string) old('payment_account_id') === (string) $paymentAccount->id)>
                                        <span class="subscription-payment-option__mark" aria-hidden="true"></span>
                                        <i class="fa-solid fa-building-columns" aria-hidden="true"></i>
                                        <span class="subscription-payment-option__details">
                                            <strong><span>Bank Name: </span><span dir="auto">{{ $paymentAccount->bank_name }}</span></strong>
                                            <small><span>Account Title: </span><span dir="auto">{{ $paymentAccount->account_title }}</span></small>
                                            <small dir="ltr">Account Number: {{ $paymentAccount->account_no }}</small>
                                            @if ($paymentAccount->iban)
                                                <small dir="ltr">IBAN: {{ $paymentAccount->iban }}</small>
                                            @endif
                                            @if ($paymentAccount->branch_code)
                                                <small dir="ltr">Branch Code: {{ $paymentAccount->branch_code }}</small>
                                            @endif
                                        </span>
                                    </label>
                                @empty
                                    <div class="alert alert-warning mb-0" role="alert">No payment accounts are currently available.</div>
                                @endforelse
                            </fieldset>

                            @error('payment_account_id')
                                <p class="subscription-checkout-summary__error">{{ $message }}</p>
                            @enderror

                            <div class="subscription-payment-proof">
                                <div>
                                    <label class="form-label" for="transaction_id">Transaction / Reference Number</label>
                                    <input class="form-control @error('transaction_id') is-invalid @enderror" id="transaction_id" name="transaction_id" type="text" maxlength="100" value="{{ old('transaction_id') }}" dir="ltr" autocomplete="off" placeholder="Enter your transaction reference number">
                                    @error('transaction_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="form-label" for="payment_slip">Upload Payment Slip <span class="text-danger">*</span></label>
                                    <input class="form-control @error('payment_slip') is-invalid @enderror" id="payment_slip" name="payment_slip" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" required>
                                    <div class="form-text">Allowed formats: JPG, JPEG, PNG, WEBP and PDF. Maximum size: 5 MB.</div>
                                    @error('payment_slip')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <button type="submit" class="subscription-checkout-summary__action" data-checkout-action disabled @disabled($paymentAccounts->isEmpty())>
                                {{ isset($paymentResubmission) ? 'Resubmit Payment' : 'Submit Subscription' }}
                                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </button>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </main>
@endsection