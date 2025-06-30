@extends('layouts.app')

@section('title', 'Nuova Dashboard')
@section('page-title', 'Crea Dashboard')

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('dashboards.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label">Nome</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Descrizione</label>
                <textarea name="description" class="form-control" rows="4"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Salva</button>
            <a href="{{ route('dashboards.index') }}" class="btn btn-secondary">Annulla</a>
        </form>
    </div>
</div>
@endsection
