@extends('layouts.app')

@section('title', 'Modifica Dashboard')
@section('page-title', 'Modifica Dashboard')

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('dashboards.update', $dashboard->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">Nome</label>
                <input type="text" name="name" value="{{ old('name', $dashboard->name) }}" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Descrizione</label>
                <textarea name="description" class="form-control" rows="4">{{ old('description', $dashboard->description) }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Aggiorna</button>
            <a href="{{ route('dashboards.index') }}" class="btn btn-secondary">Annulla</a>
        </form>
    </div>
</div>
@endsection
