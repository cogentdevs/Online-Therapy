<?php

namespace App\Models;

use App\Models\Concerns\HasAuditOwnership;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['language', 'title', 'button_label', 'image', 'short_description', 'description', 'duration_type', 'duration_value', 'level', 'instructor_name', 'instructor_bio', 'instructor_image', 'certificate_available', 'access_type', 'access_description', 'isActive', 'isFeatured', 'status', 'published_at'])]
class Course extends Model
{
    use HasAuditOwnership;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    /** @var array<string, string> */
    public const DURATION_TYPES = ['minutes' => 'Minutes', 'hours' => 'Hours', 'days' => 'Days', 'weeks' => 'Weeks', 'months' => 'Months'];

    public const LEVELS = ['Beginner', 'Intermediate', 'Advanced'];

    public const ACCESS_TYPES = ['Lifetime', 'Limited', 'Contact for Details'];

    /** @var array<string, mixed> */
    protected $attributes = ['isActive' => true, 'isFeatured' => false, 'status' => self::STATUS_DRAFT];

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function ownerAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_admin_id');
    }

    public function relatedCourses(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'related_courses', 'course_id', 'related_course_id')->withTimestamps();
    }

    public function detailItems(): HasMany
    {
        return $this->hasMany(CourseDetailItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function learningOutcomes(): HasMany
    {
        return $this->detailItems()->where('type', CourseDetailItem::LEARNING_OUTCOME);
    }

    public function targetAudiences(): HasMany
    {
        return $this->detailItems()->where('type', CourseDetailItem::TARGET_AUDIENCE);
    }

    public function requirements(): HasMany
    {
        return $this->detailItems()->where('type', CourseDetailItem::REQUIREMENT);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(CourseSection::class)->orderBy('sort_order')->orderBy('id');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(CourseFaq::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopePubliclyAvailable(Builder $query): Builder
    {
        return $query
            ->where('isActive', true)
            ->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function formattedDuration(): string
    {
        $unit = self::DURATION_TYPES[$this->duration_type] ?? str($this->duration_type)->replace('_', ' ')->title()->toString();
        $formattedUnit = $this->duration_value === 1 ? str($unit)->singular()->toString() : str($unit)->plural()->toString();

        return $this->duration_value.' '.$formattedUnit;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['duration_value' => 'integer', 'certificate_available' => 'boolean', 'isActive' => 'boolean', 'isFeatured' => 'boolean', 'published_at' => 'datetime'];
    }
}
