@extends('layouts.app')

@section('title', 'Dettagli Dashboard')
@section('page-title', $dashboard->name)

@section('content')
<div class="card">
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">Nome</dt>
            <dd class="col-sm-9">{{ $dashboard->name }}</dd>
            <dt class="col-sm-3">Descrizione</dt>
            <dd class="col-sm-9">{{ $dashboard->description }}</dd>
            <dt class="col-sm-3">Creato il</dt>
            <dd class="col-sm-9">{{ $dashboard->created_at->format('d/m/Y') }}</dd>
        </dl>
        <a href="{{ route('dashboards.edit', $dashboard->id) }}" class="btn btn-warning">Modifica</a>
        <a href="{{ route('dashboards.index') }}" class="btn btn-secondary">Indietro</a>
    </div>
</div>
@endsection
