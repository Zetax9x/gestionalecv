@extends('layouts.app')

@section('title', 'Modifica Mezzo')
@section('page-title', 'Modifica Mezzo')

@section('page-actions')
    <a href="{{ route('mezzi.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Indietro
    </a>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('mezzi.update', $mezzo->id) }}">
                @csrf
                @method('PUT')
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Targa</label>
                        <input type="text" name="targa" value="{{ old('targa', $mezzo->targa) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select" required>
                            <option value="ambulanza_a" {{ old('tipo', $mezzo->tipo)=='ambulanza_a' ? 'selected' : '' }}>Ambulanza Tipo A</option>
                            <option value="ambulanza_b" {{ old('tipo', $mezzo->tipo)=='ambulanza_b' ? 'selected' : '' }}>Ambulanza Tipo B</option>
                            <option value="auto_medica" {{ old('tipo', $mezzo->tipo)=='auto_medica' ? 'selected' : '' }}>Auto Medica</option>
                            <option value="auto_servizio" {{ old('tipo', $mezzo->tipo)=='auto_servizio' ? 'selected' : '' }}>Auto di Servizio</option>
                            <option value="furgone" {{ old('tipo', $mezzo->tipo)=='furgone' ? 'selected' : '' }}>Furgone</option>
                            <option value="altro" {{ old('tipo', $mezzo->tipo)=='altro' ? 'selected' : '' }}>Altro</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Anno</label>
                        <input type="number" name="anno" value="{{ old('anno', $mezzo->anno) }}" class="form-control" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Marca</label>
                        <input type="text" name="marca" value="{{ old('marca', $mezzo->marca) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Modello</label>
                        <input type="text" name="modello" value="{{ old('modello', $mezzo->modello) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Colore</label>
                        <input type="text" name="colore" value="{{ old('colore', $mezzo->colore) }}" class="form-control" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Alimentazione</label>
                        <select name="alimentazione" class="form-select" required>
                            <option value="benzina" {{ old('alimentazione', $mezzo->alimentazione)=='benzina' ? 'selected' : '' }}>Benzina</option>
                            <option value="diesel" {{ old('alimentazione', $mezzo->alimentazione)=='diesel' ? 'selected' : '' }}>Diesel</option>
                            <option value="gpl" {{ old('alimentazione', $mezzo->alimentazione)=='gpl' ? 'selected' : '' }}>GPL</option>
                            <option value="metano" {{ old('alimentazione', $mezzo->alimentazione)=='metano' ? 'selected' : '' }}>Metano</option>
                            <option value="elettrico" {{ old('alimentazione', $mezzo->alimentazione)=='elettrico' ? 'selected' : '' }}>Elettrico</option>
                            <option value="ibrido" {{ old('alimentazione', $mezzo->alimentazione)=='ibrido' ? 'selected' : '' }}>Ibrido</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Km Attuali</label>
                        <input type="number" name="km_attuali" value="{{ old('km_attuali', $mezzo->km_attuali) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Intervallo Tagliando (km)</label>
                        <input type="number" name="intervallo_tagliando" value="{{ old('intervallo_tagliando', $mezzo->intervallo_tagliando) }}" class="form-control" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Scadenza Revisione</label>
                        <input type="date" name="scadenza_revisione" value="{{ old('scadenza_revisione', optional($mezzo->scadenza_revisione)->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Scadenza Assicurazione</label>
                        <input type="date" name="scadenza_assicurazione" value="{{ old('scadenza_assicurazione', optional($mezzo->scadenza_assicurazione)->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Scadenza Bollo</label>
                        <input type="date" name="scadenza_bollo" value="{{ old('scadenza_bollo', optional($mezzo->scadenza_bollo)->format('Y-m-d')) }}" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Note</label>
                    <textarea name="note" class="form-control" rows="3">{{ old('note', $mezzo->note) }}</textarea>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg"></i> Aggiorna
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
