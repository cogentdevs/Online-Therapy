@extends('layouts.adminLayout.admin-design')

@section('title', 'Home Hero')

@section('content')
    @php($heroExists = $heroes->isNotEmpty())

    <div class="container-fluid">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h2 class="mb-1">Home Hero</h2>
                <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active" aria-current="page">Home Hero</li></ol></nav>
            </div>
            @can('home-hero.create')
                @if ($heroExists || ! $heroTableAvailable)
                    <span title="{{ $heroTableAvailable ? 'Only one Home Hero is allowed for the configured language.' : 'Complete Home Hero setup before adding content.' }}"><button class="btn btn-secondary opacity-75" type="button" disabled aria-disabled="true" style="cursor: not-allowed;"><i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Add New</button></span>
                @else
                    <a class="btn btn-primary admin-primary-button" href="{{ route('admin.home-hero.create') }}"><i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Add New</a>
                @endif
            @endcan
        </div>

        @if ($setupWarning)<div class="alert alert-warning" role="alert">{{ $setupWarning }}</div>@endif
        @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
        @if (session('warning'))<div class="alert alert-warning" role="alert">{{ session('warning') }}</div>@endif

        <div class="card admin-settings-card"><div class="card-body"><div class="table-responsive">
            <table class="table table-hover align-middle w-100" data-admin-datatable data-admin-serials>
                <thead><tr><th>ID</th><th>Small Heading</th><th>Main Heading</th><th>Hero Image</th><th>Status</th><th>Created At</th><th>Actions</th></tr></thead>
                <tbody>
                    @foreach ($heroes as $hero)
                        <tr>
                            <td>{{ $hero->id }}</td><td>{{ $hero->eyebrow }}</td><td>{{ $hero->heading_before_highlight }}</td>
                            <td>@if ($hero->hero_image)<img src="{{ asset($hero->hero_image) }}" alt="{{ $hero->image_alt ?: 'Hero image' }}" class="rounded object-fit-cover" width="80" height="52">@else<span class="text-muted">No image</span>@endif</td>
                            <td><span class="badge {{ $hero->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $hero->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>{{ $hero->created_at?->format('d M Y, h:i A') ?? '—' }}</td>
                            <td><div class="d-flex flex-wrap gap-2">
                                @can('home-hero.view')<a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.home-hero.show', $hero->id) }}" aria-label="View Home Hero"><i class="fa-solid fa-eye"></i></a>@endcan
                                @can('home-hero.edit')<a class="btn btn-sm btn-outline-primary" href="{{ route('admin.home-hero.edit', $hero->id) }}" aria-label="Edit Home Hero"><i class="fa-solid fa-pen-to-square"></i></a>@endcan
                                @can('home-hero.delete')<form method="POST" action="{{ route('admin.home-hero.destroy', $hero->id) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit" data-delete-confirm data-delete-title="Delete this Home Hero?" aria-label="Delete Home Hero"><i class="fa-solid fa-trash"></i></button></form>@endcan
                            </div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($heroes->isEmpty())
                <p class="text-center text-muted py-4 mb-0">{{ $heroTableAvailable ? 'No Home Hero has been saved yet.' : 'Complete setup to manage Home Hero.' }}</p>
            @endif
        </div></div></div>
    </div>
@endsection
