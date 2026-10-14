<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['course_id', 'type', 'content', 'sort_order'])]
class CourseDetailItem extends Model
{
    public const LEARNING_OUTCOME = 'learning_outcome';

    public const TARGET_AUDIENCE = 'target_audience';

    public const REQUIREMENT = 'requirement';

    public const TYPES = [self::LEARNING_OUTCOME, self::TARGET_AUDIENCE, self::REQUIREMENT];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
