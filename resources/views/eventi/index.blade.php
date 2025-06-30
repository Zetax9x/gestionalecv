@extends('layouts.app')

@section('page-title', 'Eventi')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">📅 Eventi</h3>
            <a href="{{ route('eventi.create') }}" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Nuovo Evento
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Titolo</th>
                            <th>Tipo</th>
                            <th>Inizio</th>
                            <th>Fine</th>
                            <th>Stato</th>
                            <th class="text-end">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($eventi as $evento)
                        <tr>
                            <td>{{ $evento->titolo }}</td>
                            <td>{{ ucfirst(str_replace('_',' ', $evento->tipo_evento ?? $evento->tipo)) }}</td>
                            <td>{{ $evento->data_inizio->format('d/m/Y H:i') }}</td>
                            <td>{{ $evento->data_fine->format('d/m/Y H:i') }}</td>
                            <td><span class="badge bg-primary">{{ ucfirst($evento->stato) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('eventi.show', $evento->id) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('eventi.edit', $evento->id) }}" class="btn btn-outline-warning btn-sm">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('eventi.destroy', $evento->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Eliminare questo evento?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Nessun evento trovato.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $eventi->links() }}
        </div>
    </div>
</div>
@endsection
