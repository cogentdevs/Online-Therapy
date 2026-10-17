@extends('layouts.adminLayout.admin-design')
@section('title', 'Add Home Hero')
@section('content')
<div class="container-fluid"><div class="mb-4"><h2 class="mb-1">Add Home Hero</h2><nav aria-label="breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('admin.home-hero.index') }}">Home Hero</a></li><li class="breadcrumb-item active">Add</li></ol></nav></div>@include('admin.home-hero._form')</div>
@endsection
