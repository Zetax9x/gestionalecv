@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header">
            <h3 class="card-title"><i class="bi bi-pencil-square me-2"></i> Modifica Volontario</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('volontari.update', $volontario->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="{{ old('nome', $volontario->user->nome) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="cognome" class="form-label">Cognome</label>
                            <input type="text" class="form-control" id="cognome" name="cognome" value="{{ old('cognome', $volontario->user->cognome) }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $volontario->user->email) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Telefono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono" value="{{ old('telefono', $volontario->user->telefono) }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tessera_numero" class="form-label">Numero Tessera</label>
                            <input type="text" class="form-control" id="tessera_numero" name="tessera_numero" value="{{ old('tessera_numero', $volontario->tessera_numero) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="data_iscrizione" class="form-label">Data Iscrizione</label>
                            <input type="date" class="form-control" id="data_iscrizione" name="data_iscrizione" value="{{ old('data_iscrizione', optional($volontario->data_iscrizione)->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i> Aggiorna
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
