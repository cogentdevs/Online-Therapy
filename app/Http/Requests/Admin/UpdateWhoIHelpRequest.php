<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWhoIHelpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'eyebrow' => ['required', 'string', 'max:100'],
            'heading' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'bottom_message' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
            'items_present' => ['nullable', 'required_with:items', 'boolean'],
            'items' => ['nullable', 'array', 'max:100'],
            'items.*.id' => ['nullable', 'integer', 'distinct'],
            'items.*.title' => ['nullable', 'string', 'max:1000'],
            'items.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['eyebrow', 'heading', 'description', 'bottom_message'] as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);
                $normalized[$field] = is_string($value) ? trim($value) : $value;
            }
        }

        foreach (['description', 'bottom_message'] as $field) {
            if (($normalized[$field] ?? null) === '') {
                $normalized[$field] = null;
            }
        }

        if ($this->has('items_present') && ! $this->has('items')) {
            $normalized['items'] = [];
        }

        if (is_array($this->input('items'))) {
            $items = [];

            foreach ($this->input('items') as $item) {
                if (! is_array($item)) {
                    $items[] = $item;

                    continue;
                }

                $title = isset($item['title']) && is_string($item['title']) ? trim($item['title']) : ($item['title'] ?? null);

                // Empty new rows are ignored; empty existing rows are omitted so
                // synchronization safely removes only that existing card.
                if ($title === '' || $title === null) {
                    continue;
                }

                $item['title'] = $title;
                $items[] = $item;
            }

            $normalized['items'] = $items;
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }
}
