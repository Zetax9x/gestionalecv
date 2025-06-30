@extends('layouts.app')

@section('title', 'Gestione Permessi')
@section('page-title', 'Gestione Permessi ACL')

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Matrice Permessi per Ruolo</h5>
    </div>
    <div class="card-body">
        <form id="permissionsForm" method="POST" action="{{ route('admin.permissions.update') }}">
            @csrf
            @method('PUT')
            
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Modulo</th>
                            @foreach($ruoli as $ruolo => $label)
                                <th class="text-center">{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($moduli as $modulo => $label)
                        <tr>
                            <td><strong>{{ $label }}</strong></td>
                            @foreach($ruoli as $ruolo => $ruoloLabel)
                                @php
                                    $permission = $matrice[$modulo]['ruoli'][$ruolo] ?? null;
                                @endphp
                                <td class="text-center">
                                    <div class="btn-group-vertical btn-group-sm" role="group">
                                        <input type="checkbox" class="btn-check" 
                                               id="perm_{{ $modulo }}_{{ $ruolo }}_visualizza"
                                               name="permissions[{{ $modulo }}][{{ $ruolo }}][visualizza]"
                                               value="1"
                                               {{ $permission && $permission->visualizza ? 'checked' : '' }}>
                                        <label class="btn btn-outline-primary btn-sm" 
                                               for="perm_{{ $modulo }}_{{ $ruolo }}_visualizza">V</label>
                                        
                                        <input type="checkbox" class="btn-check" 
                                               id="perm_{{ $modulo }}_{{ $ruolo }}_crea"
                                               name="permissions[{{ $modulo }}][{{ $ruolo }}][crea]"
                                               value="1"
                                               {{ $permission && $permission->crea ? 'checked' : '' }}>
                                        <label class="btn btn-outline-success btn-sm" 
                                               for="perm_{{ $modulo }}_{{ $ruolo }}_crea">C</label>
                                        
                                        <input type="checkbox" class="btn-check" 
                                               id="perm_{{ $modulo }}_{{ $ruolo }}_modifica"
                                               name="permissions[{{ $modulo }}][{{ $ruolo }}][modifica]"
                                               value="1"
                                               {{ $permission && $permission->modifica ? 'checked' : '' }}>
                                        <label class="btn btn-outline-warning btn-sm" 
                                               for="perm_{{ $modulo }}_{{ $ruolo }}_modifica">M</label>
                                        
                                        <input type="checkbox" class="btn-check" 
                                               id="perm_{{ $modulo }}_{{ $ruolo }}_elimina"
                                               name="permissions[{{ $modulo }}][{{ $ruolo }}][elimina]"
                                               value="1"
                                               {{ $permission && $permission->elimina ? 'checked' : '' }}>
                                        <label class="btn btn-outline-danger btn-sm" 
                                               for="perm_{{ $modulo }}_{{ $ruolo }}_elimina">E</label>
                                    </div>
                                    
                                    <!-- Hidden inputs per struttura corretta -->
                                    <input type="hidden" name="permissions[{{ $modulo }}][{{ $ruolo }}][modulo]" value="{{ $modulo }}">
                                    <input type="hidden" name="permissions[{{ $modulo }}][{{ $ruolo }}][ruolo]" value="{{ $ruolo }}">
                                </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i>
                    Salva Permessi
                </button>
                <small class="text-muted ms-3">
                    V=Visualizza, C=Crea, M=Modifica, E=Elimina
                </small>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('permissionsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const permissions = [];
    
    // Converte i dati del form in formato corretto per il backend
    for (let [key, value] of formData.entries()) {
        if (key.startsWith('permissions[')) {
            // Parsing del nome del campo
            const match = key.match(/permissions\[([^\]]+)\]\[([^\]]+)\]\[([^\]]+)\]/);
            if (match) {
                const [, modulo, ruolo, azione] = match;
                
                let permission = permissions.find(p => p.modulo === modulo && p.ruolo === ruolo);
                if (!permission) {
                    permission = { modulo, ruolo, visualizza: false, crea: false, modifica: false, elimina: false };
                    permissions.push(permission);
                }
                
                if (azione !== 'modulo' && azione !== 'ruolo') {
                    permission[azione] = value === '1';
                }
            }
        }
    }
    
    // Invia i dati processati
    fetch(this.action, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ permissions })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Permessi aggiornati con successo!');
            location.reload();
        } else {
            alert('Errore: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Errore di comunicazione');
    });
});
</script>
@endsection