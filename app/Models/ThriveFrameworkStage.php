<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['section_id', 'letter', 'stage_label', 'title', 'description', 'sort_order', 'is_active'])]
class ThriveFrameworkStage extends Model
{
    protected $attributes = ['sort_order' => 0, 'is_active' => true];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ThriveFrameworkSection::class, 'section_id');
    }
}
