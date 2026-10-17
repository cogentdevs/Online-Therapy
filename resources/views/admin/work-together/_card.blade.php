@php
    $cardValue = static function (string $field, mixed $default = null) use ($card): mixed {
        $value = data_get($card, $field, $default);
        return is_scalar($value) ? $value : null;
    };
@endphp
<article class="border rounded p-3" data-work-card>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h4 class="h6 mb-0">Card <span data-card-number>{{ is_numeric($index) ? $index + 1 : '' }}</span></h4>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" type="button" data-move="up" aria-label="Move card up"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-move="down" aria-label="Move card down"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
            <button class="btn btn-sm btn-outline-danger" type="button" data-remove-card aria-label="Remove card"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
        </div>
    </div>
    @if (filled($cardValue('id')))<input type="hidden" data-card-field="id" name="cards[{{ $index }}][id]" value="{{ $cardValue('id') }}">@endif
    @error("cards.$index")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    @error("cards.$index.id")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label" data-card-label="title" for="work-card-{{ $index }}-title">Card Title <span class="text-danger">*</span></label>
            <input class="form-control @error("cards.$index.title") is-invalid @enderror" type="text" id="work-card-{{ $index }}-title" data-card-field="title" name="cards[{{ $index }}][title]" maxlength="255" value="{{ $cardValue('title') }}">
            @error("cards.$index.title")<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" data-card-label="layout_type" for="work-card-{{ $index }}-layout_type">Card Layout</label>
            <select class="form-select @error("cards.$index.layout_type") is-invalid @enderror" id="work-card-{{ $index }}-layout_type" data-card-field="layout_type" name="cards[{{ $index }}][layout_type]">
                @foreach (['standard' => 'Standard', 'full_width' => 'Full Width'] as $layout => $label)
                    <option value="{{ $layout }}" @selected($cardValue('layout_type', 'standard') === $layout)>{{ $label }}</option>
                @endforeach
            </select>
            @error("cards.$index.layout_type")<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        @foreach (['description' => 'Card Description', 'additional_details' => 'Additional Details'] as $field => $label)
            <div class="col-md-6">
                <label class="form-label" data-card-label="{{ $field }}" for="work-card-{{ $index }}-{{ $field }}">{{ $label }}</label>
                <textarea class="form-control @error("cards.$index.$field") is-invalid @enderror" id="work-card-{{ $index }}-{{ $field }}" data-card-field="{{ $field }}" name="cards[{{ $index }}][{{ $field }}]" rows="3" maxlength="10000">{{ $cardValue($field) }}</textarea>
                @error("cards.$index.$field")<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        @endforeach
        @foreach (['button_text' => ['Button Text', 255], 'button_url' => ['Button URL', 2048]] as $field => [$label, $maximum])
            <div class="col-md-6">
                <label class="form-label" data-card-label="{{ $field }}" for="work-card-{{ $index }}-{{ $field }}">{{ $label }}</label>
                <input class="form-control @error("cards.$index.$field") is-invalid @enderror" id="work-card-{{ $index }}-{{ $field }}" data-card-field="{{ $field }}" name="cards[{{ $index }}][{{ $field }}]" type="text" maxlength="{{ $maximum }}" value="{{ $cardValue($field) }}">
                @error("cards.$index.$field")<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        @endforeach
        <fieldset class="col-12"><legend class="form-label">Card Status</legend><div class="d-flex gap-4">
            @foreach ([1 => 'Active', 0 => 'Inactive'] as $status => $label)
                <div class="form-check">
                    <input class="form-check-input @error("cards.$index.is_active") is-invalid @enderror" type="radio" data-card-field="is_active" name="cards[{{ $index }}][is_active]" id="work-card-{{ $index }}-is_active-{{ $status }}" value="{{ $status }}" @checked((int) (bool) $cardValue('is_active', 1) === $status)>
                    <label class="form-check-label" data-card-label="is_active-{{ $status }}" for="work-card-{{ $index }}-is_active-{{ $status }}">{{ $label }}</label>
                </div>
            @endforeach
        </div>@error("cards.$index.is_active")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</fieldset>
    </div>
</article>

