<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateThriveFrameworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'eyebrow' => ['required', 'string', 'max:255'],
            'heading' => ['required', 'string', 'max:255'],
            'description_one' => ['nullable', 'string'],
            'description_two' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
            'stages_present' => $this->exists('stages') ? ['required', 'accepted'] : ['sometimes', 'accepted'],
            'stages' => ['sometimes', 'array'],
            'stages.*' => ['array:id,letter,stage_label,title,description,is_active'],
            'stages.*.id' => ['nullable', 'integer', 'distinct'],
            'stages.*.letter' => ['required', 'string', 'max:10'],
            'stages.*.stage_label' => ['nullable', 'string', 'max:50'],
            'stages.*.title' => ['required', 'string', 'max:255'],
            'stages.*.description' => ['nullable', 'string'],
            'stages.*.is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (in_array($this->input('stages_present'), ['1', 1, true], true) && ! $this->exists('stages')) {
            $this->merge(['stages' => []]);
        }
        if (! is_array($this->input('stages'))) {
            return;
        }
        $stages = [];
        foreach ($this->input('stages') as $index => $stage) {
            if (is_array($stage)) {
                foreach (['letter', 'stage_label', 'title', 'description'] as $field) {
                    if (isset($stage[$field]) && is_string($stage[$field])) {
                        $stage[$field] = trim($stage[$field]);
                    }
                }
                $hasContent = collect(['id', 'letter', 'stage_label', 'title', 'description'])
                    ->contains(fn (string $field): bool => filled($stage[$field] ?? null));
                if (! $hasContent && collect(array_keys($stage))->diff(['id', 'letter', 'stage_label', 'title', 'description', 'is_active'])->isEmpty()) {
                    continue;
                }
            }
            $stages[$index] = $stage;
        }
        $this->merge(['stages' => $stages]);
    }

    public function messages(): array
    {
        return [
            'stages.*.letter.required' => 'Each populated stage needs a letter.',
            'stages.*.title.required' => 'Each populated stage needs a title.',
        ];
    }
}
