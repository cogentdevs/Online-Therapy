@php
    $sectionExists = (bool) ($section?->exists);
    $sectionValues = [
        'eyebrow' => old('eyebrow', $sectionExists ? $section->eyebrow : $defaultSection['eyebrow']),
        'heading' => old('heading', $sectionExists ? $section->heading : $defaultSection['heading']),
        'description' => old('description', $sectionExists ? $section->description : $defaultSection['description']),
        'bottom_message' => old('bottom_message', $sectionExists ? $section->bottom_message : $defaultSection['bottom_message']),
        'is_active' => old('is_active', $sectionExists ? (int) $section->is_active : (int) $defaultSection['is_active']),
    ];
    $itemRows = old(
        'items',
        $sectionExists
            ? $section->items->map(fn ($item) => ['id' => $item->id, 'title' => $item->title, 'is_active' => (int) $item->is_active])->all()
            : $defaultItems,
    );
@endphp

<form method="POST" action="{{ $sectionExists ? route('admin.who-i-help.update', $section->id) : route('admin.who-i-help.store') }}" data-who-i-help-form>
    @csrf
    @if ($sectionExists)
        @method('PUT')
    @endif

    <section class="card admin-settings-card mb-4">
        <div class="card-header">
            <h3>Section Information</h3>
            <p>Manage the content and visibility of the Who I Help homepage section.</p>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label" for="who-i-help-eyebrow">Small Heading / Eyebrow <span class="text-danger">*</span></label>
                    <input class="form-control @error('eyebrow') is-invalid @enderror" id="who-i-help-eyebrow" name="eyebrow" type="text" maxlength="100" value="{{ $sectionValues['eyebrow'] }}" required>
                    @error('eyebrow')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label d-block">Section Status <span class="text-danger">*</span></label>
                    <div class="d-flex flex-wrap gap-4">
                        @foreach ([1 => 'Active', 0 => 'Inactive'] as $value => $label)
                            <div class="form-check">
                                <input class="form-check-input" id="who-i-help-status-{{ $value }}" name="is_active" type="radio" value="{{ $value }}" @checked((string) $sectionValues['is_active'] === (string) $value) required>
                                <label class="form-check-label" for="who-i-help-status-{{ $value }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                    @error('is_active')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="who-i-help-heading">Main Heading <span class="text-danger">*</span></label>
                    <input class="form-control @error('heading') is-invalid @enderror" id="who-i-help-heading" name="heading" type="text" maxlength="255" value="{{ $sectionValues['heading'] }}" required>
                    @error('heading')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="who-i-help-description">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="who-i-help-description" name="description" rows="5" maxlength="5000">{{ $sectionValues['description'] }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="who-i-help-bottom-message">Bottom Message</label>
                    <textarea class="form-control @error('bottom_message') is-invalid @enderror" id="who-i-help-bottom-message" name="bottom_message" rows="3" maxlength="5000">{{ $sectionValues['bottom_message'] }}</textarea>
                    @error('bottom_message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </section>

    <section class="card admin-settings-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h3>Help Cards</h3>
                <p>Cards are optional. Add, update, reorder, enable, or remove cards when needed.</p>
            </div>
            <button class="btn btn-outline-primary" type="button" data-add-help-card><i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Add Card</button>
        </div>
        <div class="card-body">
            <input name="items_present" type="hidden" value="1">
            @error('items_present')<div class="invalid-feedback d-block mb-3">{{ $message }}</div>@enderror
            @error('items')<div class="invalid-feedback d-block mb-3">{{ $message }}</div>@enderror

            <div class="vstack gap-3" data-help-card-list>
                @foreach ($itemRows as $index => $item)
                    @php($itemIsActive = (string) data_get($item, 'is_active', 1))
                    <article class="border rounded p-3" data-help-card>
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <h4 class="h6 mb-0">Help Card <span data-card-number>{{ $loop->iteration }}</span></h4>
                            <div class="d-flex flex-wrap gap-2">
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-move="up"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i><span class="visually-hidden">Move Up</span></button>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-move="down"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i><span class="visually-hidden">Move Down</span></button>
                                <button class="btn btn-sm btn-outline-danger" type="button" data-remove-help-card><i class="fa-solid fa-trash" aria-hidden="true"></i><span class="visually-hidden">Remove</span></button>
                            </div>
                        </div>
                        @if (filled(data_get($item, 'id')))
                            <input data-help-field="id" name="items[{{ $index }}][id]" type="hidden" value="{{ data_get($item, 'id') }}">
                        @endif
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-8">
                                <label class="form-label">Card Text</label>
                                <textarea class="form-control @error("items.$index.title") is-invalid @enderror" data-help-field="title" name="items[{{ $index }}][title]" rows="3" maxlength="1000">{{ data_get($item, 'title') }}</textarea>
                                @error("items.$index.title")<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-lg-4">
                                <span class="form-label d-block">Status</span>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ([1 => 'Active', 0 => 'Inactive'] as $value => $label)
                                        <div class="form-check">
                                            <input class="form-check-input" data-help-field="is_active" id="who-i-help-item-{{ $index }}-status-{{ $value }}" name="items[{{ $index }}][is_active]" type="radio" value="{{ $value }}" @checked($itemIsActive === (string) $value)>
                                            <label class="form-check-label" data-help-status-label="{{ $value }}" for="who-i-help-item-{{ $index }}-status-{{ $value }}">{{ $label }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                @error("items.$index.is_active")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            <p class="text-muted mb-0 mt-3" data-help-card-empty @if (count($itemRows)) hidden @endif>No Help Cards have been added. Use Add Card to create one.</p>
        </div>
    </section>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a class="btn btn-outline-secondary" href="{{ route('admin.who-i-help.index') }}">Cancel</a>
        <button class="btn btn-primary admin-primary-button" type="submit" data-save-who-i-help><i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i>{{ $sectionExists ? 'Save Changes' : 'Add Who I Help' }}</button>
    </div>
</form>

@push('scripts')
<script>
(() => {
    const form = document.querySelector('[data-who-i-help-form]');
    if (!form) return;
    const list = form.querySelector('[data-help-card-list]');
    const emptyState = form.querySelector('[data-help-card-empty]');
    const addButton = form.querySelector('[data-add-help-card]');
    const updateRows = () => {
        [...list.querySelectorAll('[data-help-card]')].forEach((row, index) => {
            row.querySelector('[data-card-number]').textContent = index + 1;
            row.querySelectorAll('[data-help-field]').forEach((field) => {
                const fieldName = field.dataset.helpField;
                field.name = `items[${index}][${fieldName}]`;
                if (field.type === 'radio') {
                    field.id = `who-i-help-item-${index}-status-${field.value}`;
                    const label = row.querySelector(`[data-help-status-label="${field.value}"]`);
                    if (label) label.htmlFor = field.id;
                }
            });
        });
        emptyState.hidden = list.querySelectorAll('[data-help-card]').length !== 0;
    };
    const newRow = () => {
        const row = document.createElement('article');
        row.className = 'border rounded p-3';
        row.dataset.helpCard = '';
        row.innerHTML = `<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3"><h4 class="h6 mb-0">Help Card <span data-card-number></span></h4><div class="d-flex flex-wrap gap-2"><button class="btn btn-sm btn-outline-secondary" type="button" data-move="up"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i><span class="visually-hidden">Move Up</span></button><button class="btn btn-sm btn-outline-secondary" type="button" data-move="down"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i><span class="visually-hidden">Move Down</span></button><button class="btn btn-sm btn-outline-danger" type="button" data-remove-help-card><i class="fa-solid fa-trash" aria-hidden="true"></i><span class="visually-hidden">Remove</span></button></div></div><div class="row g-3 align-items-end"><div class="col-lg-8"><label class="form-label">Card Text</label><textarea class="form-control" data-help-field="title" rows="3" maxlength="1000"></textarea></div><div class="col-lg-4"><span class="form-label d-block">Status</span><div class="d-flex flex-wrap gap-3"><div class="form-check"><input class="form-check-input" data-help-field="is_active" type="radio" value="1" checked><label class="form-check-label" data-help-status-label="1">Active</label></div><div class="form-check"><input class="form-check-input" data-help-field="is_active" type="radio" value="0"><label class="form-check-label" data-help-status-label="0">Inactive</label></div></div></div></div>`;
        return row;
    };
    addButton.addEventListener('click', () => { list.appendChild(newRow()); updateRows(); list.querySelector('[data-help-card]:last-child textarea')?.focus(); });
    list.addEventListener('click', (event) => {
        const moveButton = event.target.closest('[data-move]');
        if (moveButton) {
            const row = moveButton.closest('[data-help-card]');
            const sibling = moveButton.dataset.move === 'up' ? row.previousElementSibling : row.nextElementSibling;
            if (sibling?.matches('[data-help-card]')) { moveButton.dataset.move === 'up' ? list.insertBefore(row, sibling) : list.insertBefore(sibling, row); updateRows(); }
            return;
        }
        const removeButton = event.target.closest('[data-remove-help-card]');
        if (removeButton) { removeButton.closest('[data-help-card]')?.remove(); updateRows(); }
    });
    form.addEventListener('submit', () => {
        if (!form.checkValidity()) return;
        const saveButton = form.querySelector('[data-save-who-i-help]');
        if (saveButton) { saveButton.disabled = true; saveButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2" aria-hidden="true"></i>Saving...'; }
    });
    updateRows();
})();
</script>
@endpush