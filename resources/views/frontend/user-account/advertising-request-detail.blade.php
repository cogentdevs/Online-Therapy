@extends('layouts.frontLayout.front-design')

@section('title', 'Advertising Request '.$adRequest->request_no)
@section('meta_description', 'View your advertising request details and placements.')

@section('content')
    <div class="front-account-page">
        <div class="container-fluid front-account-container">
            <nav class="front-taza-breadcrumb front-ui" aria-label="Breadcrumb">
                <a href="{{ route('frontend.home') }}"><i class="fa-solid fa-house" aria-hidden="true"></i> Home</a>
                <span aria-hidden="true">/</span><a href="{{ route('front.account') }}">My Account</a>
                <span aria-hidden="true">/</span><a href="{{ route('front.account.advertising-requests.index') }}">My Advertising Requests</a>
                <span aria-hidden="true">/</span><span aria-current="page" dir="ltr">{{ $adRequest->request_no }}</span>
            </nav>

            <header class="front-account-heading">
                <h1>Advertising Request Details</h1><span aria-hidden="true"></span>
                <p dir="ltr">{{ $adRequest->request_no }}</p>
            </header>

            <div class="row g-4 align-items-start front-account-layout">
                <aside class="col-12 col-lg-3">@include('frontend.user-account.partials.sidebar')</aside>
                <div class="col-12 col-lg-9">
                    <section class="front-account-card front-ad-request-card">
                        <header class="user-subscriptions-section-header">
                            <div><h2>Request Information</h2><p>Submitted details and current status.</p></div>
                            <span class="front-ad-request-badge">{{ $requestStatusLabels[$adRequest->status] ?? $adRequest->status }}</span>
                        </header>
                        <dl class="front-ad-request-detail-grid">
                            <div><dt>Request No.</dt><dd dir="ltr">{{ $adRequest->request_no }}</dd></div>
                            <div><dt>Request Date</dt><dd>{{ $adRequest->created_at?->format('d M Y, h:i A') }}</dd></div>
                            <div><dt>Name</dt><dd>{{ $adRequest->name }}</dd></div>
                            <div><dt>Email</dt><dd dir="ltr">{{ $adRequest->email }}</dd></div>
                            <div><dt>Phone Number</dt><dd dir="ltr">{{ $adRequest->phone }}</dd></div>
                            @if (filled($adRequest->company))<div><dt>Company / Brand</dt><dd>{{ $adRequest->company }}</dd></div>@endif
                            <div><dt>Start Date</dt><dd>{{ $adRequest->from_date->format('d M Y') }}</dd></div>
                            <div><dt>End Date</dt><dd>{{ $adRequest->to_date->format('d M Y') }}</dd></div>
                            <div><dt>Total Duration</dt><dd>{{ $adRequest->durationDays() }} days</dd></div>
                            @if (filled($adRequest->details))<div class="front-ad-request-detail-grid__full"><dt>Additional Details</dt><dd class="front-ad-request-message">{{ $adRequest->details }}</dd></div>@endif
                        </dl>
                    </section>

                    <section class="front-account-card front-ad-request-card front-ad-request-placements">
                        <header class="user-subscriptions-section-header"><div><h2>Advertising Placements</h2><p>The status of each placement is shown separately.</p></div></header>
                        @foreach ($adRequest->placements as $placement)
                            <article class="front-ad-request-placement">
                                <div class="front-ad-request-item__heading">
                                    <h3>{{ $placement->displayLabel() }}</h3>
                                    <span class="front-ad-request-badge">{{ $placementStatusLabels[$placement->status] ?? $placement->status }}</span>
                                </div>

                                @if ($placement->ad)
                                    <div class="front-ad-request-linked-ad">
                                        <h4>Published Advertisement</h4>
                                        <dl class="front-ad-request-detail-grid">
                                            <div><dt>Title</dt><dd>{{ $placement->ad->title }}</dd></div>
                                            <div><dt>Ad Status</dt><dd>{{ $adDetails[$placement->id]['status'] }}</dd></div>
                                            <div><dt>Start Date</dt><dd>{{ $placement->ad->start_date?->format('d M Y') ?? 'Not set' }}</dd></div>
                                            <div><dt>End Date</dt><dd>{{ $placement->ad->expiry_date?->format('d M Y') ?? 'Not set' }}</dd></div>
                                            <div class="front-ad-request-detail-grid__full"><dt>Clicks</dt><dd>
                                                @if ($adDetails[$placement->id]['click_count_available'])
                                                    Total clicks: {{ $placement->ad->click_count }}
                                                @else
                                                    Internal click tracking is not available for this ad.
                                                @endif
                                            </dd></div>
                                        </dl>
                                    </div>
                                @elseif ($placement->status === 'confirmed')
                                    <p class="front-ad-request-note">This placement is confirmed, but the advertisement has not been published yet.</p>
                                @endif
                            </article>
                        @endforeach
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
