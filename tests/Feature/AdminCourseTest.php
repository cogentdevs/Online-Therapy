<?php

use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Permission::findOrCreate('admin.access', 'web');
    foreach (array_keys(config('admin_modules.modules.courses.actions')) as $action) {
        Permission::findOrCreate('courses.'.$action, 'web');
    }
    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole(Role::findOrCreate('super-admin', 'web'));
});

function coursePayload(array $overrides = []): array
{
    return array_replace(['title' => 'React Native Course', 'button_label' => 'Start Learning', 'image' => UploadedFile::fake()->image('course.png'), 'short_description' => 'Build mobile applications.', 'description' => '<p>Complete course description.</p>', 'duration_type' => 'hours', 'duration_value' => 8, 'isActive' => '1', 'isFeatured' => '0'], $overrides);
}
function ownedCourse(User $admin, array $attributes = []): Course
{
    return Course::query()->forceCreate(array_replace(['language' => 'en', 'title' => 'Course', 'button_label' => 'View Course', 'description' => '<p>Course body.</p>', 'duration_type' => 'hours', 'duration_value' => 1, 'isActive' => true, 'isFeatured' => false, 'status' => Course::STATUS_DRAFT, 'owner_admin_id' => $admin->id, 'created_by' => $admin->id, 'updated_by' => $admin->id], $attributes));
}

test('Course CRUD creates fixed English drafts and publishes valid active Courses', function (): void {
    $this->actingAs($this->admin)->post(route('admin.course.store'), coursePayload(['isFeatured' => '1']))->assertRedirect(route('admin.course.index'));
    $course = Course::query()->sole();
    expect($course->language)->toBe('en')->and($course->status)->toBe(Course::STATUS_DRAFT)->and($course->button_label)->toBe('Start Learning')->and($course->isFeatured)->toBeTrue();
    expect(ActivityLog::query()->where('module', 'courses')->where('action', 'created')->where('subject_id', $course->id)->exists())->toBeTrue();
    $this->post(route('admin.course.publish', $course->id))->assertSessionHas('status', 'Course published successfully.');
    expect($course->refresh()->status)->toBe(Course::STATUS_PUBLISHED)->and($course->published_at)->not->toBeNull();
    expect(ActivityLog::query()->where('module', 'courses')->where('action', 'published')->where('subject_id', $course->id)->exists())->toBeTrue();
});

test('Course relationships are directional and self relations are rejected', function (): void {
    $course = ownedCourse($this->admin);
    $related = ownedCourse($this->admin, ['title' => 'Related']);
    $this->actingAs($this->admin)->post(route('admin.course.update', $course->id), coursePayload(['image' => null, 'related_course_ids' => [$related->id]]))->assertRedirect();
    expect($course->relatedCourses()->whereKey($related)->exists())->toBeTrue()->and($related->relatedCourses()->whereKey($course)->exists())->toBeFalse();
    $this->post(route('admin.course.update', $course->id), coursePayload(['image' => null, 'related_course_ids' => [$course->id]]))->assertSessionHasErrors('related_course_ids.0');
});

test('Course create and update allow an empty long description', function (): void {
    $course = ownedCourse($this->admin);

    $this->actingAs($this->admin)
        ->post(route('admin.course.store'), coursePayload(['description' => '<p>&nbsp;</p>']))
        ->assertRedirect(route('admin.course.index'));

    $newCourse = Course::query()->where('title', 'React Native Course')->sole();
    expect($newCourse->description)->toBe('');
    $this->post(route('admin.course.publish', $newCourse->id))->assertSessionHas('status', 'Course published successfully.');

    $this->post(route('admin.course.update', $course->id), coursePayload(['image' => null, 'description' => '<p><br></p>']))
        ->assertRedirect(route('admin.course.index'));

    expect($course->refresh()->description)->toBe('');
});

test('Course create keeps image and duration validation', function (): void {
    $this->actingAs($this->admin)
        ->post(route('admin.course.store'), coursePayload(['image' => null]))
        ->assertSessionHasErrors('image');

    $this->post(route('admin.course.store'), coursePayload(['duration_type' => '', 'duration_value' => 0]))
        ->assertSessionHasErrors(['duration_type', 'duration_value']);
});

test('Course listing renders for an authorized Admin', function (): void {
    $course = ownedCourse($this->admin, ['title' => 'Listed Course']);

    $this->actingAs($this->admin)
        ->get(route('admin.course.index'))
        ->assertSuccessful()
        ->assertViewIs('admin.course.view-course')
        ->assertSee($course->title);
});

test('Course add and edit forms display image upload guidance', function (): void {
    $course = ownedCourse($this->admin);

    $this->actingAs($this->admin)
        ->get(route('admin.course.create'))
        ->assertSuccessful()
        ->assertSee('Recommended Image Size: 1200 × 750 px (16:10 ratio). Maximum File Size: 5 MB. Allowed Formats: JPG, JPEG, PNG, WEBP.')
        ->assertDontSee('Tip: Small logos/icons');

    $this->get(route('admin.course.edit', $course->id))
        ->assertSuccessful()
        ->assertSee('Recommended Image Size: 1200 × 750 px (16:10 ratio). Maximum File Size: 5 MB. Allowed Formats: JPG, JPEG, PNG, WEBP.')
        ->assertDontSee('Tip: Small logos/icons');
});
