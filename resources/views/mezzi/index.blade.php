@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">🚛 Mezzi</h3>
            <a href="{{ route('mezzi.create') }}" class="btn btn-success">
                <i class="fas fa-plus-circle"></i> Aggiungi Mezzo
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Targa</th>
                            <th>Modello</th>
                            <th>Stato</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($mezzi as $mezzo)
                        <tr>
                            <td>{{ $mezzo->targa }}</td>
                            <td>{{ $mezzo->modello }}</td>
                            <td>{{ ucfirst($mezzo->stato) }}</td>
                            <td>
                                <a href="{{ route('mezzi.edit', $mezzo->id) }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Modifica
                                </a>
                                <form action="{{ route('mezzi.destroy', $mezzo->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Eliminare questo mezzo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash-alt"></i> Elimina
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">Nessun mezzo trovato.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
