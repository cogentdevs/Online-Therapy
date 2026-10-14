@php($courseImageFit = in_array($course->image_fit ?? $homepageImageFit ?? null, ['contain', 'cover'], true) ? ($course->image_fit ?? $homepageImageFit) : 'cover')

<article class="consultancy-card course-card w-100">
    <a class="consultancy-card__image consultancy-card__image--{{ $courseImageFit }}" href="{{ route('front.courses.show', $course) }}">
        @if (filled($course->image))
            <img @class([
                'consultancy-card__image-content--'.$courseImageFit,
            ]) src="{{ asset($course->image) }}" alt="{{ $course->title }}" loading="lazy">
        @else
            <span class="consultancy-card__image-placeholder"><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i></span>
        @endif
    </a>
    <div class="consultancy-card__body">
        <h2><a href="{{ route('front.courses.show', $course) }}">{{ $course->title }}</a></h2>
        @if (filled($course->short_description))
            <p>{{ $course->short_description }}</p>
        @endif
        <dl class="consultancy-card__details front-ui">
            <div><dt>Duration</dt><dd>{{ $course->formattedDuration() }}</dd></div>
        </dl>
        <a class="front-taza-button consultancy-card__button" href="{{ route('front.courses.show', $course) }}">{{ $course->button_label ?: 'View Details' }}</a>
    </div>
</article>
