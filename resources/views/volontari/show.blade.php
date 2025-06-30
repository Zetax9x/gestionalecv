@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title"><i class="bi bi-person-lines-fill me-2"></i> Dettagli Volontario</h3>
            <div>
                <a href="{{ route('volontari.edit', $volontario->id) }}" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-pencil"></i> Modifica
                </a>
                <a href="{{ route('volontari.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Indietro
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <tbody>
                        <tr>
                            <th>Tessera</th>
                            <td><span class="badge bg-primary">{{ $volontario->tessera_numero }}</span></td>
                        </tr>
                        <tr>
                            <th>Nome Completo</th>
                            <td>{{ $volontario->user->nome }} {{ $volontario->user->cognome }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><a href="mailto:{{ $volontario->user->email }}">{{ $volontario->user->email }}</a></td>
                        </tr>
                        <tr>
                            <th>Telefono</th>
                            <td>
                                @if($volontario->user->telefono)
                                    <a href="tel:{{ $volontario->user->telefono }}">{{ $volontario->user->telefono }}</a>
                                @else
                                    <span class="text-muted">Non disponibile</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Stato Formazione</th>
                            <td>
                                @php
                                    $badgeClass = match($volontario->stato_formazione) {
                                        'base' => 'bg-info',
                                        'avanzato' => 'bg-success',
                                        'istruttore' => 'bg-warning',
                                        'in_corso' => 'bg-secondary',
                                        default => 'bg-light text-dark'
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">
                                    {{ ucfirst(str_replace('_', ' ', $volontario->stato_formazione)) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Disponibilità</th>
                            <td>
                                @php
                                    $dispClass = match($volontario->disponibilita) {
                                        'sempre' => 'bg-success',
                                        'weekdays' => 'bg-primary',
                                        'weekend' => 'bg-info',
                                        'sera' => 'bg-warning',
                                        'limitata' => 'bg-secondary',
                                        default => 'bg-light text-dark'
                                    };
                                @endphp
                                <span class="badge {{ $dispClass }}">
                                    {{ ucfirst($volontario->disponibilita) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Stato</th>
                            <td>
                                @if($volontario->attivo)
                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Attivo</span>
                                @else
                                    <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Sospeso</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Data Iscrizione</th>
                            <td>{{ $volontario->data_iscrizione?->format('d/m/Y') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
