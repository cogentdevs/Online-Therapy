@extends('layouts.adminLayout.admin-design')
@section('title', 'THRIVE Framework Layout Settings')
@section('content')
    <div class="container-fluid">
        <div class="mb-4">
            <h2>Homepage Layout Settings</h2>
            <nav aria-label="breadcrumb"><ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.thrive-framework.index') }}">THRIVE Framework</a></li>
                <li class="breadcrumb-item active">Layout Settings</li>
            </ol></nav>
        </div>
        @foreach (['status' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $key => $style)
            @if (session($key))<div class="alert alert-{{ $style }}" role="status">{{ session($key) }}</div>@endif
        @endforeach
        <form method="POST" action="{{ route('admin.thrive-framework.homepage-layout-settings.update') }}">
            @csrf
            @method('PUT')
            <section class="card admin-settings-card">
                <div class="card-header"><h3>THRIVE Framework</h3><p>Choose how many Framework cards appear in each desktop row on the homepage.</p></div>
                <div class="card-body">
                    @if (! $settingsAvailable)
                        <div class="alert alert-warning mb-0">Complete the THRIVE Framework layout setup before this setting can be saved.</div>
                    @elseif (! $section)
                        <div class="alert alert-warning mb-0">Add a THRIVE Framework section before saving its layout settings.</div>
                    @else
                        <label class="form-label" for="framework-cards-per-row">Desktop Cards Per Row</label>
                        <select class="form-select @error('cards_per_row') is-invalid @enderror" id="framework-cards-per-row" name="cards_per_row">
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
                <a class="btn btn-outline-secondary" href="{{ route('admin.thrive-framework.index') }}">Back to Listing</a>
                @if ($settingsAvailable && $section)
                    <button class="btn btn-primary admin-primary-button" type="submit"><i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i>Save Settings</button>
                @endif
            </div>
        </form>
    </div>
@endsection
