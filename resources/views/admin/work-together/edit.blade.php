@extends('layouts.adminLayout.admin-design')
@section('title', 'Edit How We Can Work Together')
@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h2>Edit How We Can Work Together</h2><nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.work-together.index') }}">How We Can Work Together</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol></nav></div>
        
    </div>
    @include('admin.work-together._form')
</div>
@endsection

