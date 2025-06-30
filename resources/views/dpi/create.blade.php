@extends('layouts.app')

@section('title', 'Nuovo DPI')
@section('page-title', 'Nuovo DPI')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">Crea DPI</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('dpi.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nome" class="form-label">Nome</label>
                        <input type="text" id="nome" name="nome" class="form-control" value="{{ old('nome') }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="categoria" class="form-label">Categoria</label>
                        <select id="categoria" name="categoria" class="form-select" required>
                            <option value="">Seleziona...</option>
                            @foreach($categorie as $key => $label)
                                <option value="{{ $key }}" {{ old('categoria') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="taglia" class="form-label">Taglia</label>
                        <input type="text" id="taglia" name="taglia" class="form-control" value="{{ old('taglia') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="stato" class="form-label">Stato</label>
                        <select id="stato" name="stato" class="form-select">
                            @php
                                $stati = [
                                    'nuovo' => 'Nuovo',
                                    'buono' => 'Buone Condizioni',
                                    'usato' => 'Usato',
                                    'da_controllare' => 'Da Controllare',
                                    'da_sostituire' => 'Da Sostituire',
                                    'dismesso' => 'Dismesso'
                                ];
                            @endphp
                            @foreach($stati as $val => $text)
                                <option value="{{ $val }}" {{ old('stato') == $val ? 'selected' : '' }}>{{ $text }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 mb-3">
                        <label for="descrizione" class="form-label">Descrizione</label>
                        <textarea id="descrizione" name="descrizione" class="form-control" rows="3">{{ old('descrizione') }}</textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="foto" class="form-label">Foto</label>
                        <input type="file" id="foto" name="foto" class="form-control">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Salva
                </button>
                <a href="{{ route('dpi.index') }}" class="btn btn-secondary">Annulla</a>
            </form>
        </div>
    </div>
</div>
@endsection
