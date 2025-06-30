@extends('layouts.app')

@section('page-title', 'Modifica Evento')

@section('content')
<div class="container-fluid">
    <form action="{{ route('eventi.update', $evento->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title">Modifica Evento</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Titolo</label>
                    <input type="text" name="titolo" class="form-control" value="{{ old('titolo', $evento->titolo) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Descrizione</label>
                    <textarea name="descrizione" rows="4" class="form-control" required>{{ old('descrizione', $evento->descrizione) }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tipo</label>
                    <select name="tipo_evento" class="form-select" required>
                        <option value="">Seleziona</option>
                        @foreach($tipi_evento as $key => $label)
                            <option value="{{ $key }}" {{ (old('tipo_evento', $evento->tipo_evento ?? $evento->tipo) == $key) ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Data Inizio</label>
                        <input type="datetime-local" name="data_inizio" class="form-control" value="{{ old('data_inizio', $evento->data_inizio->format('Y-m-d\TH:i')) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Data Fine</label>
                        <input type="datetime-local" name="data_fine" class="form-control" value="{{ old('data_fine', $evento->data_fine->format('Y-m-d\TH:i')) }}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Luogo</label>
                    <input type="text" name="luogo" class="form-control" value="{{ old('luogo', $evento->luogo) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Max Partecipanti</label>
                    <input type="number" name="max_partecipanti" class="form-control" value="{{ old('max_partecipanti', $evento->max_partecipanti) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Note</label>
                    <textarea name="note" rows="3" class="form-control">{{ old('note', $evento->note) }}</textarea>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('eventi.show', $evento->id) }}" class="btn btn-secondary">Annulla</a>
                <button type="submit" class="btn btn-primary">Aggiorna</button>
            </div>
        </div>
    </form>
</div>
@endsection
