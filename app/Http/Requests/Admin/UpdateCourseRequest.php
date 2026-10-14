<?php

namespace App\Http\Requests\Admin;

use App\Models\Course;
use App\Models\CourseLesson;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'button_label' => ['required', 'string', 'max:100'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'short_description' => ['nullable', 'string'], 'description' => ['nullable', 'string'],
            'duration_type' => ['required', 'string', Rule::in(array_keys(Course::DURATION_TYPES))],
            'duration_value' => ['required', 'integer', 'min:1'], 'isActive' => ['required', 'boolean'], 'isFeatured' => ['required', 'boolean'],
            'related_course_ids' => ['nullable', 'array'],
            'related_course_ids.*' => ['integer', 'distinct', Rule::exists((new Course)->getTable(), 'id'), Rule::notIn([(int) $this->route('id')])],
            'level' => ['nullable', 'string', Rule::in(Course::LEVELS)],
            'instructor_name' => ['nullable', 'string', 'max:255'],
            'instructor_bio' => ['nullable', 'string', 'max:5000'],
            'instructor_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'certificate_available' => ['nullable', 'boolean'],
            'access_type' => ['nullable', 'string', Rule::in(Course::ACCESS_TYPES)],
            'access_description' => ['nullable', 'string', 'max:5000'],
            'learning_outcomes' => ['nullable', 'array', 'max:50'],
            'learning_outcomes_present' => ['nullable', 'boolean'],
            'learning_outcomes.*.id' => ['nullable', 'integer'],
            'learning_outcomes.*.content' => ['required', 'string', 'max:1000'],
            'target_audiences' => ['nullable', 'array', 'max:50'],
            'target_audiences_present' => ['nullable', 'boolean'],
            'target_audiences.*.id' => ['nullable', 'integer'],
            'target_audiences.*.content' => ['required', 'string', 'max:1000'],
            'requirements' => ['nullable', 'array', 'max:50'],
            'requirements_present' => ['nullable', 'boolean'],
            'requirements.*.id' => ['nullable', 'integer'],
            'requirements.*.content' => ['required', 'string', 'max:1000'],
            'sections' => ['nullable', 'array', 'max:30'],
            'sections_present' => ['nullable', 'boolean'],
            'sections.*.id' => ['nullable', 'integer'],
            'sections.*.title' => ['required', 'string', 'max:255'],
            'sections.*.description' => ['nullable', 'string', 'max:5000'],
            'sections.*.lessons' => ['nullable', 'array', 'max:100'],
            'sections.*.lessons.*.id' => ['nullable', 'integer'],
            'sections.*.lessons.*.title' => ['required', 'string', 'max:255'],
            'sections.*.lessons.*.description' => ['nullable', 'string', 'max:5000'],
            'sections.*.lessons.*.lesson_type' => ['nullable', 'string', Rule::in(CourseLesson::TYPES)],
            'sections.*.lessons.*.duration_minutes' => ['nullable', 'integer', 'min:0'],
            'faqs' => ['nullable', 'array', 'max:50'],
            'faqs_present' => ['nullable', 'boolean'],
            'faqs.*.id' => ['nullable', 'integer'],
            'faqs.*.question' => ['required', 'string', 'max:1000'],
            'faqs.*.answer' => ['required', 'string', 'max:10000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $description = (string) $this->input('description', '');
        $plainDescription = trim(str_replace("\u{00A0}", ' ', html_entity_decode(strip_tags($description), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        $normalized = ['description' => $plainDescription === '' ? '' : $description];
        foreach (['learning_outcomes', 'target_audiences', 'requirements', 'sections', 'faqs'] as $collection) {
            if ($this->has("{$collection}_present") && ! $this->has($collection)) {
                $normalized[$collection] = [];
            }
        }
        $this->merge($normalized);
    }
}
