@extends('layouts.app')

@section('title', 'Modifica Ticket')
@section('page-title', 'Modifica Ticket')

@section('content')
<form method="POST" action="{{ route('tickets.update', $ticket) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-title mb-0">Dettagli Ticket</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Titolo</label>
                <input type="text" name="titolo" class="form-control" value="{{ old('titolo', $ticket->titolo) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Descrizione</label>
                <textarea name="descrizione" class="form-control" rows="4" required>{{ old('descrizione', $ticket->descrizione) }}</textarea>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Categoria</label>
                    <select name="categoria" class="form-select" required>
                        @php
                            $cats = ['mezzi'=>'Mezzi','dpi'=>'DPI','magazzino'=>'Magazzino','strutture'=>'Strutture','informatica'=>'Informatica','formazione'=>'Formazione','amministrativo'=>'Amministrativo','sicurezza'=>'Sicurezza','altro'=>'Altro'];
                        @endphp
                        @foreach($cats as $key=>$label)
                            <option value="{{ $key }}" {{ old('categoria', $ticket->categoria)===$key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Priorità</label>
                    <select name="priorita" class="form-select" required>
                        @foreach(['bassa'=>'Bassa','media'=>'Media','alta'=>'Alta','critica'=>'Critica'] as $key=>$label)
                            <option value="{{ $key }}" {{ old('priorita', $ticket->priorita)===$key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Urgenza</label>
                    <select name="urgenza" class="form-select" required>
                        @foreach(['non_urgente'=>'Non urgente','normale'=>'Normale','urgente'=>'Urgente','critica'=>'Critica'] as $key=>$label)
                            <option value="{{ $key }}" {{ old('urgenza', $ticket->urgenza)===$key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Mezzo</label>
                    <select name="mezzo_id" class="form-select">
                        <option value="">--</option>
                        @foreach($mezzi as $m)
                            <option value="{{ $m->id }}" {{ old('mezzo_id', $ticket->mezzo_id)==$m->id ? 'selected' : '' }}>{{ $m->targa }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">DPI</label>
                    <select name="dpi_id" class="form-select">
                        <option value="">--</option>
                        @foreach($dpi as $d)
                            <option value="{{ $d->id }}" {{ old('dpi_id', $ticket->dpi_id)==$d->id ? 'selected' : '' }}>{{ $d->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Articolo Magazzino</label>
                    <select name="articolo_magazzino_id" class="form-select">
                        <option value="">--</option>
                        @foreach($articoliMagazzino as $a)
                            <option value="{{ $a->id }}" {{ old('articolo_magazzino_id', $ticket->articolo_magazzino_id)==$a->id ? 'selected' : '' }}>{{ $a->nome_articolo }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Ubicazione problema</label>
                <input type="text" name="ubicazione_problema" class="form-control" value="{{ old('ubicazione_problema', $ticket->ubicazione_problema) }}">
            </div>
            <div class="form-check mb-2">
                <input type="checkbox" name="blocca_operativita" value="1" class="form-check-input" id="blocca" {{ old('blocca_operativita', $ticket->blocca_operativita) ? 'checked' : '' }}>
                <label for="blocca" class="form-check-label">Blocca operatività</label>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" name="richiede_approvazione" value="1" class="form-check-input" id="approvazione" {{ old('richiede_approvazione', $ticket->richiede_approvazione) ? 'checked' : '' }}>
                <label for="approvazione" class="form-check-label">Richiede approvazione</label>
            </div>
            <div class="mb-3">
                <label class="form-label">Allegati</label>
                <input type="file" name="allegati[]" class="form-control" multiple>
            </div>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check"></i> Aggiorna
            </button>
        </div>
    </div>
</form>
@endsection
