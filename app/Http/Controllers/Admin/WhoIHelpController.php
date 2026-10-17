<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWhoIHelpRequest;
use App\Http\Requests\Admin\UpdateWhoIHelpRequest;
use App\Models\WhoIHelpSection;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WhoIHelpController extends Controller
{
    public function index(): View
    {
        $sections = WhoIHelpSection::query()
            ->where('language', config('content_language.code'))
            ->withCount('items')
            ->latest('id')
            ->get();

        return view('admin.who-i-help.index', compact('sections'));
    }

    public function create(): View|RedirectResponse
    {
        if ($this->sectionExists()) {
            return to_route('admin.who-i-help.index')
                ->with('warning', 'Only one Who I Help section can exist for the configured language.');
        }

        return view('admin.who-i-help.create', [
            'section' => null,
            'defaultSection' => $this->defaultSection(),
            'defaultItems' => $this->defaultItems(),
        ]);
    }

    public function store(StoreWhoIHelpRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated): void {
                if ($this->sectionExists(lock: true)) {
                    throw ValidationException::withMessages([
                        'eyebrow' => 'A Who I Help section already exists for the configured language.',
                    ]);
                }

                $section = WhoIHelpSection::query()->create([
                    ...Arr::only($validated, ['eyebrow', 'heading', 'description', 'bottom_message', 'is_active']),
                    'language' => config('content_language.code'),
                ]);

                if (array_key_exists('items', $validated) && is_array($validated['items'])) {
                    $this->syncItems($section, $validated['items']);
                }
            });
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['19', '23000'], true)) {
                return back()->withInput()->withErrors([
                    'eyebrow' => 'A Who I Help section already exists for the configured language.',
                ]);
            }

            throw $exception;
        }

        return to_route('admin.who-i-help.index')->with('status', 'Who I Help section added successfully.');
    }

    public function show(int $id): View
    {
        return view('admin.who-i-help.show', [
            'section' => $this->sectionById($id)->load(['items', 'creator.roles', 'updater.roles']),
        ]);
    }

    public function edit(int $id): View
    {
        return view('admin.who-i-help.edit', [
            'section' => $this->sectionById($id)->load('items'),
            'defaultSection' => $this->defaultSection(),
            'defaultItems' => [],
        ]);
    }

    public function update(UpdateWhoIHelpRequest $request, int $id): RedirectResponse
    {
        $validated = $request->validated();
        $section = $this->sectionById($id);

        DB::transaction(function () use ($section, $validated): void {
            $section->update(Arr::only($validated, [
                'eyebrow',
                'heading',
                'description',
                'bottom_message',
                'is_active',
            ]));

            if (array_key_exists('items', $validated) && is_array($validated['items'])) {
                $this->syncItems($section, $validated['items']);
            }
        });

        return to_route('admin.who-i-help.index')->with('status', 'Who I Help section updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $section = $this->sectionById($id)->load('items');

        DB::transaction(function () use ($section): void {
            $section->items->each->delete();
            $section->delete();
        });

        return to_route('admin.who-i-help.index')->with('status', 'Who I Help section deleted successfully.');
    }

    /** @param array<int, array<string, mixed>> $items */
    private function syncItems(WhoIHelpSection $section, array $items): void
    {
        $retainedItemIds = [];

        foreach (array_values($items) as $sortOrder => $submittedItem) {
            $itemId = Arr::get($submittedItem, 'id');
            $title = trim((string) Arr::get($submittedItem, 'title'));

            if ($title === '') {
                continue;
            }

            if ($itemId !== null) {
                $item = $section->items()->whereKey($itemId)->first();

                if ($item === null) {
                    throw ValidationException::withMessages([
                        "items.{$sortOrder}.id" => 'The selected Help Card does not belong to this Who I Help section.',
                    ]);
                }

                $attributes = ['title' => $title, 'sort_order' => $sortOrder];

                if (Arr::has($submittedItem, 'is_active') && Arr::get($submittedItem, 'is_active') !== null) {
                    $attributes['is_active'] = (bool) Arr::get($submittedItem, 'is_active');
                }

                $item->update($attributes);
            } else {
                $item = $section->items()->create([
                    'title' => $title,
                    'is_active' => Arr::has($submittedItem, 'is_active')
                        ? (bool) Arr::get($submittedItem, 'is_active')
                        : true,
                    'sort_order' => $sortOrder,
                ]);
            }

            $retainedItemIds[] = $item->id;
        }

        $section->items()
            ->whereNotIn('id', $retainedItemIds)
            ->get()
            ->each->delete();
    }

    private function sectionById(int $id): WhoIHelpSection
    {
        return WhoIHelpSection::query()
            ->where('language', config('content_language.code'))
            ->findOrFail($id);
    }

    private function sectionExists(bool $lock = false): bool
    {
        $query = WhoIHelpSection::query()->where('language', config('content_language.code'));

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->exists();
    }

    /** @return array<string, string|bool> */
    private function defaultSection(): array
    {
        return [
            'eyebrow' => 'WHO I HELP',
            'heading' => "When Life Feels Overwhelming, It's Time to Understand Yourself.",
            'description' => 'I support women who want to understand their emotions, work through unhelpful patterns, and build greater self-trust, confidence, and emotional resilience.',
            'bottom_message' => "You don't have to figure everything out on your own. Support can be the beginning of change.",
            'is_active' => true,
        ];
    }

    /** @return list<array{title: string, is_active: bool}> */
    private function defaultItems(): array
    {
        return [
            ['title' => "Overthinking and a mind that won't switch off", 'is_active' => true],
            ['title' => 'Perfectionism and fear of making mistakes', 'is_active' => true],
            ['title' => 'People-pleasing and difficulty setting boundaries', 'is_active' => true],
            ['title' => 'Self-doubt despite trying your best', 'is_active' => true],
            ['title' => 'Emotional overwhelm and feeling stretched too thin', 'is_active' => true],
            ['title' => 'Repeating emotional patterns you want to change', 'is_active' => true],
        ];
    }
}
