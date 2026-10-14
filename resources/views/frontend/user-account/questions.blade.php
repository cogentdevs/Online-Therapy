@extends('layouts.frontLayout.front-design')

@section('title', 'My Questions')
@section('meta_description', 'View your submitted questions and their current status.')

@section('content')
    <div class="front-account-page">
        <div class="container-fluid front-account-container">
            <nav class="front-taza-breadcrumb front-ui" aria-label="Breadcrumb">
                <a href="{{ route('frontend.home') }}"><i class="fa-solid fa-house" aria-hidden="true"></i> Home</a>
                <span aria-hidden="true">/</span><a href="{{ route('front.account') }}">My Account</a>
                <span aria-hidden="true">/</span><span aria-current="page">My Questions</span>
            </nav>

            <header class="front-account-heading">
                <h1>My Questions</h1><span aria-hidden="true"></span>
                <p>View your questions, their current status, and any responses.</p>
            </header>

            <div class="row g-4 align-items-start front-account-layout">
                <aside class="col-12 col-lg-3">@include('frontend.user-account.partials.sidebar')</aside>
                <div class="col-12 col-lg-9">
                    <section class="front-account-card front-ad-request-card">
                        <header class="user-subscriptions-section-header">
                            <div><h2>All Questions</h2><p>The newest question is shown first.</p></div>
                            <a class="front-account-outline-action" href="{{ route('front.ask-question') }}">Ask a New Question</a>
                        </header>

                        @if ($questions->isNotEmpty())
                            <div class="table-responsive front-account-listing-table-wrap">
                                <table
                                    class="table align-middle front-account-listing-table"
                                    data-front-account-listing-datatable="questions"
                                    data-current-page="{{ $questions->currentPage() }}"
                                    data-total-pages="{{ $questions->lastPage() }}"
                                >
                                    <thead>
                                        <tr>
                                            <th>Question No.</th>
                                            <th>Subject</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($questions as $question)
                                            <tr>
                                                <td><strong dir="ltr">{{ $question->question_no }}</strong></td>
                                                <td><span class="front-account-datatable-title" title="{{ $question->subject }}">{{ $question->subject }}</span></td>
                                                <td><span class="front-ad-request-badge">{{ $question->userStatusLabel() }}</span></td>
                                                <td data-order="{{ $question->created_at?->timestamp }}">
                                                    <time datetime="{{ $question->created_at?->toDateString() }}" dir="ltr">{{ $question->created_at?->format('d M Y') }}</time>
                                                </td>
                                                <td>
                                                    <a class="front-account-outline-action" href="{{ route('front.account.questions.show', $question) }}">
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
                                <i class="fa-regular fa-circle-question" aria-hidden="true"></i>
                                <p>You have not asked a question yet.</p>
                                <a class="front-account-outline-action" href="{{ route('front.ask-question') }}">Ask Your First Question</a>
                            </div>
                        @endif

                        @if ($questions->hasPages())
                            <div class="user-subscriptions-pagination">{{ $questions->links() }}</div>
                        @endif
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
