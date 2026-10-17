@extends('layouts.adminLayout.admin-design')
@section('title', 'Details How We Can Work Together')
@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h2>Details How We Can Work Together</h2><nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.work-together.index') }}">How We Can Work Together</a></li>
            <li class="breadcrumb-item active" aria-current="page">Details</li>
        </ol></nav></div>
        <div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="{{ route('admin.work-together.index') }}">Back to Listing</a>@can('work-together.edit')<a class="btn btn-primary" href="{{ route('admin.work-together.edit', $section->id) }}">Edit</a>@endcan</div>
    </div>
    <section class="card admin-settings-card mb-4"><div class="card-body"><dl class="row mb-0">
        @foreach (['eyebrow' => 'Small Heading', 'heading' => 'Main Heading', 'description' => 'Description', 'bottom_message' => 'Bottom Message', 'bottom_button_text' => 'Bottom Button Text', 'bottom_button_url' => 'Bottom Button URL'] as $field => $label)
            <dt class="col-md-3">{{ $label }}</dt><dd class="col-md-9 text-break">{{ filled($section->{$field}) ? $section->{$field} : '—' }}</dd>
        @endforeach
        <dt class="col-md-3">Cards Per Row</dt><dd class="col-md-9">{{ $section->cards_per_row }}</dd>
        <dt class="col-md-3">Status</dt><dd class="col-md-9">{{ $section->is_active ? 'Active' : 'Inactive' }}</dd>
        <dt class="col-md-3">Created At</dt><dd class="col-md-9">{{ $section->created_at?->format('d M Y, h:i A') }}</dd>
        <dt class="col-md-3">Updated At</dt><dd class="col-md-9">{{ $section->updated_at?->format('d M Y, h:i A') }}</dd>
    </dl></div></section>
    <section class="card admin-settings-card"><div class="card-header"><h3>Cards ({{ $section->cards->count() }})</h3></div><div class="card-body">
        @forelse ($section->cards as $card)
            <article class="border rounded p-3 mb-3">
                <h4 class="h5">Card {{ $loop->iteration }}: {{ $card->title }}</h4>
                <dl class="row mb-0">
                    @foreach (['description' => 'Description', 'additional_details' => 'Additional Details', 'button_text' => 'Button Text', 'button_url' => 'Button URL'] as $field => $label)
                        <dt class="col-md-3">{{ $label }}</dt><dd class="col-md-9 text-break">{{ filled($card->{$field}) ? $card->{$field} : '—' }}</dd>
                    @endforeach
                    <dt class="col-md-3">Layout</dt><dd class="col-md-9">{{ $card->layout_type === 'full_width' ? 'Full Width' : 'Standard' }}</dd>
                    <dt class="col-md-3">Status</dt><dd class="col-md-9">{{ $card->is_active ? 'Active' : 'Inactive' }}</dd>
                </dl>
            </article>
        @empty<p class="text-muted mb-0">No cards have been added.</p>@endforelse
    </div></section>
</div>
@endsection

