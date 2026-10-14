<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['course_section_id', 'title', 'description', 'lesson_type', 'duration_minutes', 'sort_order'])]
class CourseLesson extends Model
{
    public const TYPES = ['Video', 'Reading', 'Practical', 'Assignment'];

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'course_section_id');
    }
}
