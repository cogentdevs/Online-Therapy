<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWorkTogetherRequest;
use App\Http\Requests\Admin\UpdateWorkTogetherLayoutRequest;
use App\Http\Requests\Admin\UpdateWorkTogetherRequest;
use App\Models\WorkTogetherCard;
use App\Models\WorkTogetherSection;
use Illuminate\Contracts\View\View;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class WorkTogetherController extends Controller
{
    private const FIELDS = ['eyebrow', 'heading', 'description', 'bottom_message', 'bottom_button_text', 'bottom_button_url', 'is_active'];

    private const SETUP_WARNING = 'How We Can Work Together setup is incomplete. Please ask your site administrator to finish setup before managing this section.';

    public function index(): View
    {
        $tablesAvailable = $this->tablesAvailable();
        $sections = $tablesAvailable
            ? WorkTogetherSection::query()->where('language', config('content_language.code'))->withCount('cards')->latest('id')->get()
            : collect();

        return view('admin.work-together.index', [
            'sections' => $sections,
            'tablesAvailable' => $tablesAvailable,
            'setupWarning' => $tablesAvailable ? null : self::SETUP_WARNING,
        ]);
    }

    public function homepageLayoutSettings(): View
    {
        $settingsAvailable = $this->layoutSettingAvailable();
        $section = $settingsAvailable
            ? WorkTogetherSection::query()->where('language', config('content_language.code'))->first()
            : null;

        return view('admin.work-together.homepage-layout-settings', [
            'settingsAvailable' => $settingsAvailable,
            'section' => $section,
            'desktopColumns' => $section?->cards_per_row ?? 3,
        ]);
    }

    public function updateHomepageLayoutSettings(UpdateWorkTogetherLayoutRequest $request): RedirectResponse
    {
        if (! $this->layoutSettingAvailable()) {
            return to_route('admin.work-together.homepage-layout-settings')->with('error', 'Layout setup is incomplete: the sections table and cards_per_row column are required.');
        }
        $updated = DB::transaction(function () use ($request): bool {
            $section = WorkTogetherSection::query()->where('language', config('content_language.code'))->lockForUpdate()->first();
            if ($section === null) {
                return false;
            }
            $section->update($request->safe()->only('cards_per_row'));

            return true;
        });

        return to_route('admin.work-together.homepage-layout-settings')->with(
            $updated ? 'status' : 'warning',
            $updated ? 'Homepage layout settings saved successfully.' : 'Add a How We Can Work Together section before saving its layout settings.',
        );
    }

    private function layoutSettingAvailable(): bool
    {
        $table = (new WorkTogetherSection)->getTable();

        return Schema::hasTable($table) && Schema::hasColumn($table, 'cards_per_row');
    }

    public function create(): View|RedirectResponse
    {
        if (! $this->tablesAvailable()) {
            return to_route('admin.work-together.index');
        }
        if ($this->sectionExists()) {
            return to_route('admin.work-together.index')->with('warning', 'Only one How We Can Work Together section is allowed for the configured language.');
        }

        return view('admin.work-together.create', ['section' => null]);
    }

    public function store(StoreWorkTogetherRequest $request): RedirectResponse
    {
        if (! $this->tablesAvailable()) {
            return to_route('admin.work-together.index');
        }
        $validated = $request->validated();
        try {
            DB::transaction(function () use ($validated): void {
                if ($this->sectionExists()) {
                    throw ValidationException::withMessages(['eyebrow' => 'A section already exists for the configured language.']);
                }
                $section = WorkTogetherSection::query()->create([
                    ...Arr::only($validated, self::FIELDS), 'language' => config('content_language.code'),
                ]);
                if (array_key_exists('cards', $validated)) {
                    $this->syncCards($section, $validated['cards']);
                }
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (! $this->sectionExists()) {
                throw $exception;
            }
            throw ValidationException::withMessages(['eyebrow' => 'A section already exists for the configured language.']);
        }

        return to_route('admin.work-together.index')->with('status', 'How We Can Work Together added successfully.');
    }

    public function show(int $id): View|RedirectResponse
    {
        if (! $this->tablesAvailable()) {
            return to_route('admin.work-together.index');
        }

        return view('admin.work-together.show', ['section' => $this->sectionById($id)->load('cards')]);
    }

    public function edit(int $id): View|RedirectResponse
    {
        if (! $this->tablesAvailable()) {
            return to_route('admin.work-together.index');
        }

        return view('admin.work-together.edit', ['section' => $this->sectionById($id)->load('cards')]);
    }

    public function update(UpdateWorkTogetherRequest $request, int $id): RedirectResponse
    {
        if (! $this->tablesAvailable()) {
            return to_route('admin.work-together.index');
        }
        $validated = $request->validated();
        DB::transaction(function () use ($id, $validated): void {
            $section = $this->sectionById($id, lock: true);
            $section->update(Arr::only($validated, self::FIELDS));
            if (array_key_exists('cards', $validated)) {
                $this->syncCards($section, $validated['cards']);
            }
        });

        return to_route('admin.work-together.index')->with('status', 'How We Can Work Together updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        if (! $this->tablesAvailable()) {
            return to_route('admin.work-together.index');
        }
        DB::transaction(function () use ($id): void {
            $section = $this->sectionById($id, lock: true);
            $section->cards()->get()->each->delete();
            $section->delete();
        });

        return to_route('admin.work-together.index')->with('status', 'How We Can Work Together deleted successfully.');
    }

    /** @param array<int, array<string, mixed>> $cards */
    private function syncCards(WorkTogetherSection $section, array $cards): void
    {
        $retainedIds = [];
        foreach ($cards as $index => $submittedCard) {
            $sortOrder = count($retainedIds);
            $attributes = Arr::only($submittedCard, ['title', 'description', 'additional_details', 'button_text', 'button_url', 'layout_type', 'is_active']);
            $attributes['sort_order'] = $sortOrder;
            $cardId = $submittedCard['id'] ?? null;
            if ($cardId !== null) {
                $card = $section->cards()->whereKey($cardId)->first();
                if ($card === null) {
                    throw ValidationException::withMessages(["cards.{$index}.id" => 'This card does not belong to the selected section.']);
                }
                $card->update($attributes);
            } else {
                $card = $section->cards()->create($attributes);
            }
            $retainedIds[] = $card->id;
        }
        $section->cards()->whereNotIn('id', $retainedIds)->get()->each->delete();
    }

    private function sectionById(int $id, bool $lock = false): WorkTogetherSection
    {
        $query = WorkTogetherSection::query()->where('language', config('content_language.code'));
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->findOrFail($id);
    }

    private function sectionExists(): bool
    {
        return WorkTogetherSection::query()->where('language', config('content_language.code'))->exists();
    }

    private function tablesAvailable(): bool
    {
        return Schema::hasTable((new WorkTogetherSection)->getTable())
            && Schema::hasTable((new WorkTogetherCard)->getTable());
    }
}
