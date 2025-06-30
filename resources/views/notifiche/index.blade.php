@extends('layouts.app')

@section('title', 'Notifiche')
@section('page-title', 'Centro Notifiche')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="text-muted mb-1">Totali</div>
                    <h3 class="mb-0">{{ $stats['totali'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="text-muted mb-1">Non Lette</div>
                    <h3 class="mb-0">{{ $stats['non_lette'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="text-muted mb-1">Oggi</div>
                    <h3 class="mb-0">{{ $stats['oggi'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="text-muted mb-1">Questa settimana</div>
                    <h3 class="mb-0">{{ $stats['questa_settimana'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">🔔 Notifiche</h3>
            <a href="{{ route('notifiche.create') }}" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Nuova Notifica
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Titolo</th>
                            <th>Tipo</th>
                            <th>Priorità</th>
                            <th>Data</th>
                            <th>Stato</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notifiche as $notifica)
                        <tr>
                            <td>{{ $notifica->titolo }}</td>
                            <td>{{ $tipi_notifiche[$notifica->tipo] ?? $notifica->tipo }}</td>
                            <td>{{ ucfirst($notifica->priorita) }}</td>
                            <td>{{ $notifica->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @if($notifica->read_at)
                                    <span class="badge bg-success">Letta</span>
                                @else
                                    <span class="badge bg-warning text-dark">Non letta</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('notifiche.show', $notifica->id) }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <form action="{{ route('notifiche.destroy', $notifica->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Eliminare questa notifica?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Nessuna notifica trovata.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $notifiche->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

