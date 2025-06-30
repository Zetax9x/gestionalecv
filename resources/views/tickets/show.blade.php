@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->numero_ticket)
@section('page-title', 'Ticket #' . $ticket->numero_ticket)

@section('page-actions')
<a href="{{ route('tickets.edit', $ticket) }}" class="btn btn-warning">
    <i class="bi bi-pencil"></i> Modifica
</a>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <h5 class="card-title">{{ $ticket->titolo }}</h5>
        <p class="mb-4">{{ $ticket->descrizione }}</p>
        <div class="row mb-3">
            <div class="col-md-4">
                <strong>Stato:</strong>
                <span class="badge bg-{{ $ticket->colore_stato }}">{{ $ticket->stato_label }}</span>
            </div>
            <div class="col-md-4">
                <strong>Priorità:</strong>
                <span class="badge bg-{{ $ticket->colore_priorita }}">{{ $ticket->priorita_label }}</span>
            </div>
            <div class="col-md-4">
                <strong>Categoria:</strong> {{ $ticket->categoria_label }}
            </div>
        </div>
        <dl class="row mb-0">
            <dt class="col-sm-3">Richiedente</dt>
            <dd class="col-sm-9">{{ $ticket->user->nome_completo }}</dd>
            <dt class="col-sm-3">Assegnato a</dt>
            <dd class="col-sm-9">{{ $ticket->assegnatario?->nome_completo ?? '—' }}</dd>
            <dt class="col-sm-3">Aperto il</dt>
            <dd class="col-sm-9">{{ $ticket->data_apertura->format('d/m/Y H:i') }}</dd>
        </dl>
    </div>
</div>
@endsection
