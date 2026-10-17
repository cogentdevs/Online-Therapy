<?php

namespace App\Models;

use App\Models\Concerns\HasAuditOwnership;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'language',
    'eyebrow',
    'heading_before_highlight',
    'highlighted_text',
    'heading_after_highlight',
    'description',
    'hero_image',
    'image_alt',
    'primary_button_text',
    'primary_button_url',
    'secondary_button_text',
    'secondary_button_url',
    'is_active',
])]
class HomeHeroSection extends Model
{
    use HasAuditOwnership;

    protected $attributes = ['is_active' => true];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
