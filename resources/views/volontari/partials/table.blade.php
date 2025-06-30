<div class="table-responsive">
    <table class="table table-hover">
        <thead class="table-light">
            <tr>
                <th>
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'tessera_numero', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}" 
                       class="text-decoration-none text-dark">
                        Tessera
                        @if(request('sort') === 'tessera_numero')
                            <i class="bi bi-arrow-{{ request('direction') === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </a>
                </th>
                <th>
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'nome_completo', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}" 
                       class="text-decoration-none text-dark">
                        Nome Completo
                        @if(request('sort') === 'nome_completo')
                            <i class="bi bi-arrow-{{ request('direction') === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </a>
                </th>
                <th>Email</th>
                <th>Telefono</th>
                <th>Stato Formazione</th>
                <th>Disponibilità</th>
                <th>Stato</th>
                <th>Scadenze</th>
                <th width="150">Azioni</th>
            </tr>
        </thead>
        <tbody>
            @forelse($volontari as $volontario)
            <tr>
                <td>
                    <span class="badge bg-primary">{{ $volontario->tessera_numero }}</span>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm me-2">
                            <img src="{{ $volontario->user->avatar_url }}" 
                                 alt="Avatar" 
                                 class="rounded-circle" 
                                 width="32" height="32">
                        </div>
                        <div>
                            <div class="fw-semibold">{{ $volontario->user->nome_completo }}</div>
                            <small class="text-muted">{{ $volontario->anni_servizio }} anni di servizio</small>
                        </div>
                    </div>
                </td>
                <td>
                    <a href="mailto:{{ $volontario->user->email }}" class="text-decoration-none">
                        {{ $volontario->user->email }}
                    </a>
                </td>
                <td>
                    @if($volontario->user->telefono)
                        <a href="tel:{{ $volontario->user->telefono }}" class="text-decoration-none">
                            {{ $volontario->user->telefono }}
                        </a>
                    @else
                        <span class="text-muted">Non disponibile</span>
                    @endif
                </td>
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
                <td>
                    @if($volontario->attivo)
                        <span class="badge bg-success">
                            <i class="bi bi-check-circle"></i> Attivo
                        </span>
                    @else
                        <span class="badge bg-danger">
                            <i class="bi bi-x-circle"></i> Sospeso
                        </span>
                    @endif
                </td>
                <td>
                    @php
                        $scadenze = $volontario->scadenze_vicine;
                    @endphp
                    @if($scadenze->isNotEmpty())
                        @foreach($scadenze as $scadenza)
                            <span class="badge bg-{{ $scadenza['urgente'] ? 'danger' : 'warning' }} d-block mb-1">
                                {{ $scadenza['tipo'] }}: {{ $scadenza['giorni'] }}gg
                            </span>
                        @endforeach
                    @else
                        <span class="text-success">
                            <i class="bi bi-check-circle"></i> OK
                        </span>
                    @endif
                </td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="{{ route('volontari.show', $volontario->id) }}" 
                           class="btn btn-outline-primary" 
                           title="Visualizza">
                            <i class="bi bi-eye"></i>
                        </a>
                        @can('permission', ['volontari', 'modifica'])
                        <a href="{{ route('volontari.edit', $volontario->id) }}" 
                           class="btn btn-outline-warning" 
                           title="Modifica">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @endcan
                        @can('permission', ['volontari', 'elimina'])
                        <button type="button" 
                                class="btn btn-outline-danger" 
                                onclick="deleteVolontario({{ $volontario->id }})"
                                title="Elimina">
                            <i class="bi bi-trash"></i>
                        </button>
                        @endcan
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center py-4">
                    <div class="text-muted">
                        <i class="bi bi-people fs-1 d-block mb-2"></i>
                        Nessun volontario trovato
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
function deleteVolontario(id) {
    if (confirm('Sei sicuro di voler eliminare questo volontario?')) {
        fetch(`/volontari/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Errore: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Errore di comunicazione');
        });
    }
}
</script>