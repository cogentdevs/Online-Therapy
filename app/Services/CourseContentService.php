<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseDetailItem;
use App\Models\CourseSection;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class CourseContentService
{
    /** @param array<string, mixed> $validated */
    public function sync(Course $course, array $validated): void
    {
        if (array_key_exists('learning_outcomes', $validated)) {
            $this->syncDetailItems($course, CourseDetailItem::LEARNING_OUTCOME, $validated['learning_outcomes']);
        }
        if (array_key_exists('target_audiences', $validated)) {
            $this->syncDetailItems($course, CourseDetailItem::TARGET_AUDIENCE, $validated['target_audiences']);
        }
        if (array_key_exists('requirements', $validated)) {
            $this->syncDetailItems($course, CourseDetailItem::REQUIREMENT, $validated['requirements']);
        }
        if (array_key_exists('sections', $validated)) {
            $this->syncSections($course, $validated['sections']);
        }
        if (array_key_exists('faqs', $validated)) {
            $this->syncFaqs($course, $validated['faqs']);
        }
    }

    /** @param array<int, array<string, mixed>> $items */
    private function syncDetailItems(Course $course, string $type, array $items): void
    {
        $retainedIds = [];
        foreach (array_values($items) as $sortOrder => $item) {
            $itemId = Arr::get($item, 'id');
            $attributes = ['content' => trim((string) Arr::get($item, 'content')), 'sort_order' => $sortOrder];
            if ($itemId !== null) {
                $detailItem = $course->detailItems()->where('type', $type)->whereKey($itemId)->first();
                if ($detailItem === null) {
                    throw ValidationException::withMessages(["{$type}.{$sortOrder}.id" => 'The selected Course detail item does not belong to this Course.']);
                }
                $detailItem->update($attributes);
                $retainedIds[] = $detailItem->id;

                continue;
            }
            $retainedIds[] = $course->detailItems()->create([...$attributes, 'type' => $type])->id;
        }
        $course->detailItems()->where('type', $type)->whereNotIn('id', $retainedIds)->delete();
    }

    /** @param array<int, array<string, mixed>> $sections */
    private function syncSections(Course $course, array $sections): void
    {
        $retainedSectionIds = [];
        foreach (array_values($sections) as $sortOrder => $submittedSection) {
            $sectionId = Arr::get($submittedSection, 'id');
            $attributes = [
                'title' => trim((string) Arr::get($submittedSection, 'title')),
                'description' => filled(Arr::get($submittedSection, 'description')) ? Arr::get($submittedSection, 'description') : null,
                'sort_order' => $sortOrder,
            ];
            if ($sectionId !== null) {
                $section = $course->sections()->whereKey($sectionId)->first();
                if ($section === null) {
                    throw ValidationException::withMessages(["sections.{$sortOrder}.id" => 'The selected Course section does not belong to this Course.']);
                }
                $section->update($attributes);
            } else {
                $section = $course->sections()->create($attributes);
            }
            $retainedSectionIds[] = $section->id;
            $this->syncLessons($section, Arr::get($submittedSection, 'lessons', []), $sortOrder);
        }
        $course->sections()->whereNotIn('id', $retainedSectionIds)->delete();
    }

    /** @param array<int, array<string, mixed>> $lessons */
    private function syncLessons(CourseSection $section, array $lessons, int $sectionIndex): void
    {
        $retainedLessonIds = [];
        foreach (array_values($lessons) as $sortOrder => $submittedLesson) {
            $lessonId = Arr::get($submittedLesson, 'id');
            $attributes = [
                'title' => trim((string) Arr::get($submittedLesson, 'title')),
                'description' => filled(Arr::get($submittedLesson, 'description')) ? Arr::get($submittedLesson, 'description') : null,
                'lesson_type' => Arr::get($submittedLesson, 'lesson_type'),
                'duration_minutes' => Arr::get($submittedLesson, 'duration_minutes'),
                'sort_order' => $sortOrder,
            ];
            if ($lessonId !== null) {
                $lesson = $section->lessons()->whereKey($lessonId)->first();
                if ($lesson === null) {
                    throw ValidationException::withMessages(["sections.{$sectionIndex}.lessons.{$sortOrder}.id" => 'The selected lesson does not belong to this Course section.']);
                }
                $lesson->update($attributes);
            } else {
                $lesson = $section->lessons()->create($attributes);
            }
            $retainedLessonIds[] = $lesson->id;
        }
        $section->lessons()->whereNotIn('id', $retainedLessonIds)->delete();
    }

    /** @param array<int, array<string, mixed>> $faqs */
    private function syncFaqs(Course $course, array $faqs): void
    {
        $retainedFaqIds = [];
        foreach (array_values($faqs) as $sortOrder => $submittedFaq) {
            $faqId = Arr::get($submittedFaq, 'id');
            $attributes = ['question' => trim((string) Arr::get($submittedFaq, 'question')), 'answer' => trim((string) Arr::get($submittedFaq, 'answer')), 'sort_order' => $sortOrder];
            if ($faqId !== null) {
                $faq = $course->faqs()->whereKey($faqId)->first();
                if ($faq === null) {
                    throw ValidationException::withMessages(["faqs.{$sortOrder}.id" => 'The selected Course FAQ does not belong to this Course.']);
                }
                $faq->update($attributes);
            } else {
                $faq = $course->faqs()->create($attributes);
            }
            $retainedFaqIds[] = $faq->id;
        }
        $course->faqs()->whereNotIn('id', $retainedFaqIds)->delete();
    }
}
