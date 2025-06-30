@extends('layouts.app')

@section('title', 'Dashboards')
@section('page-title', 'Gestione Dashboards')

@section('page-actions')
    <a href="{{ route('dashboards.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Nuova Dashboard
    </a>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        @include('dashboards.partials.table', ['dashboards' => $dashboards])
    </div>
</div>
@endsection
