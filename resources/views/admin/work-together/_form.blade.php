@php
    $existing = (bool) $section?->exists;
    $value = static function (string $field, mixed $default = null) use ($existing, $section): mixed {
        $result = old($field, $existing ? $section->{$field} : $default);
        return is_scalar($result) ? $result : null;
    };
    $hasCardInput = session()->hasOldInput('cards') || session()->hasOldInput('cards_present');
    $cardRows = $hasCardInput ? old('cards', []) : ($existing ? $section->cards->toArray() : []);
    $cardRows = is_array($cardRows) ? $cardRows : [];
@endphp
<form method="POST" action="{{ $existing ? route('admin.work-together.update', $section->id) : route('admin.work-together.store') }}" data-work-together-form>
    @csrf
    @if ($existing) @method('PUT') @endif
    <section class="card admin-settings-card mb-4"><div class="card-header"><h3>Section Details</h3></div><div class="card-body"><div class="row g-4">
        @foreach (['eyebrow' => 'Small Heading', 'heading' => 'Main Heading'] as $field => $label)
            <div class="col-md-6"><label class="form-label" for="work-{{ $field }}">{{ $label }} <span class="text-danger">*</span></label>
                <input class="form-control @error($field) is-invalid @enderror" id="work-{{ $field }}" name="{{ $field }}" type="text" maxlength="255" value="{{ $value($field) }}" required>
                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        @endforeach
        @foreach (['description' => 'Description', 'bottom_message' => 'Bottom Message'] as $field => $label)
            <div class="col-md-6"><label class="form-label" for="work-{{ $field }}">{{ $label }}</label>
                <textarea class="form-control @error($field) is-invalid @enderror" id="work-{{ $field }}" name="{{ $field }}" rows="4" maxlength="10000">{{ $value($field) }}</textarea>
                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        @endforeach
        @foreach (['bottom_button_text' => ['Bottom Button Text', 255], 'bottom_button_url' => ['Bottom Button URL', 2048]] as $field => [$label, $maximum])
            <div class="col-md-6"><label class="form-label" for="work-{{ $field }}">{{ $label }}</label>
                <input class="form-control @error($field) is-invalid @enderror" id="work-{{ $field }}" name="{{ $field }}" type="text" maxlength="{{ $maximum }}" value="{{ $value($field) }}">
                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        @endforeach
        <fieldset class="col-md-4"><legend class="form-label">Section Status <span class="text-danger">*</span></legend><div class="d-flex gap-4 pt-2">
            @foreach ([1 => 'Active', 0 => 'Inactive'] as $status => $label)
                <div class="form-check"><input class="form-check-input @error('is_active') is-invalid @enderror" id="work-status-{{ $status }}" name="is_active" type="radio" value="{{ $status }}" @checked((int) (bool) $value('is_active', 1) === $status) required><label class="form-check-label" for="work-status-{{ $status }}">{{ $label }}</label></div>
            @endforeach
        </div>@error('is_active')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</fieldset>
    </div></div></section>
    <section class="card admin-settings-card"><div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div><h3>Cards Management</h3><p>Add, reorder, enable or remove cards. A populated card needs a title. CTA links require both text and URL.</p></div>
        <button class="btn btn-outline-primary" type="button" data-add-card><i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Add Card</button>
    </div><div class="card-body">
        <input name="cards_present" type="hidden" value="1">
        @error('cards_present')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @error('cards')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <div class="vstack gap-3" data-card-list>@foreach ($cardRows as $index => $card)@include('admin.work-together._card', compact('index', 'card'))@endforeach</div>
        <p class="text-muted mb-0 mt-3" data-cards-empty @if (count($cardRows)) hidden @endif>No cards have been added. Use Add Card to create one.</p>
    </div></section>
    <div class="d-flex justify-content-end gap-2 mt-4"><a class="btn btn-outline-secondary" href="{{ route('admin.work-together.index') }}">Cancel</a><button class="btn btn-primary admin-primary-button" type="submit" data-save-work-together>{{ $existing ? 'Save Changes' : 'Add Section' }}</button></div>
</form>
<template data-work-card-template>@include('admin.work-together._card', ['index' => '__INDEX__', 'card' => []])</template>
@push('scripts')
<script>
(() => {
    const form = document.querySelector('[data-work-together-form]');
    if (!form) return;
    const list = form.querySelector('[data-card-list]');
    const template = document.querySelector('[data-work-card-template]');
    const updateRows = () => {
        const rows = [...list.querySelectorAll('[data-work-card]')];
        // Separate radio groups before reindexing so moving cards preserves checked status.
        rows.forEach((row, index) => {
            row.querySelectorAll('input[type="radio"][data-card-field]').forEach(field => {
                field.name = `work-card-temporary[${index}][${field.dataset.cardField}]`;
            });
        });
        rows.forEach((row, index) => {
            row.querySelector('[data-card-number]').textContent = index + 1;
            row.querySelectorAll('[data-card-field]').forEach(field => {
                const key = field.dataset.cardField;
                const suffix = field.type === 'radio' ? `${key}-${field.value}` : key;
                field.name = `cards[${index}][${key}]`;
                field.id = `work-card-${index}-${suffix}`;
                const label = row.querySelector(`[data-card-label="${suffix}"]`);
                if (label) label.htmlFor = field.id;
            });
            row.querySelector('[data-move="up"]').disabled = index === 0;
            row.querySelector('[data-move="down"]').disabled = index === rows.length - 1;
        });
        form.querySelector('[data-cards-empty]').hidden = rows.length > 0;
    };
    form.querySelector('[data-add-card]').addEventListener('click', () => {
        list.appendChild(template.content.cloneNode(true));
        updateRows();
        list.lastElementChild.querySelector('[data-card-field="title"]').focus();
    });
    list.addEventListener('click', event => {
        const button = event.target.closest('button');
        if (!button) return;
        const row = button.closest('[data-work-card]');
        if (!row) return;
        if (button.hasAttribute('data-remove-card')) row.remove();
        if (button.dataset.move === 'up' && row.previousElementSibling) list.insertBefore(row, row.previousElementSibling);
        if (button.dataset.move === 'down' && row.nextElementSibling) list.insertBefore(row.nextElementSibling, row);
        updateRows();
    });
    form.addEventListener('submit', () => {
        if (form.checkValidity()) form.querySelector('[data-save-work-together]').disabled = true;
    });
    updateRows();
})();
</script>
@endpush

