@extends('layouts.frontLayout.front-design')

@section('title', $course->title)
@section('meta_description', $course->short_description)

@section('content')
    @php
        $lessonCount = $course->sections->sum(fn ($section) => $section->lessons->count());
        $hasInstructor = filled($course->instructor_name) || filled($course->instructor_bio) || filled($course->instructor_image);
        $hasCurriculum = $course->sections->isNotEmpty();
        $hasFaqs = $course->faqs->isNotEmpty();
        $courseUrl = url()->current();
        $shareText = rawurlencode($course->title);
        $shareUrl = rawurlencode($courseUrl);
        $languageLabel = match ($course->language) {
            'en' => 'English',
            'ur' => 'Urdu',
            default => strtoupper((string) $course->language),
        };
    @endphp

    <section class="course-detail">
        <div class="container-fluid course-detail__container">
            <nav class="front-taza-breadcrumb front-ui" aria-label="Breadcrumb">
                <a href="{{ route('frontend.home') }}"><i class="fa-solid fa-house" aria-hidden="true"></i> Home</a>
                <span aria-hidden="true">/</span><a href="{{ route('front.courses.index') }}">Courses</a>
                <span aria-hidden="true">/</span><span aria-current="page">{{ $course->title }}</span>
            </nav>

            <article class="course-detail__hero">
                <div class="course-detail__hero-media course-detail__hero-media--{{ $course->image_fit }}">
                    @if (filled($course->image))
                        <img class="course-detail__hero-image--{{ $course->image_fit }}" src="{{ asset($course->image) }}" alt="{{ $course->title }}">
                    @else
                        <span class="course-detail__hero-placeholder"><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i></span>
                    @endif
                </div>
                <div class="course-detail__hero-content">
                    <h1>{{ $course->title }}</h1>
                    @if (filled($course->short_description))
                        <p class="course-detail__subtitle">{{ $course->short_description }}</p>
                    @endif
                    <dl class="course-detail__hero-meta front-ui">
                        <div><i class="fa-regular fa-clock" aria-hidden="true"></i><dt>Duration</dt><dd>{{ $course->formattedDuration() }}</dd></div>
                        @if (filled($course->level))<div><i class="fa-solid fa-signal" aria-hidden="true"></i><dt>Level</dt><dd>{{ $course->level }}</dd></div>@endif
                        @if (filled($course->instructor_name))<div><i class="fa-regular fa-user" aria-hidden="true"></i><dt>Instructor</dt><dd>{{ $course->instructor_name }}</dd></div>@endif
                        @if ($lessonCount > 0)<div><i class="fa-solid fa-book-open" aria-hidden="true"></i><dt>Lessons</dt><dd>{{ $lessonCount }}</dd></div>@endif
                    </dl>
                    <a class="front-taza-button course-detail__cta" href="{{ route('front.contact') }}">Contact for Details <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </div>
            </article>

            <div class="course-detail__layout">
                <div class="course-detail__main">
                    <div class="course-detail__tabs-card">
                        <nav class="course-detail__tabs" aria-label="Course detail sections" role="tablist">
                            <button class="active" id="course-overview-tab" type="button" data-bs-toggle="tab" data-bs-target="#course-overview" role="tab" aria-controls="course-overview" aria-selected="true">Overview</button>
                            @if ($hasCurriculum)<button id="course-content-tab" type="button" data-bs-toggle="tab" data-bs-target="#course-content" role="tab" aria-controls="course-content" aria-selected="false">Course Content</button>@endif
                            @if ($hasInstructor)<button id="course-instructor-tab" type="button" data-bs-toggle="tab" data-bs-target="#course-instructor" role="tab" aria-controls="course-instructor" aria-selected="false">Instructor</button>@endif
                            @if ($hasFaqs)<button id="course-faqs-tab" type="button" data-bs-toggle="tab" data-bs-target="#course-faqs" role="tab" aria-controls="course-faqs" aria-selected="false">FAQs</button>@endif
                        </nav>
                        <div class="tab-content course-detail__tab-content">
                            <section class="tab-pane fade show active" id="course-overview" role="tabpanel" aria-labelledby="course-overview-tab" tabindex="0">
                                @if (filled($course->description))<div class="course-detail__rich-text"><h2>Course Description</h2>{!! $course->description !!}</div>@endif
                                @foreach (['learningOutcomes' => 'What You Will Learn', 'targetAudiences' => 'Who Is This Course For?', 'requirements' => 'Requirements'] as $relation => $heading)
                                    @if ($course->{$relation}->isNotEmpty())
                                        <section class="course-detail__checklist"><h2>{{ $heading }}</h2><ul>@foreach ($course->{$relation} as $item)<li><i class="fa-solid fa-check" aria-hidden="true"></i><span>{{ $item->content }}</span></li>@endforeach</ul></section>
                                    @endif
                                @endforeach
                                @if (blank($course->description) && $course->learningOutcomes->isEmpty() && $course->targetAudiences->isEmpty() && $course->requirements->isEmpty())<p class="course-detail__empty">Course information will be available soon.</p>@endif
                            </section>

                            @if ($hasCurriculum)
                                <section class="tab-pane fade" id="course-content" role="tabpanel" aria-labelledby="course-content-tab" tabindex="0">
                                    <div class="course-detail__panel-heading"><h2>Course Content</h2><span>{{ $course->sections->count() }} sections · {{ $lessonCount }} lessons</span></div>
                                    <div class="accordion course-detail__accordion" id="course-curriculum">
                                        @foreach ($course->sections as $section)
                                            <article class="accordion-item">
                                                <h2 class="accordion-header" id="course-section-heading-{{ $section->id }}"><button class="accordion-button @if (! $loop->first) collapsed @endif" type="button" data-bs-toggle="collapse" data-bs-target="#course-section-{{ $section->id }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="course-section-{{ $section->id }}">{{ $section->title }} <span>{{ $section->lessons->count() }} lessons</span></button></h2>
                                                <div id="course-section-{{ $section->id }}" class="accordion-collapse collapse @if ($loop->first) show @endif" aria-labelledby="course-section-heading-{{ $section->id }}" data-bs-parent="#course-curriculum"><div class="accordion-body">
                                                    @if (filled($section->description))<p>{{ $section->description }}</p>@endif
                                                    <ol class="course-detail__lessons">@foreach ($section->lessons as $lesson)<li><div><strong>{{ $lesson->title }}</strong>@if (filled($lesson->description))<p>{{ $lesson->description }}</p>@endif</div><span>@if (filled($lesson->lesson_type)){{ $lesson->lesson_type }}@endif @if ($lesson->duration_minutes) · {{ $lesson->duration_minutes }} min @endif</span></li>@endforeach</ol>
                                                </div></div>
                                            </article>
                                        @endforeach
                                    </div>
                                </section>
                            @endif

                            @if ($hasInstructor)
                                <section class="tab-pane fade" id="course-instructor" role="tabpanel" aria-labelledby="course-instructor-tab" tabindex="0">
                                    <div class="course-detail__instructor">
                                        @if (filled($course->instructor_image))<img src="{{ asset($course->instructor_image) }}" alt="{{ $course->instructor_name ?: 'Course instructor' }}">@endif
                                        <div><h2>{{ $course->instructor_name ?: 'Instructor' }}</h2>@if (filled($course->instructor_bio))<p>{{ $course->instructor_bio }}</p>@endif</div>
                                    </div>
                                </section>
                            @endif

                            @if ($hasFaqs)
                                <section class="tab-pane fade" id="course-faqs" role="tabpanel" aria-labelledby="course-faqs-tab" tabindex="0">
                                    <div class="accordion course-detail__accordion" id="course-faqs-accordion">
                                        @foreach ($course->faqs as $faq)
                                            <article class="accordion-item"><h2 class="accordion-header" id="course-faq-heading-{{ $faq->id }}"><button class="accordion-button @if (! $loop->first) collapsed @endif" type="button" data-bs-toggle="collapse" data-bs-target="#course-faq-{{ $faq->id }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="course-faq-{{ $faq->id }}">{{ $faq->question }}</button></h2><div id="course-faq-{{ $faq->id }}" class="accordion-collapse collapse @if ($loop->first) show @endif" aria-labelledby="course-faq-heading-{{ $faq->id }}" data-bs-parent="#course-faqs-accordion"><div class="accordion-body">{{ $faq->answer }}</div></div></article>
                                        @endforeach
                                    </div>
                                </section>
                            @endif
                        </div>
                    </div>
                </div>

                <aside class="course-detail__sidebar">
                    <section class="course-detail__info-card"><h2>Course Information</h2><dl>
                        <div><dt><i class="fa-regular fa-clock" aria-hidden="true"></i> Duration</dt><dd>{{ $course->formattedDuration() }}</dd></div>
                        @if (filled($course->level))<div><dt><i class="fa-solid fa-signal" aria-hidden="true"></i> Level</dt><dd>{{ $course->level }}</dd></div>@endif
                        @if ($lessonCount > 0)<div><dt><i class="fa-solid fa-book-open" aria-hidden="true"></i> Lessons</dt><dd>{{ $lessonCount }}</dd></div>@endif
                        @if (filled($course->instructor_name))<div><dt><i class="fa-regular fa-user" aria-hidden="true"></i> Instructor</dt><dd>{{ $course->instructor_name }}</dd></div>@endif
                        @if (filled($course->language))<div><dt><i class="fa-solid fa-globe" aria-hidden="true"></i> Language</dt><dd>{{ $languageLabel }}</dd></div>@endif
                        <div><dt><i class="fa-regular fa-certificate" aria-hidden="true"></i> Certificate</dt><dd>{{ $course->certificate_available ? 'Yes' : 'No' }}</dd></div>
                        @if (filled($course->access_type))<div><dt><i class="fa-solid fa-unlock-keyhole" aria-hidden="true"></i> Access</dt><dd>{{ $course->access_type }}</dd></div>@endif
                        <div><dt><i class="fa-regular fa-calendar" aria-hidden="true"></i> Last Updated</dt><dd>{{ $course->updated_at?->format('M j, Y') }}</dd></div>
                    </dl>@if (filled($course->access_description))<p class="course-detail__access-description">{{ $course->access_description }}</p>@endif<a class="front-taza-button course-detail__sidebar-cta" href="{{ route('front.contact') }}">Contact for Details <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></section>
                    <section class="course-detail__share-card"><h2>Share This Course</h2><div class="course-detail__share-links"><a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Share on Facebook"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></a><a href="https://twitter.com/intent/tweet?text={{ $shareText }}&url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Share on X"><i class="fa-brands fa-x-twitter" aria-hidden="true"></i></a><a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Share on LinkedIn"><i class="fa-brands fa-linkedin-in" aria-hidden="true"></i></a><a href="https://wa.me/?text={{ rawurlencode($course->title.' '.$courseUrl) }}" target="_blank" rel="noopener noreferrer" aria-label="Share on WhatsApp"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></a><button type="button" data-course-copy-link data-course-url="{{ $courseUrl }}" aria-label="Copy course link"><i class="fa-solid fa-link" aria-hidden="true"></i><span class="visually-hidden" data-course-copy-feedback>Copy course link</span></button></div></section>
                </aside>
            </div>

            @if ($course->relatedCourses->isNotEmpty())
                <section class="course-detail__related" aria-labelledby="related-courses-heading">
                    <div class="course-detail__related-heading"><div class="front-section-heading"><h2 id="related-courses-heading">Related Courses</h2><span class="front-section-heading__line" aria-hidden="true"></span></div><a class="front-taza-button" href="{{ route('front.courses.index') }}">View All Courses <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
                    <div class="row g-4">
                        @foreach ($course->relatedCourses as $relatedCourse)
                            <div class="col-12 col-sm-6 col-xl-3 d-flex">@include('frontend.courses._card', ['course' => $relatedCourse])</div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </section>
@endsection
