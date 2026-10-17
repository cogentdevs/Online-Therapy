@extends('layouts.adminLayout.admin-design')
@section('title', 'How We Can Work Together Layout Settings')
@section('content')
    <div class="container-fluid">
        <div class="mb-4">
            <h2>Homepage Layout Settings</h2>
            <nav aria-label="breadcrumb"><ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.work-together.index') }}">How We Can Work Together</a></li>
                <li class="breadcrumb-item active">Layout Settings</li>
            </ol></nav>
        </div>
        @foreach (['status' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $key => $style)
            @if (session($key))<div class="alert alert-{{ $style }}" role="status">{{ session($key) }}</div>@endif
        @endforeach
        <form method="POST" action="{{ route('admin.work-together.homepage-layout-settings.update') }}">
            @csrf
            @method('PUT')
            <section class="card admin-settings-card">
                <div class="card-header"><h3>How We Can Work Together</h3><p>Choose how many cards appear in each desktop row on the homepage.</p></div>
                <div class="card-body">
                    @if (! $settingsAvailable)
                        <div class="alert alert-warning mb-0">Layout setup is incomplete: the work_together_sections table and cards_per_row column are required. Ask your site administrator to complete setup.</div>
                    @elseif (! $section)
                        <div class="alert alert-warning mb-0">Add a How We Can Work Together section before saving its layout settings.</div>
                    @else
                        <label class="form-label" for="work-cards-per-row">Cards Per Row</label>
                        <select class="form-select @error('cards_per_row') is-invalid @enderror" id="work-cards-per-row" name="cards_per_row" @cannot('work-together.edit') disabled @endcannot>
                            @foreach ([3, 4] as $columns)
                                <option value="{{ $columns }}" @selected((int) old('cards_per_row', $desktopColumns) === $columns)>{{ $columns }} Cards Per Row</option>
                            @endforeach
                        </select>
                        <p class="form-text mb-0">Tablet displays 2 cards per row; mobile displays 1.</p>
                        @error('cards_per_row')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @endif
                </div>
            </section>
            <div class="d-flex justify-content-end gap-2 mt-3">
                <a class="btn btn-outline-secondary" href="{{ route('admin.work-together.index') }}">Back to Listing</a>
                @can('work-together.edit')
                    <button class="btn btn-primary admin-primary-button" type="submit" @disabled(! $settingsAvailable || ! $section)><i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i>Save Settings</button>
                @endcan
            </div>
        </form>
    </div>
@endsection
