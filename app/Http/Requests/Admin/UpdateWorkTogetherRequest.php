<?php

namespace App\Http\Requests\Admin;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkTogetherRequest extends FormRequest
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
            'description' => ['nullable', 'string', 'max:10000'],
            'bottom_message' => ['nullable', 'string', 'max:10000'],
            'bottom_button_text' => ['nullable', 'string', 'max:255'],
            'bottom_button_url' => ['nullable', 'string', 'max:2048', $this->safeUrlRule()],
            'is_active' => ['required', 'boolean'],
            'cards_present' => $this->exists('cards') ? ['required', 'accepted'] : ['sometimes', 'accepted'],
            'cards' => ['sometimes', 'array'],
            'cards.*' => ['array:id,title,description,additional_details,button_text,button_url,layout_type,is_active'],
            'cards.*.id' => ['nullable', 'integer', 'min:1', 'distinct'],
            'cards.*.title' => ['required', 'string', 'max:255'],
            'cards.*.description' => ['nullable', 'string', 'max:10000'],
            'cards.*.additional_details' => ['nullable', 'string', 'max:10000'],
            'cards.*.button_text' => ['nullable', 'string', 'max:255'],
            'cards.*.button_url' => ['nullable', 'string', 'max:2048', $this->safeUrlRule()],
            'cards.*.layout_type' => ['sometimes', 'string', 'in:standard,full_width'],
            'cards.*.is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->boolean('cards_present') && ! $this->exists('cards')) {
            $this->merge(['cards' => []]);
        }
        if (! is_array($this->input('cards'))) {
            return;
        }
        $cards = [];
        foreach ($this->input('cards') as $index => $card) {
            if (is_array($card)) {
                $fields = ['title', 'description', 'additional_details', 'button_text', 'button_url'];
                foreach ($fields as $field) {
                    if (isset($card[$field]) && is_string($card[$field])) {
                        $card[$field] = trim($card[$field]);
                    }
                }
                $hasContent = collect(['id', ...$fields])->contains(fn (string $field): bool => filled($card[$field] ?? null));
                $defaultLayout = ! array_key_exists('layout_type', $card) || $card['layout_type'] === 'standard';
                $defaultStatus = ! array_key_exists('is_active', $card) || in_array($card['is_active'], [1, '1', true], true);
                if (! $hasContent && $defaultLayout && $defaultStatus && collect(array_keys($card))->diff(['id', ...$fields, 'layout_type', 'is_active'])->isEmpty()) {
                    continue;
                }
            }
            $cards[$index] = $card;
        }
        $this->merge(['cards' => $cards]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'cards.*.title.required' => 'Each populated card needs a title.',
            'cards.*.layout_type.in' => 'Choose Standard or Full Width for this card.',
            'cards_present.required' => 'The cards submission marker is missing. Existing cards have been preserved.',
        ];
    }

    private function safeUrlRule(): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || $value === '') {
                return;
            }
            $unsafeCharacters = preg_match('/[\\x00-\\x20\\x7f\\\\\\\\]/', $value);
            $relative = str_starts_with($value, '/') && ! str_starts_with($value, '//');
            $absolute = in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true)
                && filter_var($value, FILTER_VALIDATE_URL) !== false;
            if ($unsafeCharacters || (! $relative && ! $absolute)) {
                $fail('The '.$attribute.' must be a relative path or an HTTP(S) URL.');
            }
        };
    }
}
