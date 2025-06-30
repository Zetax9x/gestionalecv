@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">🛡️ DPI</h3>
            <a href="{{ route('dpi.create') }}" class="btn btn-success">
                <i class="fas fa-plus-circle"></i> Nuovo DPI
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Stato</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dpi as $item)
                        <tr>
                            <td>{{ $item->nome }}</td>
                            <td>{{ $item->categoria_label ?? '-' }}</td>
                            <td>{{ $item->stato_label ?? '-' }}</td>
                            <td>
                                <a href="{{ route('dpi.show', $item->id) }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i> Dettagli
                                </a>
                                <a href="{{ route('dpi.edit', $item->id) }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Modifica
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">Nessun DPI trovato.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
