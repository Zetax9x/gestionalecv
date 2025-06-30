@extends('layouts.app')

@section('title', 'Dettaglio Mezzo')
@section('page-title', 'Dettaglio Mezzo')

@section('page-actions')
    <a href="{{ route('mezzi.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Indietro
    </a>
    @can('permission', ['mezzi', 'modifica'])
    <a href="{{ route('mezzi.edit', $mezzo->id) }}" class="btn btn-primary">
        <i class="bi bi-pencil"></i> Modifica
    </a>
    @endcan
@endsection

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-4">{{ $mezzo->targa }} - {{ $mezzo->tipo_descrizione }}</h5>
            <div class="row mb-3">
                <div class="col-md-4">
                    <strong>Marca:</strong> {{ $mezzo->marca }}<br>
                    <strong>Modello:</strong> {{ $mezzo->modello }}<br>
                    <strong>Anno:</strong> {{ $mezzo->anno }}
                </div>
                <div class="col-md-4">
                    <strong>Km attuali:</strong> {{ number_format($mezzo->km_attuali) }}<br>
                    <strong>Stato:</strong> {{ $mezzo->stato_descrizione }}<br>
                    <strong>Posizione:</strong> {{ $mezzo->posizione_attuale ?? '-' }}
                </div>
                <div class="col-md-4">
                    <strong>Revisione:</strong> {{ optional($mezzo->scadenza_revisione)->format('d/m/Y') }}<br>
                    <strong>Assicurazione:</strong> {{ optional($mezzo->scadenza_assicurazione)->format('d/m/Y') }}<br>
                    <strong>Bollo:</strong> {{ optional($mezzo->scadenza_bollo)->format('d/m/Y') }}
                </div>
            </div>
            <div class="mb-3">
                <strong>Note:</strong><br>
                {{ $mezzo->note ?? '-' }}
            </div>
        </div>
    </div>
</div>
@endsection
