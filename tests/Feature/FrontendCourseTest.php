<?php

use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function publicCourse(array $attributes = []): Course
{
    return Course::query()->forceCreate(array_replace([
        'language' => 'en',
        'title' => 'React Native Complete Course',
        'button_label' => 'View Course',
        'image' => 'backend-images/course/react-native.png',
        'short_description' => 'Build cross-platform mobile applications.',
        'description' => '<p>Complete Course description.</p>',
        'duration_type' => 'hours',
        'duration_value' => 8,
        'isActive' => true,
        'isFeatured' => false,
        'status' => Course::STATUS_PUBLISHED,
        'published_at' => now()->subMinute(),
    ], $attributes));
}

test('listing exposes only publicly eligible Courses with individual card labels', function (): void {
    $visible = publicCourse();
    $second = publicCourse(['title' => 'Laravel Course', 'button_label' => 'Explore Course']);
    publicCourse(['title' => 'Draft Course', 'status' => Course::STATUS_DRAFT]);
    publicCourse(['title' => 'Inactive Course', 'isActive' => false]);
    publicCourse(['title' => 'Future Course', 'published_at' => now()->addHour()]);

    $this->get(route('front.courses.index'))
        ->assertSuccessful()
        ->assertViewIs('frontend.courses.index')
        ->assertSee($visible->title)
        ->assertSee($visible->short_description)
        ->assertSee('8 Hours')
        ->assertSee('View Course')
        ->assertSee('Explore Course')
        ->assertDontSee('Ask a Question')
        ->assertSee('col-12 col-md-6 col-xl-4 d-flex', false)
        ->assertSee('consultancy-card course-card w-100', false)
        ->assertSee('consultancy-card__image--cover', false)
        ->assertSee(route('front.courses.show', $visible), false)
        ->assertSee(route('front.courses.show', $second), false)
        ->assertDontSee('Draft Course')
        ->assertDontSee('Inactive Course')
        ->assertDontSee('Future Course')
        ->assertDontSee('owner_admin_id', false);
});

test('detail protects unpublished Courses and filters directional related Courses', function (): void {
    $course = publicCourse(['title' => 'Course A', 'button_label' => 'A Label']);
    $eligible = publicCourse(['title' => 'Course B', 'button_label' => 'Explore B']);
    $draft = publicCourse(['title' => 'Course C', 'status' => Course::STATUS_DRAFT]);
    $inactive = publicCourse(['title' => 'Course D', 'isActive' => false]);
    $future = publicCourse(['title' => 'Course E', 'published_at' => now()->addHour()]);
    $reverse = publicCourse(['title' => 'Course F']);
    $course->relatedCourses()->attach([$eligible->id, $draft->id, $inactive->id, $future->id]);
    $reverse->relatedCourses()->attach($course->id);

    $this->get(route('front.courses.show', $course))
        ->assertSuccessful()
        ->assertSee('Course A')
        ->assertSee('Complete Course description.', false)
        ->assertSee('Contact for Details')
        ->assertSee('Course Information')
        ->assertSee('Share This Course')
        ->assertSee('Course B')
        ->assertSee('Explore B')
        ->assertSee('col-12 col-sm-6 col-xl-3 d-flex', false)
        ->assertSee('consultancy-card course-card w-100', false)
        ->assertSee('consultancy-card__image--cover', false)
        ->assertDontSee(route('front.courses.show', $draft), false)
        ->assertDontSee(route('front.courses.show', $inactive), false)
        ->assertDontSee(route('front.courses.show', $future), false)
        ->assertDontSee(route('front.courses.show', $reverse), false);

    $this->get(route('front.courses.show', $draft))->assertNotFound();
    $this->get(route('front.courses.show', $inactive))->assertNotFound();
    $this->get(route('front.courses.show', $future))->assertNotFound();
    $this->get(route('front.courses.show', 999999))->assertNotFound();
});

test('detail renders real Course management data in functional tabs', function (): void {
    $course = publicCourse([
        'level' => 'Beginner',
        'instructor_name' => 'Jane Doe',
        'instructor_bio' => 'A real instructor biography.',
        'certificate_available' => true,
        'access_type' => 'Lifetime',
    ]);

    $course->detailItems()->create(['type' => 'learning_outcome', 'content' => 'Build a visual identity.', 'sort_order' => 1]);
    $course->detailItems()->create(['type' => 'target_audience', 'content' => 'New designers.', 'sort_order' => 1]);
    $course->detailItems()->create(['type' => 'requirement', 'content' => 'A laptop.', 'sort_order' => 1]);
    $section = $course->sections()->create(['title' => 'Foundation', 'description' => 'Start here.', 'sort_order' => 1]);
    $section->lessons()->create(['title' => 'Brand basics', 'lesson_type' => 'Video', 'duration_minutes' => 20, 'sort_order' => 1]);
    $course->faqs()->create(['question' => 'Is a certificate included?', 'answer' => 'Yes.', 'sort_order' => 1]);

    $this->get(route('front.courses.show', $course))
        ->assertSuccessful()
        ->assertSee('Overview')
        ->assertSee('class="active" id="course-overview-tab"', false)
        ->assertSee('Course Content')
        ->assertSee('Instructor')
        ->assertSee('FAQs')
        ->assertDontSee('Reviews')
        ->assertSee('Build a visual identity.')
        ->assertSee('Foundation')
        ->assertSee('Brand basics')
        ->assertSee('Jane Doe')
        ->assertSee('fa-solid fa-book-open', false)
        ->assertSee('A real instructor biography.')
        ->assertSee('Is a certificate included?')
        ->assertSee('Yes.')
        ->assertSee('Lifetime')
        ->assertSee('data-course-copy-feedback', false)
        ->assertSee('data-course-copy-link', false);
});

test('detail omits the long description block when a Course has no description', function (): void {
    $course = publicCourse(['description' => '']);

    $this->get(route('front.courses.show', $course))
        ->assertSuccessful()
        ->assertDontSee('Course Description');
});

test('detail centers a small Course icon inside the full hero media area', function (): void {
    $course = publicCourse([
        'image' => 'backend-images/course/01M488C67CDWCKFJCCD9DMTS0W.png',
    ]);

    $this->get(route('front.courses.show', $course))
        ->assertSuccessful()
        ->assertSee('course-detail__hero-media--contain', false)
        ->assertSee('course-detail__hero-image--contain', false);
});

test('listing and related Course cards contain small Course icons', function (): void {
    $course = publicCourse([
        'image' => 'backend-images/course/01M4QPK5ACG0C7TY3VYH7PS7BF.jpg',
    ]);
    $smallIconCourse = publicCourse([
        'title' => 'Small Icon Course',
        'image' => 'backend-images/course/01M488C67CDWCKFJCCD9DMTS0W.png',
    ]);
    $course->relatedCourses()->attach($smallIconCourse->id);

    $this->get(route('front.courses.index'))
        ->assertSuccessful()
        ->assertSee('Small Icon Course')
        ->assertSee('consultancy-card__image--contain', false);

    $this->get(route('front.courses.show', $course))
        ->assertSuccessful()
        ->assertSee('Small Icon Course')
        ->assertSee('consultancy-card__image--contain', false);
});

test('listing has a clean empty state when no Course is publicly available', function (): void {
    publicCourse(['status' => Course::STATUS_DRAFT]);

    $this->get(route('front.courses.index'))
        ->assertSuccessful()
        ->assertSee('No courses are currently available.');
});
