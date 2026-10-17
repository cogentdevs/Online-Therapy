<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['section_id', 'title', 'description', 'additional_details', 'button_text', 'button_url', 'layout_type', 'sort_order', 'is_active'])]
class WorkTogetherCard extends Model
{
    protected $attributes = ['layout_type' => 'standard', 'sort_order' => 0, 'is_active' => true];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_active' => 'boolean'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(WorkTogetherSection::class, 'section_id');
    }
}
