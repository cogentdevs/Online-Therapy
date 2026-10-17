<?php

namespace App\Models;

use App\Models\Concerns\HasAuditOwnership;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['language', 'eyebrow', 'heading', 'description_one', 'description_two', 'is_active', 'cards_per_row'])]
class ThriveFrameworkSection extends Model
{
    use HasAuditOwnership;

    protected $attributes = ['is_active' => true, 'cards_per_row' => 3];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'cards_per_row' => 'integer'];
    }

    public function stages(): HasMany
    {
        return $this->hasMany(ThriveFrameworkStage::class, 'section_id')->orderBy('sort_order')->orderBy('id');
    }
}
