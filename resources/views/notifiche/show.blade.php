@extends('layouts.app')

@section('title', 'Dettaglio Notifica')
@section('page-title', 'Notifica')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">{{ $notifica->titolo }}</h3>
            <a href="{{ route('notifiche.index') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Indietro
            </a>
        </div>
        <div class="card-body">
            <p class="text-muted">Tipo: {{ $notifica->tipo }}</p>
            <p class="text-muted">Priorità: {{ ucfirst($notifica->priorita) }}</p>
            <p class="text-muted">Data: {{ $notifica->created_at->format('d/m/Y H:i') }}</p>
            <hr>
            <p>{{ $notifica->messaggio }}</p>
            @if($notifica->url_azione)
                <a href="{{ $notifica->url_azione }}" class="btn btn-primary" target="_blank">
                    {{ $notifica->testo_azione ?? 'Apri collegamento' }}
                </a>
            @endif
        </div>
    </div>
</div>
@endsection

