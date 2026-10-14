<?php

use App\Models\Course;
use App\Models\CourseDetailItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    $this->course = Course::query()->forceCreate(['language' => 'en', 'title' => 'Course', 'button_label' => 'View', 'description' => '', 'duration_type' => 'hours', 'duration_value' => 1, 'isActive' => true, 'isFeatured' => false, 'status' => Course::STATUS_DRAFT, 'owner_admin_id' => $this->admin->id]);
});
function courseManagementPayload(array $overrides = []): array
{
    return array_replace(['title' => 'Course', 'button_label' => 'View', 'description' => '', 'duration_type' => 'hours', 'duration_value' => 1, 'isActive' => '1', 'isFeatured' => '0'], $overrides);
}
test('nested markers preserve and clear intended collections', function (): void {
    $outcome = $this->course->detailItems()->create(['type' => CourseDetailItem::LEARNING_OUTCOME, 'content' => 'Old', 'sort_order' => 0]);
    $this->actingAs($this->admin)->post(route('admin.course.update', $this->course->id), courseManagementPayload())->assertRedirect();
    expect($outcome->refresh()->exists)->toBeTrue();
    $this->post(route('admin.course.update', $this->course->id), courseManagementPayload(['learning_outcomes_present' => '1']))->assertRedirect();
    expect($this->course->detailItems()->count())->toBe(0);
});
test('course management detail page renders saved curriculum and metadata', function (): void {
    $this->course->update(['level' => 'Beginner', 'instructor_name' => 'Jane', 'certificate_available' => true, 'access_type' => 'Lifetime']);
    $this->course->detailItems()->create(['type' => CourseDetailItem::LEARNING_OUTCOME, 'content' => 'Learn', 'sort_order' => 0]);
    $section = $this->course->sections()->create(['title' => 'Start', 'sort_order' => 0]);
    $section->lessons()->create(['title' => 'Intro', 'lesson_type' => 'Video', 'duration_minutes' => 5, 'sort_order' => 0]);
    $this->actingAs($this->admin)->get(route('admin.course.show', $this->course->id))->assertSuccessful()->assertSee(['Jane', 'Learn', 'Start', 'Intro', '1 Lessons', 'Lifetime']);
});
