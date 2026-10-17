<?php

namespace App\Models;

use App\Models\Concerns\HasAuditOwnership;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['language', 'eyebrow', 'heading', 'description', 'bottom_message', 'is_active'])]
class WhoIHelpSection extends Model
{
    use HasAuditOwnership;

    protected $attributes = ['is_active' => true];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(WhoIHelpItem::class, 'section_id')->orderBy('sort_order')->orderBy('id');
    }
}
