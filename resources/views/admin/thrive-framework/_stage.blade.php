<article class="border rounded p-3" data-framework-stage>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><h4 class="h6 mb-0">Stage <span data-stage-number>{{ is_numeric($index) ? $index + 1 : '' }}</span></h4>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" type="button" data-move="up" aria-label="Move stage up">↑</button>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-move="down" aria-label="Move stage down">↓</button>
            <button class="btn btn-sm btn-outline-danger" type="button" data-remove-stage>Remove</button>
        </div>
    </div>
    @if (filled(data_get($stage, 'id')))<input type="hidden" data-stage-field="id" name="stages[{{ $index }}][id]" value="{{ data_get($stage, 'id') }}">@endif
    @error("stages.$index.id")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    <div class="row g-3">
        @foreach (['letter' => ['Letter', 10], 'stage_label' => ['Stage Label', 50], 'title' => ['Title', 255]] as $field => [$label, $maximum])
            <div class="{{ $field === 'title' ? 'col-md-6' : 'col-md-3' }}"><label class="form-label" data-stage-label="{{ $field }}" for="framework-stage-{{ $index }}-{{ $field }}">{{ $label }}{{ in_array($field, ['letter', 'title']) ? ' *' : '' }}</label>
                <input class="form-control @error("stages.$index.$field") is-invalid @enderror" id="framework-stage-{{ $index }}-{{ $field }}" data-stage-field="{{ $field }}" name="stages[{{ $index }}][{{ $field }}]" type="text" maxlength="{{ $maximum }}" value="{{ data_get($stage, $field) }}">
                @error("stages.$index.$field")<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        @endforeach
        <div class="col-md-9"><label class="form-label" data-stage-label="description" for="framework-stage-{{ $index }}-description">Description</label><textarea class="form-control @error("stages.$index.description") is-invalid @enderror" id="framework-stage-{{ $index }}-description" data-stage-field="description" name="stages[{{ $index }}][description]" rows="3">{{ data_get($stage, 'description') }}</textarea>@error("stages.$index.description")<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-3"><label class="form-label" data-stage-label="is_active" for="framework-stage-{{ $index }}-is_active">Status</label><select class="form-select @error("stages.$index.is_active") is-invalid @enderror" id="framework-stage-{{ $index }}-is_active" data-stage-field="is_active" name="stages[{{ $index }}][is_active]"><option value="1" @selected((int) (bool) data_get($stage, 'is_active', 1) === 1)>Active</option><option value="0" @selected((int) (bool) data_get($stage, 'is_active', 1) === 0)>Inactive</option></select>@error("stages.$index.is_active")<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    </div>
</article>

