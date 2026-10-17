@extends('layouts.adminLayout.admin-design')

@section('title', 'Who I Help')

@section('content')
    @php($sectionExists = $sections->isNotEmpty())

    <div class="container-fluid">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h2 class="mb-1">Who I Help</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Who I Help</li>
                    </ol>
                </nav>
            </div>

            @can('who-i-help.create')
                @if ($sectionExists)
                    <span title="Only one Who I Help section is allowed for the configured language.">
                        <button class="btn btn-secondary opacity-75" type="button" disabled aria-disabled="true" style="cursor: not-allowed;">
                            <i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Add New
                        </button>
                    </span>
                @else
                    <a class="btn btn-primary admin-primary-button" href="{{ route('admin.who-i-help.create') }}">
                        <i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Add New
                    </a>
                @endif
            @endcan
        </div>

        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif
        @if (session('warning'))
            <div class="alert alert-warning" role="alert">{{ session('warning') }}</div>
        @endif

        <div class="card admin-settings-card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100" data-admin-datatable data-admin-serials>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Small Heading</th>
                                <th>Main Heading</th>
                                <th>Total Cards</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sections as $section)
                                <tr>
                                    <td>{{ $section->id }}</td>
                                    <td>{{ $section->eyebrow }}</td>
                                    <td>{{ $section->heading }}</td>
                                    <td>{{ $section->items_count }}</td>
                                    <td><span class="badge {{ $section->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $section->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td>{{ $section->created_at?->format('d M Y, h:i A') ?? 'â€”' }}</td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            @can('who-i-help.view')
                                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.who-i-help.show', $section->id) }}" aria-label="View Who I Help section"><i class="fa-solid fa-eye"></i></a>
                                            @endcan
                                            @can('who-i-help.edit')
                                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.who-i-help.edit', $section->id) }}" aria-label="Edit Who I Help section"><i class="fa-solid fa-pen-to-square"></i></a>
                                            @endcan
                                            @can('who-i-help.delete')
                                                <form method="POST" action="{{ route('admin.who-i-help.destroy', $section->id) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger" type="submit" data-delete-confirm data-delete-title="Delete this Who I Help section?" aria-label="Delete Who I Help section"><i class="fa-solid fa-trash"></i></button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center text-muted py-4" colspan="7">No Who I Help section has been saved yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection