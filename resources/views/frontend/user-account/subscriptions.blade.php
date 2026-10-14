@extends('layouts.frontLayout.front-design')

@section('title', 'My Subscriptions')
@section('meta_description', 'View your active and previous Thrive subscriptions.')

@section('content')
    <div class="front-account-page">
        <div class="container-fluid front-account-container">
            <nav class="front-taza-breadcrumb front-ui" aria-label="Breadcrumb">
                <a href="{{ route('frontend.home') }}"><i class="fa-solid fa-house" aria-hidden="true"></i> Home</a>
                <span aria-hidden="true">/</span><a href="{{ route('front.account') }}">My Account</a>
                <span aria-hidden="true">/</span><span aria-current="page">My Subscriptions</span>
            </nav>

            <header class="front-account-heading">
                <h1>My Subscriptions</h1><span aria-hidden="true"></span>
                <p>View details for all your current and previous subscriptions.</p>
            </header>

            @if (session('success'))
                <div class="alert alert-success" role="status">{{ session('success') }}</div>
            @endif

            <div class="row g-4 align-items-start front-account-layout">
                <aside class="col-12 col-lg-3">@include('frontend.user-account.partials.sidebar')</aside>
                <div class="col-12 col-lg-9">
                    <section class="front-account-card user-subscription-history">
                        <header class="user-subscriptions-section-header">
                            <div><h2>Subscription History</h2><p>All purchases are shown from newest to oldest.</p></div>
                        </header>

                        <div class="table-responsive user-subscription-history-table-wrap">
                            <table class="table user-subscription-history-table align-middle" data-front-account-datatable="subscriptions"
                                data-source-url="{{ route('front.account.subscriptions', ['datatable' => 1]) }}">
                                <thead><tr><th>Subscription</th><th>Modules</th><th>Amount</th><th>Dates</th><th>Payment</th><th>Status</th><th>Documents / Actions</th></tr></thead>
                                <tbody>
                                    @foreach ($subscriptions as $subscription)
                                        <tr>
                                            <td>{{ $subscription->product_name ?: 'Subscription' }} {{ $subscription->frontend_product_type }}</td>
                                            <td>{{ $subscription->subscriptionTypes->pluck('frontend_label')->join('، ') ?: 'No modules listed' }}</td>
                                            <td>{{ $subscription->currency?->code ?? $subscription->currency?->symbol }} {{ $subscription->frontend_amount }}</td>
                                            <td>
                                                <small class="d-block">Submitted: {{ $subscription->payment_submitted_at?->format('d M Y, h:i A') ?? '—' }}</small>
                                                <small class="d-block">Start: {{ $subscription->start_date?->format('d M Y') ?? '—' }}</small>
                                                <small class="d-block">End: {{ $subscription->end_date?->format('d M Y') ?? '—' }}</small>
                                                @if ($subscription->reviewed_at)
                                                    <small class="d-block">Reviewed: {{ $subscription->reviewed_at->format('d M Y, h:i A') }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="d-block">{{ $subscription->frontend_payment_method }}</span>
                                                <small class="d-block">{{ $subscription->frontend_payment_status_label }}</small>
                                                @if (filled($subscription->transaction_id))
                                                    <small class="d-block text-break" dir="ltr">Ref: {{ $subscription->transaction_id }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $subscription->frontend_status_label }}
                                                @if ($subscription->payment_status === \App\Models\UserSubscription::PAYMENT_STATUS_REJECTED && filled($subscription->rejection_reason))
                                                    <small class="d-block text-danger">{{ $subscription->rejection_reason }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($subscription->hasFinalInvoice())
                                                    <small class="d-block" dir="ltr">Invoice: {{ $subscription->invoice_no }}</small>
                                                    <small class="d-block mb-1" dir="ltr">Order: {{ $subscription->order_no }}</small>
                                                    <div class="d-inline-flex flex-wrap gap-1" dir="ltr">
                                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('front.account.subscriptions.invoice.view', $subscription) }}" target="_blank" rel="noopener" title="View Invoice"><i class="fa-regular fa-eye" aria-hidden="true"></i><span class="visually-hidden">View Invoice</span></a>
                                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('front.account.subscriptions.invoice.download', $subscription) }}" title="Download PDF"><i class="fa-solid fa-download" aria-hidden="true"></i><span class="visually-hidden">Download PDF</span></a>
                                                    </div>
                                                @else
                                                    <span aria-label="Invoice pending">—</span>
                                                @endif
                                                @if ($subscription->payment_status === \App\Models\UserSubscription::PAYMENT_STATUS_REJECTED && $subscription->status === \App\Models\UserSubscription::STATUS_REJECTED)
                                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('front.account.subscriptions.resubmit', $subscription) }}">Resubmit Payment</a>
                                                @endif
                                                @if ($subscription->product_for === \App\Models\SubscriptionProduct::FOR_PLAN && $subscription->frontend_effective_status === \App\Models\UserSubscription::STATUS_ACTIVE)
                                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('front.account.subscriptions.videos', $subscription) }}">View Videos</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($subscriptions->isEmpty())<span class="visually-hidden">No subscription records are available yet.</span>@endif
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
