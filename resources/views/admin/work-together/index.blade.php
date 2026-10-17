@extends('layouts.adminLayout.admin-design')
@section('title', 'How We Can Work Together')
@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h2>How We Can Work Together</h2><nav aria-label="breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">How We Can Work Together</li></ol></nav></div>
        <div class="d-flex flex-wrap gap-2">
        @can('work-together.view')
            <a class="btn btn-outline-primary" href="{{ route('admin.work-together.homepage-layout-settings') }}"><i class="fa-solid fa-sliders me-2" aria-hidden="true"></i>Layout Settings</a>
        @endcan
        @can('work-together.create')
            @if (! $tablesAvailable || $sections->isNotEmpty())
                <button class="btn btn-secondary" type="button" disabled aria-disabled="true" title="{{ $tablesAvailable ? 'Only one section is allowed per configured language.' : 'Complete setup before adding content.' }}">Add New</button>
            @else
                <a class="btn btn-primary admin-primary-button" href="{{ route('admin.work-together.create') }}">Add New</a>
            @endif
        @endcan
        </div>
    </div>
    @if ($setupWarning)<div class="alert alert-warning" role="alert">{{ $setupWarning }}</div>@endif
    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
    @if (session('warning'))<div class="alert alert-warning" role="alert">{{ session('warning') }}</div>@endif
    <div class="card admin-settings-card"><div class="card-body"><div class="table-responsive">
        <table class="table table-hover align-middle w-100" data-admin-datatable>
            <thead><tr><th>ID</th><th>Small Heading</th><th>Main Heading</th><th>Total Cards</th><th>Cards Per Row</th><th>Status</th><th>Created At</th><th>Actions</th></tr></thead>
            <tbody>@foreach ($sections as $section)
                <tr><td>{{ $section->id }}</td><td>{{ $section->eyebrow }}</td><td>{{ $section->heading }}</td><td>{{ $section->cards_count }}</td><td>{{ $section->cards_per_row }}</td><td><span class="badge {{ $section->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $section->is_active ? 'Active' : 'Inactive' }}</span></td><td>{{ $section->created_at?->format('d M Y, h:i A') }}</td>
                <td><div class="d-flex flex-wrap gap-2">
                    @can('work-together.view')<a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.work-together.show', $section->id) }}" aria-label="View How We Can Work Together" title="View"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>@endcan
                    @can('work-together.edit')<a class="btn btn-sm btn-outline-primary" href="{{ route('admin.work-together.edit', $section->id) }}" aria-label="Edit How We Can Work Together" title="Edit"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></a>@endcan
                    @can('work-together.delete')<form method="POST" action="{{ route('admin.work-together.destroy', $section->id) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit" data-delete-confirm data-delete-title="Delete this section and its cards?" aria-label="Delete How We Can Work Together" title="Delete"><i class="fa-solid fa-trash" aria-hidden="true"></i></button></form>@endcan
                </div></td></tr>
            @endforeach</tbody>
        </table>
        @if ($sections->isEmpty())<p class="text-muted text-center mb-0 py-4">{{ $tablesAvailable ? 'No How We Can Work Together has been saved yet.' : 'Complete setup to manage How We Can Work Together.' }}</p>@endif
    </div></div></div>
</div>
@endsection



