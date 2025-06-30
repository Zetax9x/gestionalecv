@extends('layouts.app')

@section('page-title', 'Nuovo Evento')

@section('content')
<div class="container-fluid">
    <form action="{{ route('eventi.store') }}" method="POST">
        @csrf
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title">Crea Evento</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Titolo</label>
                    <input type="text" name="titolo" class="form-control" value="{{ old('titolo') }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Descrizione</label>
                    <textarea name="descrizione" rows="4" class="form-control" required>{{ old('descrizione') }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tipo</label>
                    <select name="tipo_evento" class="form-select" required>
                        <option value="">Seleziona</option>
                        @foreach($tipi_evento as $key => $label)
                            <option value="{{ $key }}" {{ old('tipo_evento') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Data Inizio</label>
                        <input type="datetime-local" name="data_inizio" class="form-control" value="{{ old('data_inizio') }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Data Fine</label>
                        <input type="datetime-local" name="data_fine" class="form-control" value="{{ old('data_fine') }}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Luogo</label>
                    <input type="text" name="luogo" class="form-control" value="{{ old('luogo') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Max Partecipanti</label>
                    <input type="number" name="max_partecipanti" class="form-control" value="{{ old('max_partecipanti') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Note</label>
                    <textarea name="note" rows="3" class="form-control">{{ old('note') }}</textarea>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('eventi.index') }}" class="btn btn-secondary">Annulla</a>
                <button type="submit" class="btn btn-primary">Salva</button>
            </div>
        </div>
    </form>
</div>
@endsection
