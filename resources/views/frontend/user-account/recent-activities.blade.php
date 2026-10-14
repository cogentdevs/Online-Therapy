@extends('layouts.frontLayout.front-design')

@section('title', 'Recent Activities')
@section('meta_description', 'View your recently viewed articles and issues.')

@section('content')
    <div class="front-account-page">
        <div class="container-fluid front-account-container">
            <nav class="front-taza-breadcrumb front-ui" aria-label="Breadcrumb">
                <a href="{{ route('frontend.home') }}"><i class="fa-solid fa-house" aria-hidden="true"></i> Home</a>
                <span aria-hidden="true">/</span><a href="{{ route('front.account') }}">My Account</a>
                <span aria-hidden="true">/</span><span aria-current="page">Recent Activities</span>
            </nav>

            <header class="front-account-heading">
                <h1>Recent Activities</h1><span aria-hidden="true"></span>
                <p>Your recently viewed articles and issues.</p>
            </header>

            <div class="row g-4 align-items-start front-account-layout">
                <aside class="col-12 col-lg-3">@include('frontend.user-account.partials.sidebar')</aside>
                <div class="col-12 col-lg-9">
                    <section class="front-account-card front-account-activities">
                        <header class="front-account-activities__header">
                            <div><h2>All Recent Activities</h2><p>Activities are shown from newest to oldest.</p></div>
                        </header>

                        <div class="table-responsive user-subscription-history-table-wrap">
                            <table class="table user-subscription-history-table align-middle" data-front-account-datatable="activities"
                                data-source-url="{{ route('front.account.recent-activities', ['datatable' => 1]) }}">
                                <thead><tr><th>Module</th><th>Title</th><th>Last Viewed</th><th>View</th></tr></thead>
                                <tbody>
                                    @foreach ($activities as $activity)
                                        <tr>
                                            <td>{{ $activity->frontend_module_label }}</td>
                                            <td><span class="front-account-datatable-title" title="{{ $activity->frontend_title }}">{{ $activity->frontend_title }}</span></td>
                                            <td><span class="front-account-activity-time" dir="ltr"><span>{{ $activity->last_visited_at?->format('d M Y, h:i A') ?? '—' }}</span><span>PKT (UTC+5)</span></span></td>
                                            <td>{{ $activity->frontend_url ? 'View' : 'Unavailable' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($activities->isEmpty())<span class="visually-hidden">No recent activity is available.</span>@endif
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
