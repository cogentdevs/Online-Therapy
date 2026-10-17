<?php

namespace App\Models;

use App\Models\Concerns\HasAuditOwnership;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['language', 'eyebrow', 'heading', 'description', 'bottom_message', 'bottom_button_text', 'bottom_button_url', 'cards_per_row', 'is_active'])]
class WorkTogetherSection extends Model
{
    use HasAuditOwnership;

    protected $attributes = ['cards_per_row' => 3, 'is_active' => true];

    protected function casts(): array
    {
        return ['cards_per_row' => 'integer', 'is_active' => 'boolean'];
    }

    public function cards(): HasMany
    {
        return $this->hasMany(WorkTogetherCard::class, 'section_id')->orderBy('sort_order')->orderBy('id');
    }
}
