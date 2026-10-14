@extends('layouts.adminLayout.admin-design')

@section('title', 'Homepage Layout Settings')

@section('content')
    <div class="container-fluid">
        <div class="mb-4">
            <h2>Homepage Layout Settings</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.course.index') }}">Courses</a></li>
                    <li class="breadcrumb-item active">Homepage Layout Settings</li>
                </ol>
            </nav>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.course.homepage-layout-settings.update') }}">
            @csrf
            @method('PUT')
            <section class="card admin-settings-card">
                <div class="card-header">
                    <h3>Featured Courses</h3>
                    <p>Choose how many Featured Courses appear in each desktop row on the homepage.</p>
                </div>
                <div class="card-body">
                    @if (! $settingsAvailable)
                        <div class="alert alert-warning mb-0">Run the featured Courses layout migration before this setting can be saved.</div>
                    @else
                        <label class="form-label" for="featured_courses_desktop_columns">Desktop Cards Per Row</label>
                        <select class="form-select @error('featured_courses_desktop_columns') is-invalid @enderror" id="featured_courses_desktop_columns" name="featured_courses_desktop_columns">
                            <option value="3" @selected(old('featured_courses_desktop_columns', $desktopColumns) == 3)>3 Cards Per Row</option>
                            <option value="4" @selected(old('featured_courses_desktop_columns', $desktopColumns) == 4)>4 Cards Per Row</option>
                        </select>
                        @error('featured_courses_desktop_columns')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    @endif
                </div>
            </section>
            <div class="d-flex justify-content-end gap-2 mt-3">
                <a class="btn btn-outline-secondary" href="{{ route('admin.course.index') }}">Cancel</a>
                @if ($settingsAvailable)
                    <button class="btn btn-primary admin-primary-button" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Save Settings</button>
                @endif
            </div>
        </form>
    </div>
@endsection
