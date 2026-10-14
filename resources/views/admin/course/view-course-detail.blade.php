@extends('layouts.adminLayout.admin-design')

@section('title', 'Course Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb-4">
        <h2>Course Details</h2>
        <a class="btn btn-outline-secondary" href="{{ route('admin.course.index') }}">Back</a>
    </div>
    <section class="card admin-settings-card mb-4">
        <div class="card-header"><h3>Basic Information</h3></div>
        <div class="card-body">
            <h3>{{ $course->title }}</h3>
            <p><strong>Button Label:</strong> {{ $course->button_label }}</p>
            <p><strong>Duration:</strong> {{ $course->formattedDuration() }}</p>
            @if ($course->image)
                <img class="admin-current-image mb-3" src="{{ asset($course->image) }}" alt="{{ $course->title }}">
            @endif
            @if (filled($course->description))
                <div class="article-content-preview">{!! $course->description !!}</div>
            @endif
        </div>
    </section>
    <section class="card admin-settings-card mb-4">
        <div class="card-header"><h3>Course Details</h3></div>
        <div class="card-body">
            @if ($course->level)<p><strong>Level:</strong> {{ $course->level }}</p>@endif
            @if ($course->instructor_name)<p><strong>Instructor:</strong> {{ $course->instructor_name }}</p>@endif
            @foreach (['learningOutcomes' => 'What You Will Learn', 'targetAudiences' => 'Who Is This Course For?', 'requirements' => 'Requirements'] as $relation => $heading)
                @if ($course->{$relation}->isNotEmpty())
                    <h4 class="h5">{{ $heading }}</h4>
                    <ul>@foreach ($course->{$relation} as $item)<li>{{ $item->content }}</li>@endforeach</ul>
                @endif
            @endforeach
        </div>
    </section>
    @if ($course->sections->isNotEmpty())
        <section class="card admin-settings-card mb-4"><div class="card-header"><h3>Curriculum ({{ $course->sections->sum(fn ($section) => $section->lessons->count()) }} Lessons)</h3></div><div class="card-body">@foreach ($course->sections as $section)<h4 class="h5">{{ $section->title }}</h4><ul>@foreach ($section->lessons as $lesson)<li>{{ $lesson->title }} {{ $lesson->lesson_type }} {{ $lesson->duration_minutes }} min</li>@endforeach</ul>@endforeach</div></section>
    @endif
    @if ($course->certificate_available || $course->access_type || $course->faqs->isNotEmpty())
        <section class="card admin-settings-card mb-4"><div class="card-header"><h3>Additional Information</h3></div><div class="card-body">
            @if ($course->certificate_available)<p>Certificate Available: Yes</p>@endif
            @if ($course->access_type)<p>Access Type: {{ $course->access_type }}</p>@endif
            @foreach ($course->faqs as $faq)<p><strong>{{ $faq->question }}</strong><br>{{ $faq->answer }}</p>@endforeach
        </div></section>
    @endif
</div>
@endsection
