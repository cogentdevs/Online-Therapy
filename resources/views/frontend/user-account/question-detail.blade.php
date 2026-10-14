@extends('layouts.frontLayout.front-design')

@section('title', 'Question '.$question->question_no)
@section('meta_description', 'View your question and the response from the administration.')

@section('content')
    <div class="front-account-page">
        <div class="container-fluid front-account-container">
            <nav class="front-taza-breadcrumb front-ui" aria-label="Breadcrumb">
                <a href="{{ route('frontend.home') }}"><i class="fa-solid fa-house" aria-hidden="true"></i> Home</a>
                <span aria-hidden="true">/</span><a href="{{ route('front.account') }}">My Account</a>
                <span aria-hidden="true">/</span><a href="{{ route('front.account.questions.index') }}">My Questions</a>
                <span aria-hidden="true">/</span><span aria-current="page" dir="ltr">{{ $question->question_no }}</span>
            </nav>

            <header class="front-account-heading">
                <h1>Question Details</h1><span aria-hidden="true"></span>
                <p dir="ltr">{{ $question->question_no }}</p>
            </header>

            <div class="row g-4 align-items-start front-account-layout">
                <aside class="col-12 col-lg-3">@include('frontend.user-account.partials.sidebar')</aside>
                <div class="col-12 col-lg-9">
                    <section class="front-account-card front-ad-request-card">
                        <header class="user-subscriptions-section-header">
                            <div><h2>Question Information</h2><p>Submitted information and current status.</p></div>
                            <span class="front-ad-request-badge">{{ $question->userStatusLabel() }}</span>
                        </header>
                        <dl class="front-ad-request-detail-grid">
                            <div><dt>Question No.</dt><dd dir="ltr">{{ $question->question_no }}</dd></div>
                            <div><dt>Submitted On</dt><dd>{{ $question->created_at?->format('d M Y, h:i A') }}</dd></div>
                            <div><dt>Name</dt><dd>{{ $question->name }}</dd></div>
                            <div><dt>Email</dt><dd dir="ltr">{{ $question->email }}</dd></div>
                            <div><dt>Phone Number</dt><dd dir="ltr">{{ $question->phone }}</dd></div>
                            <div class="front-ad-request-detail-grid__full"><dt>Subject</dt><dd>{{ $question->subject }}</dd></div>
                            <div class="front-ad-request-detail-grid__full"><dt>Original Question</dt><dd class="front-ad-request-message">{{ $question->sawal }}</dd></div>
                        </dl>
                    </section>

                    <section class="front-account-card front-ad-request-card mt-4">
                        <header class="user-subscriptions-section-header"><div><h2>Response</h2><p>The current status of the response provided by our team.</p></div></header>
                        @if (filled($question->admin_response))
                            <div class="front-ad-request-message">{{ $question->admin_response }}</div>
                        @elseif ($question->status === \App\Models\AskQuestion::STATUS_PENDING)
                            <p class="front-ad-request-note">Your question has been received and is being reviewed by our team. You will be notified by email when a response is available.</p>
                        @elseif ($question->status === \App\Models\AskQuestion::STATUS_CLOSED)
                            <p class="front-ad-request-note">This question has been closed.</p>
                        @else
                            <p class="front-ad-request-note">A response is not available yet.</p>
                        @endif
                    </section>

                    <div class="mt-3">
                        <a class="front-account-outline-action" href="{{ route('front.ask-question') }}">Ask a New Question</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
