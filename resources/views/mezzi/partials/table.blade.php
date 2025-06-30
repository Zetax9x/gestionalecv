<div class="table-responsive">
    <table class="table table-hover">
        <thead class="table-light">
            <tr>
                <th>
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'targa', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}" 
                       class="text-decoration-none text-dark">
                        Targa
                        @if(request('sort') === 'targa')
                            <i class="bi bi-arrow-{{ request('direction') === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </a>
                </th>
                <th>Tipo Mezzo</th>
                <th>Marca/Modello</th>
                <th>Anno</th>
                <th>Km Attuali</th>
                <th>Stato</th>
                <th>Scadenze</th>
                <th>Ultima Checklist</th>
                <th width="150">Azioni</th>
            </tr>
        </thead>
        <tbody>
            @forelse($mezzi as $mezzo)
            <tr>
                <td>
                    <span class="fw-bold fs-5">{{ $mezzo->targa }}</span>
                </td>
                <td>
                    @php
                        $tipoIcon = match($mezzo->tipo) {
                            'ambulanza_a' => 'truck-front',
                            'ambulanza_b' => 'truck-front',
                            'auto_medica' => 'car-front',
                            'auto_servizio' => 'car-front',
                            'furgone' => 'truck',
                            default => 'truck'
                        };
                        $tipoColor = match($mezzo->tipo) {
                            'ambulanza_a' => 'text-danger',
                            'ambulanza_b' => 'text-warning',
                            'auto_medica' => 'text-primary',
                            default => 'text-secondary'
                        };
                    @endphp
                    <div class="d-flex align-items-center">
                        <i class="bi bi-{{ $tipoIcon }} {{ $tipoColor }} me-2 fs-5"></i>
                        <span>{{ $mezzo->tipo_descrizione }}</span>
                    </div>
                </td>
                <td>
                    <div>
                        <div class="fw-semibold">{{ $mezzo->marca }} {{ $mezzo->modello }}</div>
                        <small class="text-muted">{{ $mezzo->alimentazione }}</small>
                    </div>
                </td>
                <td>
                    <span class="badge bg-{{ $mezzo->eta_veicolo > 10 ? 'warning' : 'info' }}">
                        {{ $mezzo->anno }}
                    </span>
                    <small class="text-muted d-block">{{ $mezzo->eta_veicolo }} anni</small>
                </td>
                <td>
                    <div>
                        <strong>{{ number_format($mezzo->km_attuali) }} km</strong>
                        @if($mezzo->km_prossimo_tagliando)
                            @php
                                $kmMancanti = $mezzo->km_prossimo_tagliando - $mezzo->km_attuali;
                            @endphp
                            <small class="text-muted d-block">
                                Tag: {{ $kmMancanti > 0 ? $kmMancanti . ' km' : 'Scaduto' }}
                            </small>
                        @endif
                    </div>
                </td>
                <td>
                    <span class="badge bg-{{ $mezzo->colore_stato }}">
                        {{ $mezzo->stato_descrizione }}
                    </span>
                    @if($mezzo->ticketsAperti->count() > 0)
                        <small class="text-danger d-block">
                            <i class="bi bi-exclamation-triangle"></i>
                            {{ $mezzo->ticketsAperti->count() }} ticket aperti
                        </small>
                    @endif
                </td>
                <td>
                    @php
                        $scadenze = $mezzo->scadenze_vicine;
                    @endphp
                    @if($scadenze->isNotEmpty())
                        @foreach($scadenze->take(2) as $scadenza)
                            <span class="badge bg-{{ $scadenza['urgente'] ? 'danger' : 'warning' }} d-block mb-1">
                                {{ $scadenza['tipo'] }}: 
                                @if(isset($scadenza['giorni']))
                                    {{ $scadenza['giorni'] }}gg
                                @else
                                    {{ $scadenza['km_mancanti'] }}km
                                @endif
                            </span>
                        @endforeach
                        @if($scadenze->count() > 2)
                            <small class="text-muted">+{{ $scadenze->count() - 2 }} altre</small>
                        @endif
                    @else
                        <span class="text-success">
                            <i class="bi bi-check-circle"></i> OK
                        </span>
                    @endif
                </td>
                <td>
                    @if($mezzo->ultimaChecklist)
                        <div>
                            <span class="badge bg-{{ $mezzo->ultimaChecklist->conforme ? 'success' : 'danger' }}">
                                {{ $mezzo->ultimaChecklist->conforme ? 'Conforme' : 'Non conforme' }}
                            </span>
                            <small class="text-muted d-block">
                                {{ $mezzo->ultimaChecklist->data_compilazione->format('d/m/Y') }}
                            </small>
                        </div>
                    @else
                        <span class="text-muted">Nessuna checklist</span>
                    @endif
                </td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="{{ route('mezzi.show', $mezzo->id) }}" 
                           class="btn btn-outline-primary" 
                           title="Visualizza">
                            <i class="bi bi-eye"></i>
                        </a>
                        @can('permission', ['mezzi', 'modifica'])
                        <a href="{{ route('mezzi.edit', $mezzo->id) }}" 
                           class="btn btn-outline-warning" 
                           title="Modifica">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @endcan
                        <div class="btn-group">
                            <button type="button" 
                                    class="btn btn-outline-secondary dropdown-toggle" 
                                    data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="{{ route('mezzi.checklist', $mezzo->id) }}">
                                        <i class="bi bi-clipboard-check"></i> Checklist
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('mezzi.manutenzioni', $mezzo->id) }}">
                                        <i class="bi bi-wrench"></i> Manutenzioni
                                    </a>
                                </li>
                                @can('permission', ['mezzi', 'elimina'])
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="#" onclick="deleteMezzo({{ $mezzo->id }})">
                                        <i class="bi bi-trash"></i> Elimina
                                    </a>
                                </li>
                                @endcan
                            </ul>
                        </div>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center py-4">
                    <div class="text-muted">
                        <i class="bi bi-truck fs-1 d-block mb-2"></i>
                        Nessun mezzo trovato
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
function deleteMezzo(id) {
    if (confirm('Sei sicuro di voler eliminare questo mezzo?')) {
        fetch(`/mezzi/${id}`, {
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