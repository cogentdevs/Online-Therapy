<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreThriveFrameworkRequest;
use App\Http\Requests\Admin\UpdateThriveFrameworkLayoutRequest;
use App\Http\Requests\Admin\UpdateThriveFrameworkRequest;
use App\Models\ThriveFrameworkSection;
use App\Models\ThriveFrameworkStage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ThriveFrameworkController extends Controller
{
    private const FIELDS = ['eyebrow', 'heading', 'description_one', 'description_two', 'is_active'];

    private const SETUP_WARNING = 'THRIVE Framework setup is incomplete. Please ask your site administrator to finish setup before managing this section.';

    public function index(): View
    {
        $tablesAvailable = $this->tablesAvailable();
        $sections = $tablesAvailable
            ? ThriveFrameworkSection::query()->where('language', config('content_language.code'))->withCount('stages')->latest('id')->get()
            : collect();

        return view('admin.thrive-framework.index', [
            'sections' => $sections,
            'tablesAvailable' => $tablesAvailable,
            'setupWarning' => $tablesAvailable ? null : self::SETUP_WARNING,
        ]);
    }

    public function homepageLayoutSettings(): View
    {
        $settingsAvailable = $this->layoutSettingAvailable();
        $section = $settingsAvailable
            ? ThriveFrameworkSection::query()->where('language', config('content_language.code'))->first()
            : null;

        return view('admin.thrive-framework.homepage-layout-settings', [
            'settingsAvailable' => $settingsAvailable,
            'section' => $section,
            'desktopColumns' => $section?->cards_per_row ?? 3,
        ]);
    }

    public function updateHomepageLayoutSettings(UpdateThriveFrameworkLayoutRequest $request): RedirectResponse
    {
        if (! $this->layoutSettingAvailable()) {
            return to_route('admin.thrive-framework.homepage-layout-settings')->with('error', 'Complete the THRIVE Framework layout setup before saving this setting.');
        }

        $updated = DB::transaction(function () use ($request): bool {
            $section = ThriveFrameworkSection::query()->where('language', config('content_language.code'))->lockForUpdate()->first();
            if ($section === null) {
                return false;
            }
            $section->update($request->validated());

            return true;
        });

        return to_route('admin.thrive-framework.homepage-layout-settings')
            ->with($updated ? 'status' : 'warning', $updated ? 'Homepage layout settings updated successfully.' : 'Add a THRIVE Framework section before saving its layout settings.');
    }

    private function layoutSettingAvailable(): bool
    {
        return Schema::hasTable((new ThriveFrameworkSection)->getTable())
            && Schema::hasColumn((new ThriveFrameworkSection)->getTable(), 'cards_per_row');
    }

    public function create(): View|RedirectResponse
    {
        if (! $this->tablesAvailable()) {
            return to_route('admin.thrive-framework.index');
        }
        if ($this->sectionExists()) {
            return to_route('admin.thrive-framework.index')->with('warning', 'Only one THRIVE Framework is allowed for the configured language.');
        }

        return view('admin.thrive-framework.create', ['section' => null]);
    }

    public function store(StoreThriveFrameworkRequest $request): RedirectResponse
    {
        if (! $this->tablesAvailable()) {
            return to_route('admin.thrive-framework.index');
        }
        $validated = $request->validated();
        try {
            DB::transaction(function () use ($validated): void {
                if ($this->sectionExists()) {
                    throw ValidationException::withMessages(['eyebrow' => 'A THRIVE Framework already exists for the configured language.']);
                }
                $section = ThriveFrameworkSection::query()->create([
                    ...Arr::only($validated, self::FIELDS), 'language' => config('content_language.code'),
                ]);
                if (array_key_exists('stages', $validated)) {
                    $this->syncStages($section, $validated['stages']);
                }
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (! $this->sectionExists()) {
                throw $exception;
            }
            throw ValidationException::withMessages(['eyebrow' => 'A THRIVE Framework already exists for the configured language.']);
        }

        return to_route('admin.thrive-framework.index')->with('status', 'THRIVE Framework added successfully.');
    }

    public function show(int $id): View|RedirectResponse
    {
        if (! $this->tablesAvailable()) {
            return to_route('admin.thrive-framework.index');
        }

        return view('admin.thrive-framework.show', ['section' => $this->sectionById($id)->load('stages')]);
    }

    public function edit(int $id): View|RedirectResponse
    {
        if (! $this->tablesAvailable()) {
            return to_route('admin.thrive-framework.index');
        }

        return view('admin.thrive-framework.edit', ['section' => $this->sectionById($id)->load('stages')]);
    }

    public function update(UpdateThriveFrameworkRequest $request, int $id): RedirectResponse
    {
        if (! $this->tablesAvailable()) {
            return to_route('admin.thrive-framework.index');
        }
        $validated = $request->validated();
        DB::transaction(function () use ($id, $validated): void {
            $section = $this->sectionById($id, lock: true);
            $section->update(Arr::only($validated, self::FIELDS));
            if (array_key_exists('stages', $validated)) {
                $this->syncStages($section, $validated['stages']);
            }
        });

        return to_route('admin.thrive-framework.index')->with('status', 'THRIVE Framework updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        if (! $this->tablesAvailable()) {
            return to_route('admin.thrive-framework.index');
        }
        DB::transaction(function () use ($id): void {
            $section = $this->sectionById($id, lock: true);
            $section->stages()->get()->each->delete();
            $section->delete();
        });

        return to_route('admin.thrive-framework.index')->with('status', 'THRIVE Framework deleted successfully.');
    }

    /** @param array<int, array<string, mixed>> $stages */
    private function syncStages(ThriveFrameworkSection $section, array $stages): void
    {
        $retainedIds = [];
        foreach ($stages as $index => $submittedStage) {
            $sortOrder = count($retainedIds);
            $attributes = Arr::only($submittedStage, ['letter', 'stage_label', 'title', 'description', 'is_active']);
            $attributes['sort_order'] = $sortOrder;
            $stageId = $submittedStage['id'] ?? null;
            if ($stageId !== null) {
                $stage = $section->stages()->whereKey($stageId)->first();
                if ($stage === null) {
                    throw ValidationException::withMessages(["stages.{$index}.id" => 'This stage does not belong to the selected framework.']);
                }
                $stage->update($attributes);
            } else {
                $stage = $section->stages()->create($attributes);
            }
            $retainedIds[] = $stage->id;
        }
        $section->stages()->whereNotIn('id', $retainedIds)->get()->each->delete();
    }

    private function sectionById(int $id, bool $lock = false): ThriveFrameworkSection
    {
        $query = ThriveFrameworkSection::query()->where('language', config('content_language.code'));
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->findOrFail($id);
    }

    private function sectionExists(): bool
    {
        return ThriveFrameworkSection::query()->where('language', config('content_language.code'))->exists();
    }

    private function tablesAvailable(): bool
    {
        return Schema::hasTable((new ThriveFrameworkSection)->getTable())
            && Schema::hasTable((new ThriveFrameworkStage)->getTable());
    }
}
