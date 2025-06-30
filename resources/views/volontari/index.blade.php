@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">👥 Elenco Volontari</h3>
                    <a href="{{ route('volontari.create') }}" class="btn btn-success">
                        <i class="fas fa-plus-circle"></i> Aggiungi Volontario
                    </a>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Ruolo</th>
                                    <th style="width: 160px;">Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($volontari as $volontario)
                                    <tr>
                                        <td>{{ $volontario->name }}</td>
                                        <td>{{ $volontario->email }}</td>
                                        <td>{{ ucfirst($volontario->ruolo) }}</td>
                                        <td>
                                            <a href="{{ route('volontari.edit', $volontario->id) }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i> Modifica
                                            </a>
                                            <form action="{{ route('volontari.destroy', $volontario->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Sei sicuro di voler eliminare questo volontario?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash-alt"></i> Elimina
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Nessun volontario trovato.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>
@endsection
