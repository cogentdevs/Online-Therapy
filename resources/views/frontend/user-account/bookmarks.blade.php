@extends('layouts.frontLayout.front-design')

@section('title', 'My Bookmarks')
@section('meta_description', 'View your saved articles and issues.')

@section('content')
    <div class="front-account-page">
        <div class="container-fluid front-account-container">
            <nav class="front-taza-breadcrumb front-ui" aria-label="Breadcrumb">
                <a href="{{ route('frontend.home') }}"><i class="fa-solid fa-house" aria-hidden="true"></i> Home</a>
                <span aria-hidden="true">/</span><a href="{{ route('front.account') }}">My Account</a>
                <span aria-hidden="true">/</span><span aria-current="page">Bookmarks</span>
            </nav>

            <header class="front-account-heading">
                <h1>My Bookmarks</h1><span aria-hidden="true"></span>
                <p>Manage your saved articles and issues.</p>
            </header>

            <div class="row g-4 align-items-start front-account-layout">
                <aside class="col-12 col-lg-3">@include('frontend.user-account.partials.sidebar')</aside>
                <div class="col-12 col-lg-9">
                    <section class="front-account-card front-account-bookmarks" data-account-bookmarks>
                        <header class="front-account-bookmarks__header">
                            <div><h2>All Bookmarks</h2><p>Saved items are shown from newest to oldest.</p></div>
                        </header>

                        <div class="table-responsive user-subscription-history-table-wrap">
                            <table class="table user-subscription-history-table align-middle" data-front-account-datatable="bookmarks"
                                data-source-url="{{ route('front.account.bookmarks', ['datatable' => 1]) }}">
                                <thead><tr><th>Module</th><th>Title</th><th>Saved On</th><th>View</th><th>Remove</th></tr></thead>
                                <tbody>
                                    @foreach ($bookmarks as $bookmark)
                                        <tr>
                                            <td>{{ $bookmark->frontend_module_label }}</td>
                                            <td><span class="front-account-datatable-title" title="{{ $bookmark->frontend_title }}">{{ $bookmark->frontend_title }}</span></td>
                                            <td>{{ $bookmark->created_at?->format('d M Y') ?? '—' }}</td>
                                            <td>{{ $bookmark->frontend_url ? 'View' : 'Unavailable' }}</td>
                                            <td>Remove</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($bookmarks->isEmpty())<span class="visually-hidden">No bookmarks have been saved yet.</span>@endif
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
