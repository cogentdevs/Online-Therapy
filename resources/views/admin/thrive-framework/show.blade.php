@extends('layouts.adminLayout.admin-design')
@section('title', 'THRIVE Framework Details')
@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between gap-3 mb-4"><div><h2>THRIVE Framework Details</h2><nav aria-label="breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('admin.thrive-framework.index') }}">THRIVE Framework</a></li><li class="breadcrumb-item active">Details</li></ol></nav></div>@can('thrive-framework.edit')<a class="btn btn-primary align-self-start" href="{{ route('admin.thrive-framework.edit', $section->id) }}">Edit</a>@endcan</div>
    <section class="card admin-settings-card mb-4"><div class="card-body"><dl class="row mb-0">
        <dt class="col-md-3">Small Heading</dt><dd class="col-md-9">{{ $section->eyebrow }}</dd>
        <dt class="col-md-3">Main Heading</dt><dd class="col-md-9">{{ $section->heading }}</dd>
        <dt class="col-md-3">Description One</dt><dd class="col-md-9">{{ $section->description_one ?: '—' }}</dd>
        <dt class="col-md-3">Description Two</dt><dd class="col-md-9">{{ $section->description_two ?: '—' }}</dd>
        <dt class="col-md-3">Status</dt><dd class="col-md-9">{{ $section->is_active ? 'Active' : 'Inactive' }}</dd>
        <dt class="col-md-3">Cards Per Row</dt><dd class="col-md-9">{{ $section->cards_per_row ?? 3 }} Cards</dd>
    </dl></div></section>
    <section class="card admin-settings-card"><div class="card-header"><h3>Framework Stages ({{ $section->stages->count() }})</h3></div><div class="card-body">
        @forelse ($section->stages as $stage)
            <article class="border rounded p-3 mb-3"><h4>{{ $stage->letter }} — {{ $stage->title }}</h4><p>{{ $stage->stage_label ?: 'STAGE '.$loop->iteration }}</p><p class="text-break">{{ $stage->description }}</p><span class="badge {{ $stage->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $stage->is_active ? 'Active' : 'Inactive' }}</span></article>
        @empty<p class="text-muted mb-0">No stages have been added.</p>@endforelse
    </div></section>
</div>
@endsection

