@extends('layouts.adminLayout.admin-design')
@section('title', 'Edit THRIVE Framework')
@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between gap-3 mb-4"><div><h2>Edit THRIVE Framework</h2><nav aria-label="breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('admin.thrive-framework.index') }}">THRIVE Framework</a></li><li class="breadcrumb-item active">Edit</li></ol></nav></div></div>
    @include('admin.thrive-framework._form')
</div>
@endsection

