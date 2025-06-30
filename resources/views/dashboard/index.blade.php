@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="row">
    <!-- Quick Stats -->
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Eventi Totali</h6>
                        <h3 class="mb-0">{{ $statistiche['eventi']['totali'] ?? 0 }}</h3>
                    </div>
                    <div class="text-primary">
                        <i class="bi bi-calendar-event fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Volontari Attivi</h6>
                        <h3 class="mb-0">{{ $statistiche['volontari']['attivi'] ?? 0 }}</h3>
                    </div>
                    <div class="text-success">
                        <i class="bi bi-people fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Mezzi Operativi</h6>
                        <h3 class="mb-0">{{ $statistiche['mezzi']['disponibili'] ?? 0 }}</h3>
                    </div>
                    <div class="text-warning">
                        <i class="bi bi-truck fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Tickets Aperti</h6>
                        <h3 class="mb-0">{{ $statistiche['tickets']['aperti'] ?? 0 }}</h3>
                    </div>
                    <div class="text-danger">
                        <i class="bi bi-ticket-perforated fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
@if(!empty($quickActions))
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-lightning-charge"></i>
                    Azioni Rapide
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($quickActions as $action)
                    <div class="col-lg-4 col-md-6 mb-3">
                        <a href="{{ $action['url'] }}" class="card quick-action-card text-decoration-none">
                            <div class="card-body text-center">
                                <i class="bi bi-{{ $action['icona'] }} fs-1 text-{{ $action['colore'] }} mb-3"></i>
                                <h6 class="card-title">{{ $action['titolo'] }}</h6>
                                <p class="card-text text-muted small">{{ $action['descrizione'] }}</p>
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Alerts -->
@if(!empty($alerts))
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-exclamation-triangle"></i>
                    Avvisi Sistema
                </h5>
            </div>
            <div class="card-body">
                @foreach($alerts as $alert)
                <div class="alert alert-{{ $alert['tipo'] === 'warning' ? 'warning' : 'info' }} mb-2">
                    <strong>{{ $alert['titolo'] }}</strong><br>
                    {{ $alert['messaggio'] }}
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif
@endsection