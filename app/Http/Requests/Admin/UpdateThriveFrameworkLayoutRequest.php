<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateThriveFrameworkLayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<string>> */
    public function rules(): array
    {
        return ['cards_per_row' => ['required', 'integer', 'in:3,4']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'cards_per_row.required' => 'Please select 3 or 4 cards per row.',
            'cards_per_row.integer' => 'Cards per row must be an integer.',
            'cards_per_row.in' => 'Please select either 3 or 4 cards per row.',
        ];
    }
}
