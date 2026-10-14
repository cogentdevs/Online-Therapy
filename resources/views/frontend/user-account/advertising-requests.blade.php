@extends('layouts.frontLayout.front-design')

@section('title', 'My Advertising Requests')
@section('meta_description', 'View your advertising requests and their current status.')

@section('content')
    <div class="front-account-page">
        <div class="container-fluid front-account-container">
            <nav class="front-taza-breadcrumb front-ui" aria-label="Breadcrumb">
                <a href="{{ route('frontend.home') }}"><i class="fa-solid fa-house" aria-hidden="true"></i> Home</a>
                <span aria-hidden="true">/</span><a href="{{ route('front.account') }}">My Account</a>
                <span aria-hidden="true">/</span><span aria-current="page">My Advertising Requests</span>
            </nav>

            <header class="front-account-heading">
                <h1>My Advertising Requests</h1><span aria-hidden="true"></span>
                <p>View the current status and details of your requests.</p>
            </header>

            <div class="row g-4 align-items-start front-account-layout">
                <aside class="col-12 col-lg-3">@include('frontend.user-account.partials.sidebar')</aside>
                <div class="col-12 col-lg-9">
                    <section class="front-account-card front-ad-request-card">
                        <header class="user-subscriptions-section-header">
                            <div><h2>All Requests</h2><p>The newest request is shown first.</p></div>
                        </header>

                        @if ($adRequests->isNotEmpty())
                            <div class="table-responsive front-account-listing-table-wrap">
                                <table
                                    class="table align-middle front-account-listing-table"
                                    data-front-account-listing-datatable="advertising-requests"
                                    data-current-page="{{ $adRequests->currentPage() }}"
                                    data-total-pages="{{ $adRequests->lastPage() }}"
                                >
                                    <thead>
                                        <tr>
                                            <th>Request No.</th>
                                            <th>Status</th>
                                            <th>Advertising Period</th>
                                            <th>Request Date</th>
                                            <th>Total Duration</th>
                                            <th>Placements</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($adRequests as $adRequest)
                                            <tr>
                                                <td><strong dir="ltr">{{ $adRequest->request_no }}</strong></td>
                                                <td><span class="front-ad-request-badge">{{ $requestStatusLabels[$adRequest->status] ?? $adRequest->status }}</span></td>
                                                <td data-order="{{ $adRequest->from_date->format('Y-m-d') }}">
                                                    <span dir="ltr">{{ $adRequest->from_date->format('d M Y') }} — {{ $adRequest->to_date->format('d M Y') }}</span>
                                                </td>
                                                <td data-order="{{ $adRequest->created_at?->timestamp }}">
                                                    <time datetime="{{ $adRequest->created_at?->toDateString() }}" dir="ltr">{{ $adRequest->created_at?->format('d M Y') }}</time>
                                                </td>
                                                <td data-order="{{ $adRequest->durationDays() }}">{{ $adRequest->durationDays() }} days</td>
                                                <td data-order="{{ $adRequest->placements_count }}">{{ $adRequest->placements_count }}</td>
                                                <td>
                                                    <a class="front-account-outline-action" href="{{ route('front.account.advertising-requests.show', $adRequest) }}">
                                                        View Details <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="front-account-empty-state">
                                <i class="fa-solid fa-bullhorn" aria-hidden="true"></i>
                                <p>You have not submitted any advertising requests yet.</p>
                                <a class="front-account-outline-action" href="{{ route('front.advertise') }}">Request Advertising</a>
                            </div>
                        @endif

                        @if ($adRequests->hasPages())
                            <div class="user-subscriptions-pagination">{{ $adRequests->links() }}</div>
                        @endif
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
