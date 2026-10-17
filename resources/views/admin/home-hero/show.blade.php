@extends('layouts.adminLayout.admin-design')

@section('title', 'Home Hero Details')

@section('content')
    <div class="container-fluid">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h2 class="mb-1">Home Hero Details</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.home-hero.index') }}">Home Hero</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Details</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('admin.home-hero.index') }}">Back to Listing</a>
                @can('home-hero.edit')
                    <a class="btn btn-primary admin-primary-button" href="{{ route('admin.home-hero.edit', $hero->id) }}">
                        <i class="fa-solid fa-pen-to-square me-2" aria-hidden="true"></i>Edit
                    </a>
                @endcan
            </div>
        </div>

        <section class="card admin-settings-card">
            <div class="card-body">
                <dl class="row mb-0">
                    @foreach ([
                        'eyebrow' => 'Small Heading / Eyebrow',
                        'heading_before_highlight' => 'Heading Before Highlight',
                        'highlighted_text' => 'Highlighted Text',
                        'heading_after_highlight' => 'Heading After Highlight',
                        'description' => 'Description',
                        'image_alt' => 'Image Alt Text',
                        'primary_button_text' => 'Primary Button Text',
                        'primary_button_url' => 'Primary Button URL',
                        'secondary_button_text' => 'Secondary Button Text',
                        'secondary_button_url' => 'Secondary Button URL',
                    ] as $field => $label)
                        <dt class="col-md-3">{{ $label }}</dt>
                        <dd class="col-md-9 text-break">{{ filled($hero->{$field}) ? $hero->{$field} : '—' }}</dd>
                    @endforeach
                    <dt class="col-md-3">Section Status</dt>
                    <dd class="col-md-9">
                        <span class="badge {{ $hero->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $hero->is_active ? 'Active' : 'Inactive' }}</span>
                    </dd>
                    <dt class="col-md-3">Created At</dt>
                    <dd class="col-md-9">{{ $hero->created_at?->format('d M Y, h:i A') ?? '—' }}</dd>
                    <dt class="col-md-3">Updated At</dt>
                    <dd class="col-md-9">{{ $hero->updated_at?->format('d M Y, h:i A') ?? '—' }}</dd>
                    <dt class="col-md-3">Hero Image</dt>
                    <dd class="col-md-9 mb-0">
                        @if (filled($hero->hero_image))
                            <img class="rounded border img-fluid" src="{{ asset($hero->hero_image) }}"
                                alt="{{ $hero->image_alt ?: 'Hero image' }}" style="max-height: 360px;">
                        @else
                            <span class="text-muted">No image</span>
                        @endif
                    </dd>
                </dl>
            </div>
        </section>
    </div>
@endsection

