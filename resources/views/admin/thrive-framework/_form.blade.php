@php
    $existing = (bool) $section?->exists;
    $value = static fn (string $field, mixed $default = null): mixed => old($field, $existing ? $section->{$field} : $default);
    $hasStageInput = session()->hasOldInput('stages') || session()->hasOldInput('stages_present');
    $stageRows = $hasStageInput ? old('stages', []) : ($existing ? $section->stages->toArray() : []);
    $stageRows = is_array($stageRows) ? $stageRows : [];
@endphp
<form method="POST" action="{{ $existing ? route('admin.thrive-framework.update', $section->id) : route('admin.thrive-framework.store') }}" data-framework-form>
    @csrf
    @if ($existing) @method('PUT') @endif
    <section class="card admin-settings-card mb-4"><div class="card-header"><h3>Framework Content</h3></div><div class="card-body"><div class="row g-4">
        @foreach (['eyebrow' => 'Small Heading / Eyebrow', 'heading' => 'Main Heading'] as $field => $label)
            <div class="col-md-6"><label class="form-label" for="framework-{{ $field }}">{{ $label }} <span class="text-danger">*</span></label><input class="form-control @error($field) is-invalid @enderror" id="framework-{{ $field }}" name="{{ $field }}" type="text" maxlength="255" value="{{ $value($field) }}" required>@error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        @endforeach
        @foreach (['description_one' => 'Description One', 'description_two' => 'Description Two'] as $field => $label)
            <div class="col-md-6"><label class="form-label" for="framework-{{ $field }}">{{ $label }}</label><textarea class="form-control @error($field) is-invalid @enderror" id="framework-{{ $field }}" name="{{ $field }}" rows="4">{{ $value($field) }}</textarea>@error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        @endforeach
        <fieldset class="col-md-4">
            <legend class="form-label">Section Status <span class="text-danger">*</span></legend>
            <div class="d-flex gap-4 pt-2">
                @foreach ([1 => 'Active', 0 => 'Inactive'] as $status => $label)
                    <div class="form-check">
                        <input class="form-check-input @error('is_active') is-invalid @enderror" id="framework-status-{{ $status }}" name="is_active" type="radio" value="{{ $status }}" @checked((int) (bool) $value('is_active', 1) === $status) required>
                        <label class="form-check-label" for="framework-status-{{ $status }}">{{ $label }}</label>
                    </div>
                @endforeach
            </div>
            @error('is_active')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </fieldset>
    </div></div></section>
    <section class="card admin-settings-card"><div class="card-header d-flex flex-wrap justify-content-between gap-3 align-items-center"><div><h3>Framework Stages</h3><p>Stages are optional. Add, reorder, enable or remove stages. Populated stages need a letter and title.</p></div><button class="btn btn-outline-primary" type="button" data-add-stage>Add Stage</button></div>
        <div class="card-body">
            <input name="stages_present" type="hidden" value="1">
            @error('stages_present')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            @error('stages')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <div class="vstack gap-3" data-stage-list>@foreach ($stageRows as $index => $stage)@include('admin.thrive-framework._stage', compact('index', 'stage'))@endforeach</div>
            <p class="text-muted mt-3 mb-0" data-stages-empty @if (count($stageRows)) hidden @endif>No stages have been added. Use Add Stage to create one.</p>
        </div>
    </section>
    <div class="d-flex justify-content-end gap-2 mt-4"><a class="btn btn-outline-secondary" href="{{ route('admin.thrive-framework.index') }}">Cancel</a><button class="btn btn-primary admin-primary-button" type="submit" data-framework-save>{{ $existing ? 'Save Changes' : 'Add THRIVE Framework' }}</button></div>
</form>
<template data-stage-template>@include('admin.thrive-framework._stage', ['index' => '__INDEX__', 'stage' => []])</template>
@push('scripts')
<script>
(() => {
    const form = document.querySelector('[data-framework-form]');
    if (!form) return;
    const list = form.querySelector('[data-stage-list]');
    const template = document.querySelector('[data-stage-template]');
    const updateRows = () => {
        const rows = [...list.querySelectorAll('[data-framework-stage]')];
        rows.forEach((row, index) => {
            row.querySelector('[data-stage-number]').textContent = index + 1;
            row.querySelectorAll('[data-stage-field]').forEach(field => {
                const key = field.dataset.stageField;
                field.name = `stages[${index}][${key}]`;
                field.id = `framework-stage-${index}-${key}`;
                const label = row.querySelector(`[data-stage-label="${key}"]`);
                if (label) label.htmlFor = field.id;
            });
            row.querySelector('[data-move="up"]').disabled = index === 0;
            row.querySelector('[data-move="down"]').disabled = index === rows.length - 1;
        });
        form.querySelector('[data-stages-empty]').hidden = rows.length > 0;
    };
    form.querySelector('[data-add-stage]').addEventListener('click', () => {
        list.appendChild(template.content.cloneNode(true));
        updateRows();
        list.lastElementChild.querySelector('[data-stage-field="letter"]').focus();
    });
    list.addEventListener('click', event => {
        const button = event.target.closest('button');
        if (!button) return;
        const row = button.closest('[data-framework-stage]');
        if (!row) return;
        if (button.hasAttribute('data-remove-stage')) row.remove();
        if (button.dataset.move === 'up' && row.previousElementSibling) list.insertBefore(row, row.previousElementSibling);
        if (button.dataset.move === 'down' && row.nextElementSibling) list.insertBefore(row.nextElementSibling, row);
        updateRows();
    });
    form.addEventListener('submit', () => {
        if (form.checkValidity()) form.querySelector('[data-framework-save]').disabled = true;
    });
    updateRows();
})();
</script>
@endpush

