<?php

namespace App\Http\Requests\Admin;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

abstract class HomeHeroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'eyebrow' => ['required', 'string', 'max:255'],
            'heading_before_highlight' => ['required', 'string', 'max:5000'],
            'highlighted_text' => ['nullable', 'string', 'max:255'],
            'heading_after_highlight' => ['nullable', 'string', 'max:5000'],
            'description' => ['nullable', 'string', 'max:10000'],
            'hero_image' => [$this->imageRule(), 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'primary_button_text' => ['nullable', 'string', 'max:255'],
            'primary_button_url' => ['nullable', 'string', 'max:2048', $this->safeUrlRule()],
            'secondary_button_text' => ['nullable', 'string', 'max:255'],
            'secondary_button_url' => ['nullable', 'string', 'max:2048', $this->safeUrlRule()],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $values = [];

        foreach ([
            'eyebrow', 'heading_before_highlight', 'highlighted_text', 'heading_after_highlight',
            'description', 'image_alt', 'primary_button_text', 'primary_button_url',
            'secondary_button_text', 'secondary_button_url',
        ] as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);
                $values[$field] = is_string($value) ? trim($value) : $value;
            }
        }

        foreach (['highlighted_text', 'heading_after_highlight', 'description', 'image_alt', 'primary_button_text', 'primary_button_url', 'secondary_button_text', 'secondary_button_url'] as $field) {
            if (($values[$field] ?? null) === '') {
                $values[$field] = null;
            }
        }

        $this->merge($values);
    }

    abstract protected function imageRule(): string;

    private function safeUrlRule(): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || $value === '') {
                return;
            }

            if (preg_match('/[\x00-\x20\x7f\\\\]/', $value)) {
                $fail('The '.$attribute.' must be a relative path or an HTTP(S) URL.');

                return;
            }

            if (str_starts_with($value, '/')) {
                if (str_starts_with($value, '//')) {
                    $fail('The '.$attribute.' must be a relative path or an HTTP(S) URL.');
                }

                return;
            }

            $scheme = parse_url($value, PHP_URL_SCHEME);

            if (! in_array(strtolower((string) $scheme), ['http', 'https'], true)
                || filter_var($value, FILTER_VALIDATE_URL) === false) {
                $fail('The '.$attribute.' must be a relative path or an HTTP(S) URL.');
            }
        };
    }
}
