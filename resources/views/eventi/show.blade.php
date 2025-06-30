@extends('layouts.app')

@section('page-title', 'Dettaglio Evento')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">{{ $evento->titolo }}</h3>
            <div>
                <a href="{{ route('eventi.edit', $evento->id) }}" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-pencil"></i> Modifica
                </a>
                <a href="{{ route('eventi.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Indietro
                </a>
            </div>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Tipo</dt>
                <dd class="col-sm-9">{{ ucfirst(str_replace('_',' ', $evento->tipo_evento ?? $evento->tipo)) }}</dd>

                <dt class="col-sm-3">Periodo</dt>
                <dd class="col-sm-9">
                    {{ $evento->data_inizio->format('d/m/Y H:i') }} -
                    {{ $evento->data_fine->format('d/m/Y H:i') }}
                </dd>

                <dt class="col-sm-3">Luogo</dt>
                <dd class="col-sm-9">{{ $evento->luogo }}</dd>

                <dt class="col-sm-3">Stato</dt>
                <dd class="col-sm-9"><span class="badge bg-primary">{{ ucfirst($evento->stato) }}</span></dd>

                <dt class="col-sm-3">Descrizione</dt>
                <dd class="col-sm-9">{{ $evento->descrizione }}</dd>
            </dl>
        </div>
    </div>
</div>
@endsection
