<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['section_id', 'title', 'sort_order', 'is_active'])]
class WhoIHelpItem extends Model
{
    protected $attributes = ['is_active' => true, 'sort_order' => 0];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(WhoIHelpSection::class, 'section_id');
    }
}
