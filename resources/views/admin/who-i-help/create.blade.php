@extends('layouts.adminLayout.admin-design')
@section('title', 'Add Who I Help')
@section('content')
<div class="container-fluid">
    <div class="mb-4"><h2 class="mb-1">Add Who I Help</h2><nav aria-label="breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('admin.who-i-help.index') }}">Who I Help</a></li><li class="breadcrumb-item active">Add</li></ol></nav></div>
    @if ($errors->any())<div class="alert alert-danger" role="alert"><strong>Please correct the highlighted fields.</strong></div>@endif
    @include('admin.who-i-help._form')
</div>
@endsection