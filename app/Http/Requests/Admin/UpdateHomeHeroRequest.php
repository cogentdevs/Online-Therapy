<?php

namespace App\Http\Requests\Admin;

class UpdateHomeHeroRequest extends HomeHeroRequest
{
    protected function imageRule(): string
    {
        return 'nullable';
    }
}
