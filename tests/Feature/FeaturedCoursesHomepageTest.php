<?php

use App\Models\Course;
use App\Models\GeneralSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    Permission::findOrCreate('admin.access', 'web');

    foreach (array_keys(config('admin_modules.modules.courses.actions')) as $action) {
        Permission::findOrCreate('courses.'.$action, 'web');
    }

    $this->superAdmin = User::factory()->create(['is_active' => true]);
    $this->superAdmin->assignRole(Role::findOrCreate('super-admin', 'web'));

    $this->originalFeaturedCoursesPublicPath = public_path();
    $this->featuredCoursesPublicPath = storage_path('framework/testing/featured-courses-public-'.Str::uuid());
    File::ensureDirectoryExists($this->featuredCoursesPublicPath);
    app()->usePublicPath($this->featuredCoursesPublicPath);
});

afterEach(function (): void {
    app()->usePublicPath($this->originalFeaturedCoursesPublicPath);
    File::deleteDirectory($this->featuredCoursesPublicPath);
});

function homepageCourse(array $attributes = []): Course
{
    return Course::query()->forceCreate(array_replace([
        'language' => 'en',
        'title' => 'Featured Course',
        'button_label' => 'View Course',
        'image' => null,
        'short_description' => 'Featured course summary.',
        'description' => '<p>Course description.</p>',
        'duration_type' => 'hours',
        'duration_value' => 8,
        'isActive' => true,
        'isFeatured' => true,
        'status' => Course::STATUS_PUBLISHED,
        'published_at' => now()->subMinute(),
    ], $attributes));
}

function featuredCoursesAdmin(array $permissions): User
{
    $role = Role::findOrCreate('featured-courses-admin-'.Str::random(8), 'web');
    $role->syncPermissions(['admin.access', ...$permissions]);
    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole($role);

    return $admin;
}

test('homepage shows only featured publicly available Courses while the Courses listing shows both featured states', function (): void {
    $featured = homepageCourse(['title' => 'Featured Homepage Course', 'button_label' => 'Explore Featured']);
    $notFeatured = homepageCourse(['title' => 'Listing Only Course', 'isFeatured' => false]);
    homepageCourse(['title' => 'Draft Featured Course', 'status' => Course::STATUS_DRAFT]);
    homepageCourse(['title' => 'Inactive Featured Course', 'isActive' => false]);
    homepageCourse(['title' => 'Future Featured Course', 'published_at' => now()->addHour()]);

    $this->get(route('frontend.home'))
        ->assertSuccessful()
        ->assertSee('Courses')
        ->assertSee($featured->title)
        ->assertSee($featured->button_label)
        ->assertSee(route('front.courses.show', $featured), false)
        ->assertDontSee($notFeatured->title)
        ->assertDontSee('Draft Featured Course')
        ->assertDontSee('Inactive Featured Course')
        ->assertDontSee('Future Featured Course')
        ->assertSee('col-12 col-sm-6 col-xl-4', false)
        ->assertSee('col-12 col-sm-6 col-xl-4 d-flex', false)
        ->assertSee('consultancy-card course-card w-100', false);

    $this->get(route('front.courses.index'))
        ->assertSuccessful()
        ->assertSee($featured->title)
        ->assertSee($notFeatured->title)
        ->assertSee('consultancy-card__image--cover', false);
});

test('homepage omits the Featured Courses section when no eligible Course exists', function (): void {
    homepageCourse(['isFeatured' => false]);

    $this->get(route('frontend.home'))
        ->assertSuccessful()
        ->assertDontSee('featured-courses-heading', false);
});

test('homepage applies contain to small Course images and cover to large Course images', function (): void {
    $directory = public_path('backend-images/course');
    File::ensureDirectoryExists($directory);

    UploadedFile::fake()->image('small.png', 120, 120)->move($directory, 'small.png');
    UploadedFile::fake()->image('large.png', 600, 360)->move($directory, 'large.png');

    homepageCourse(['title' => 'Small Image Course', 'image' => 'backend-images/course/small.png']);
    homepageCourse(['title' => 'Large Image Course', 'image' => 'backend-images/course/large.png']);

    $this->get(route('frontend.home'))
        ->assertSuccessful()
        ->assertSee('consultancy-card__image--contain', false)
        ->assertSee('consultancy-card__image-content--contain', false)
        ->assertSee('consultancy-card__image--cover', false)
        ->assertSee('consultancy-card__image-content--cover', false);
});

test('authorized Admin can save the four-column homepage layout and invalid values are rejected', function (): void {
    $this->actingAs($this->superAdmin)
        ->get(route('admin.course.homepage-layout-settings'))
        ->assertSuccessful()
        ->assertSee('3 Cards Per Row');

    $this->put(route('admin.course.homepage-layout-settings.update'), ['featured_courses_desktop_columns' => 4])
        ->assertRedirect(route('admin.course.homepage-layout-settings'));

    expect(GeneralSetting::current()->featured_courses_desktop_columns)->toBe(4);

    homepageCourse();

    $this->get(route('frontend.home'))
        ->assertSuccessful()
        ->assertSee('col-12 col-sm-6 col-xl-3', false);

    $this->put(route('admin.course.homepage-layout-settings.update'), ['featured_courses_desktop_columns' => 2])
        ->assertSessionHasErrors('featured_courses_desktop_columns');
});

test('Admin without the existing Courses edit permission cannot update homepage layout settings', function (): void {
    $admin = featuredCoursesAdmin(['courses.view']);

    $this->actingAs($admin)
        ->get(route('admin.course.homepage-layout-settings'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->put(route('admin.course.homepage-layout-settings.update'), ['featured_courses_desktop_columns' => 4])
        ->assertForbidden();
});
