<?php

namespace App\Http\Requests\Admin;

class StoreHomeHeroRequest extends HomeHeroRequest
{
    protected function imageRule(): string
    {
        return 'nullable';
    }
}
