@php
    $existing = (bool) ($hero?->exists);
    $value = static fn (string $field, mixed $default = null): mixed => old($field, $existing ? $hero->{$field} : $default);
@endphp

<form method="POST" action="{{ $existing ? route('admin.home-hero.update', $hero->id) : route('admin.home-hero.store') }}" enctype="multipart/form-data" data-home-hero-form>
    @csrf
    @if ($existing) @method('PUT') @endif

    <section class="card admin-settings-card mb-4">
        <div class="card-header"><h3>Hero Content</h3><p>Manage the text and visibility of the homepage Hero section.</p></div>
        <div class="card-body"><div class="row g-4">
            <div class="col-md-8"><label class="form-label" for="home-hero-eyebrow">Small Heading / Eyebrow <span class="text-danger">*</span></label><input class="form-control @error('eyebrow') is-invalid @enderror" id="home-hero-eyebrow" name="eyebrow" type="text" maxlength="255" value="{{ $value('eyebrow') }}" required>@error('eyebrow')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><span class="form-label d-block">Section Status <span class="text-danger">*</span></span><div class="d-flex gap-4 pt-2">@foreach ([1 => 'Active', 0 => 'Inactive'] as $status => $label)<div class="form-check"><input class="form-check-input" id="home-hero-status-{{ $status }}" name="is_active" type="radio" value="{{ $status }}" @checked((int) (bool) $value('is_active', 1) === $status) required><label class="form-check-label" for="home-hero-status-{{ $status }}">{{ $label }}</label></div>@endforeach</div>@error('is_active')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-label" for="home-hero-heading-before">Heading Before Highlight <span class="text-danger">*</span></label><textarea class="form-control @error('heading_before_highlight') is-invalid @enderror" id="home-hero-heading-before" name="heading_before_highlight" rows="3" required>{{ $value('heading_before_highlight') }}</textarea>@error('heading_before_highlight')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label" for="home-hero-highlighted">Highlighted Text</label><input class="form-control @error('highlighted_text') is-invalid @enderror" id="home-hero-highlighted" name="highlighted_text" type="text" maxlength="255" value="{{ $value('highlighted_text') }}">@error('highlighted_text')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label" for="home-hero-heading-after">Heading After Highlight</label><textarea class="form-control @error('heading_after_highlight') is-invalid @enderror" id="home-hero-heading-after" name="heading_after_highlight" rows="2">{{ $value('heading_after_highlight') }}</textarea>@error('heading_after_highlight')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-label" for="home-hero-description">Description</label><textarea class="form-control @error('description') is-invalid @enderror" id="home-hero-description" name="description" rows="5">{{ $value('description') }}</textarea>@error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        </div></div>
    </section>

    <section class="card admin-settings-card mb-4"><div class="card-header"><h3>Hero Image</h3></div><div class="card-body"><div class="row g-4 align-items-start">
        <div class="col-md-6"><label class="form-label" for="home-hero-image">Hero Image</label><input class="form-control @error('hero_image') is-invalid @enderror" id="home-hero-image" name="hero_image" type="file" accept="image/png,image/jpeg,image/webp">@error('hero_image')<div class="invalid-feedback">{{ $message }}</div>@enderror<p class="form-text mb-0">PNG, JPG, JPEG, or WEBP. Maximum file size: 5 MB. Recommended size: 960 × 1200 px (portrait, 4:5).</p><p class="form-text mb-0" data-selected-image-size aria-live="polite" hidden></p></div>
        <div class="col-md-6"><label class="form-label" for="home-hero-image-alt">Image Alt Text</label><input class="form-control @error('image_alt') is-invalid @enderror" id="home-hero-image-alt" name="image_alt" type="text" maxlength="255" value="{{ $value('image_alt') }}">@error('image_alt')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        @if ($existing && $hero->hero_image)<div class="col-12"><span class="form-label d-block">Current Image</span><img class="rounded border object-fit-cover" src="{{ asset($hero->hero_image) }}" data-current-hero-image alt="{{ $hero->image_alt ?: 'Current hero image' }}" style="max-width: min(360px, 100%); max-height: 220px; height: auto;"><p class="form-text mb-0" data-current-image-size aria-live="polite" hidden></p></div>@endif
    </div></div></section>

    <section class="card admin-settings-card mb-4"><div class="card-header"><h3>Primary Button</h3><p>Text displays the button. URL is optional; without a URL, clicking does not navigate.</p></div><div class="card-body"><div class="row g-4"><div class="col-md-6"><label class="form-label" for="home-hero-primary-text">Primary Button Text</label><input class="form-control @error('primary_button_text') is-invalid @enderror" id="home-hero-primary-text" name="primary_button_text" type="text" maxlength="255" value="{{ $value('primary_button_text') }}">@error('primary_button_text')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="col-md-6"><label class="form-label" for="home-hero-primary-url">Primary Button URL</label><input class="form-control @error('primary_button_url') is-invalid @enderror" id="home-hero-primary-url" name="primary_button_url" type="text" maxlength="2048" placeholder="/courses or https://example.com" value="{{ $value('primary_button_url') }}">@error('primary_button_url')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div></div></section>
    <section class="card admin-settings-card"><div class="card-header"><h3>Secondary Button</h3><p>Text displays the button. URL is optional; without a URL, clicking does not navigate.</p></div><div class="card-body"><div class="row g-4"><div class="col-md-6"><label class="form-label" for="home-hero-secondary-text">Secondary Button Text</label><input class="form-control @error('secondary_button_text') is-invalid @enderror" id="home-hero-secondary-text" name="secondary_button_text" type="text" maxlength="255" value="{{ $value('secondary_button_text') }}">@error('secondary_button_text')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="col-md-6"><label class="form-label" for="home-hero-secondary-url">Secondary Button URL</label><input class="form-control @error('secondary_button_url') is-invalid @enderror" id="home-hero-secondary-url" name="secondary_button_url" type="text" maxlength="2048" placeholder="/about or https://example.com" value="{{ $value('secondary_button_url') }}">@error('secondary_button_url')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div></div></section>

    <div class="d-flex justify-content-end gap-2 mt-4"><a class="btn btn-outline-secondary" href="{{ route('admin.home-hero.index') }}">Cancel</a><button class="btn btn-primary admin-primary-button" type="submit" data-save-home-hero><i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i>{{ $existing ? 'Save Changes' : 'Add Home Hero' }}</button></div>
</form>

@push('scripts')
<script>
(() => {
    const form = document.querySelector('[data-home-hero-form]');
    if (!form) return;
    const currentImage = form.querySelector('[data-current-hero-image]');
    const currentSize = form.querySelector('[data-current-image-size]');
    const showCurrentSize = () => {
        if (!currentImage.naturalWidth || !currentSize) return;
        currentSize.textContent = `Current image: ${currentImage.naturalWidth} × ${currentImage.naturalHeight} px`;
        currentSize.hidden = false;
    };
    if (currentImage) {
        currentImage.addEventListener('load', showCurrentSize);
        if (currentImage.complete) showCurrentSize();
    }
    const input = form.querySelector('#home-hero-image');
    const selectedSize = form.querySelector('[data-selected-image-size]');
    input.addEventListener('change', () => {
        const file = input.files[0];
        selectedSize.hidden = true;
        selectedSize.textContent = '';
        if (!file) return;
        const image = new Image();
        const objectUrl = URL.createObjectURL(file);
        image.onload = () => {
            if (input.files[0] === file) {
                selectedSize.textContent = `Selected image: ${image.naturalWidth} × ${image.naturalHeight} px`;
                selectedSize.hidden = false;
            }
            URL.revokeObjectURL(objectUrl);
        };
        image.onerror = () => URL.revokeObjectURL(objectUrl);
        image.src = objectUrl;
    });
})();
(() => { const form = document.querySelector('[data-home-hero-form]'); if (!form) return; form.addEventListener('submit', () => { if (!form.checkValidity()) return; const button = form.querySelector('[data-save-home-hero]'); if (button) { button.disabled = true; button.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2" aria-hidden="true"></i>Saving...'; } }); })();
</script>
@endpush
