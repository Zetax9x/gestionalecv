@extends('layouts.app')

@section('title', $dpi->nome)
@section('page-title', $dpi->nome)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Dettagli DPI</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Categoria:</strong> {{ $dpi->categoria_label }}</p>
                    <p class="mb-2"><strong>Codice:</strong> {{ $dpi->codice_dpi }}</p>
                    <p class="mb-2"><strong>Taglia:</strong> {{ $dpi->taglia ?? '-' }}</p>
                    <p class="mb-2"><strong>Stato:</strong> <span class="badge bg-{{ $dpi->colore_stato }}">{{ $dpi->stato_label }}</span></p>
                    @if($dpi->descrizione)
                        <p class="mb-0"><strong>Descrizione:</strong><br>{{ $dpi->descrizione }}</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Statistiche</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <strong>Utilizzi effettuati:</strong> {{ $statistiche['utilizzi_effettuati'] }}
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Utilizzi residui:</strong> {{ $statistiche['utilizzi_residui'] ?? '-' }}
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Età DPI:</strong> {{ $statistiche['eta_dpi'] ? $statistiche['eta_dpi'].' mesi' : '-' }}
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Giorni residui:</strong> {{ $statistiche['giorni_residui'] ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Storico Assegnazioni</h5>
                </div>
                <div class="card-body">
                    @if($storicoAssegnazioni->isEmpty())
                        <p class="text-muted mb-0">Nessuna assegnazione registrata.</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($storicoAssegnazioni as $assegnazione)
                                <li class="list-group-item">
                                    {{ $assegnazione->volontario->user->nome_completo }} - {{ $assegnazione->data_assegnazione->format('d/m/Y') }}
                                    @if($assegnazione->restituito)
                                        <span class="text-muted">(restituito {{ $assegnazione->data_restituzione?->format('d/m/Y') }})</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
