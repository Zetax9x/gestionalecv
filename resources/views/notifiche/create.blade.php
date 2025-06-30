@extends('layouts.app')

@section('title', 'Nuova Notifica')
@section('page-title', 'Crea Notifica')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header">
            <h3 class="card-title">Invia Notifica</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('notifiche.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Destinatari</label>
                    <select name="destinatari[]" class="form-select" multiple required>
                        @foreach($utenti as $utente)
                            <option value="{{ $utente->id }}" @selected(in_array($utente->id, old('destinatari', [])))>
                                {{ $utente->nome_completo }} ({{ $utente->ruolo_label }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipo Notifica</label>
                    <select name="tipo" class="form-select" required>
                        @foreach($tipi_notifiche as $key => $label)
                            <option value="{{ $key }}" @selected(old('tipo')=== $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Titolo</label>
                    <input type="text" name="titolo" class="form-control" value="{{ old('titolo') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Messaggio</label>
                    <textarea name="messaggio" class="form-control" rows="4" required>{{ old('messaggio') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Priorità</label>
                    <select name="priorita" class="form-select" required>
                        @foreach($priorita_levels as $level => $label)
                            <option value="{{ $level }}" @selected(old('priorita')=== $level)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">URL Azione</label>
                        <input type="url" name="url_azione" class="form-control" value="{{ old('url_azione') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Testo Azione</label>
                        <input type="text" name="testo_azione" class="form-control" value="{{ old('testo_azione') }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Scade il</label>
                        <input type="date" name="scade_il" class="form-control" value="{{ old('scade_il') }}">
                    </div>
                    <div class="col-md-3 mb-3 form-check mt-4">
                        <input type="checkbox" name="invia_email" class="form-check-input" id="chkEmail" value="1" {{ old('invia_email') ? 'checked' : '' }}>
                        <label class="form-check-label" for="chkEmail">Invia anche via email</label>
                    </div>
                    <div class="col-md-3 mb-3 form-check mt-4">
                        <input type="checkbox" name="invia_push" class="form-check-input" id="chkPush" value="1" {{ old('invia_push') ? 'checked' : '' }}>
                        <label class="form-check-label" for="chkPush">Invia notifica push</label>
                    </div>
                </div>
                <div class="text-end">
                    <a href="{{ route('notifiche.index') }}" class="btn btn-secondary">Annulla</a>
                    <button type="submit" class="btn btn-primary">Invia</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

