<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHomeHeroRequest;
use App\Http\Requests\Admin\UpdateHomeHeroRequest;
use App\Models\HomeHeroSection;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class HomeHeroController extends Controller
{
    private const IMAGE_DIRECTORY = 'images/backend-images/home-hero';

    private const SETUP_WARNING = 'Home Hero setup is incomplete. Please ask your site administrator to finish setup before managing this section.';

    public function index(): View
    {
        $heroTableAvailable = $this->heroTableExists();
        $setupWarning = $heroTableAvailable ? null : self::SETUP_WARNING;
        $heroes = $heroTableAvailable
            ? HomeHeroSection::query()
                ->where('language', config('content_language.code'))
                ->latest('id')
                ->get()
            : collect();

        return view('admin.home-hero.index', compact('heroes', 'heroTableAvailable', 'setupWarning'));
    }

    public function create(): View|RedirectResponse
    {
        if (! $this->heroTableExists()) {
            return to_route('admin.home-hero.index');
        }

        if ($this->heroExists()) {
            return to_route('admin.home-hero.index')
                ->with('warning', 'Only one Home Hero can exist for the configured language.');
        }

        return view('admin.home-hero.create', ['hero' => null]);
    }

    public function store(StoreHomeHeroRequest $request): RedirectResponse
    {
        if (! $this->heroTableExists()) {
            return to_route('admin.home-hero.index');
        }

        $validated = $request->validated();
        $imagePath = $request->hasFile('hero_image') ? $this->storeImage($request->file('hero_image')) : null;

        try {
            DB::transaction(function () use ($validated, $imagePath): void {
                if ($this->heroExists(lock: true)) {
                    throw ValidationException::withMessages([
                        'eyebrow' => 'A Home Hero already exists for the configured language.',
                    ]);
                }

                HomeHeroSection::query()->create([
                    ...Arr::except($validated, ['hero_image']),
                    'language' => config('content_language.code'),
                    'hero_image' => $imagePath,
                ]);
            });
        } catch (QueryException $exception) {
            $this->deleteManagedImage($imagePath);

            if (in_array((string) $exception->getCode(), ['19', '23000'], true)) {
                return back()->withInput()->withErrors([
                    'eyebrow' => 'A Home Hero already exists for the configured language.',
                ]);
            }

            throw $exception;
        } catch (Throwable $exception) {
            $this->deleteManagedImage($imagePath);

            throw $exception;
        }

        return to_route('admin.home-hero.index')->with('status', 'Home Hero added successfully.');
    }

    public function show(int $id): View|RedirectResponse
    {
        if (! $this->heroTableExists()) {
            return to_route('admin.home-hero.index');
        }

        return view('admin.home-hero.show', ['hero' => $this->heroById($id)->load(['creator.roles', 'updater.roles'])]);
    }

    public function edit(int $id): View|RedirectResponse
    {
        if (! $this->heroTableExists()) {
            return to_route('admin.home-hero.index');
        }

        return view('admin.home-hero.edit', ['hero' => $this->heroById($id)]);
    }

    public function update(UpdateHomeHeroRequest $request, int $id): RedirectResponse
    {
        if (! $this->heroTableExists()) {
            return to_route('admin.home-hero.index');
        }

        $hero = $this->heroById($id);
        $validated = $request->validated();
        $data = Arr::except($validated, ['hero_image']);
        $previousImagePath = $hero->hero_image;
        $newImagePath = null;

        if ($request->hasFile('hero_image')) {
            $newImagePath = $this->storeImage($request->file('hero_image'));
            $data['hero_image'] = $newImagePath;
        }

        try {
            DB::transaction(function () use ($hero, $data): void {
                $hero->update($data);
            });
        } catch (Throwable $exception) {
            $this->deleteManagedImage($newImagePath);

            throw $exception;
        }

        if ($newImagePath !== null && is_string($previousImagePath)) {
            $this->deleteManagedImage($previousImagePath);
        }

        return to_route('admin.home-hero.index')->with('status', 'Home Hero updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        if (! $this->heroTableExists()) {
            return to_route('admin.home-hero.index');
        }

        $hero = $this->heroById($id);
        $imagePath = $hero->hero_image;

        DB::transaction(function () use ($hero): void {
            $hero->delete();
        });

        if (is_string($imagePath)) {
            $this->deleteManagedImage($imagePath);
        }

        return to_route('admin.home-hero.index')->with('status', 'Home Hero deleted successfully.');
    }

    private function heroTableExists(): bool
    {
        return Schema::hasTable((new HomeHeroSection)->getTable());
    }

    private function heroById(int $id): HomeHeroSection
    {
        return HomeHeroSection::query()
            ->where('language', config('content_language.code'))
            ->findOrFail($id);
    }

    private function heroExists(bool $lock = false): bool
    {
        $query = HomeHeroSection::query()->where('language', config('content_language.code'));

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->exists();
    }

    private function storeImage(UploadedFile $image): string
    {
        $directory = public_path(self::IMAGE_DIRECTORY);
        File::ensureDirectoryExists($directory);

        $baseName = Str::of(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME))
            ->ascii()->replaceMatches('/[^A-Za-z0-9]+/', '-')->trim('-')->toString();
        $baseName = $baseName !== '' ? $baseName : 'home-hero';
        $extension = strtolower($image->extension());
        $filename = $baseName.'.'.$extension;
        $suffix = 2;

        while (File::exists($directory.DIRECTORY_SEPARATOR.$filename)) {
            $filename = $baseName.'-'.$suffix.'.'.$extension;
            $suffix++;
        }

        $image->move($directory, $filename);

        return self::IMAGE_DIRECTORY.'/'.$filename;
    }

    private function deleteManagedImage(?string $path): void
    {
        if ($path === null) {
            return;
        }

        $normalized = str_replace('\\', '/', $path);
        $filename = basename($normalized);

        if ($filename !== '' && $normalized === self::IMAGE_DIRECTORY.'/'.$filename) {
            File::delete(public_path($normalized));
        }
    }
}
