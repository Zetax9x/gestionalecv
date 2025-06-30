<div class="table-responsive">
    <table class="table table-hover">
        <thead class="table-light">
            <tr>
                <th width="120">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'numero_ticket', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}" 
                       class="text-decoration-none text-dark">
                        # Ticket
                        @if(request('sort') === 'numero_ticket')
                            <i class="bi bi-arrow-{{ request('direction') === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </a>
                </th>
                <th>Titolo</th>
                <th width="120">Categoria</th>
                <th width="100">Priorità</th>
                <th width="120">Stato</th>
                <th width="150">Assegnato a</th>
                <th width="120">Data Apertura</th>
                <th width="150">Azioni</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $ticket)
            <tr class="{{ $ticket->blocca_operativita ? 'table-danger' : '' }}">
                <td>
                    <div class="d-flex align-items-center">
                        @if($ticket->blocca_operativita)
                            <i class="bi bi-exclamation-triangle-fill text-danger me-1" title="Blocca operatività"></i>
                        @endif
                        <span class="fw-bold">{{ $ticket->numero_ticket }}</span>
                    </div>
                </td>
                <td>
                    <div>
                        <div class="fw-semibold">{{ $ticket->titolo }}</div>
                        <small class="text-muted">
                            da {{ $ticket->user->nome_completo }}
                        </small>
                        @if($ticket->risorse_collegate->isNotEmpty())
                            <div class="mt-1">
                                @foreach($ticket->risorse_collegate as $risorsa)
                                    <span class="badge bg-light text-dark">
                                        {{ $risorsa['tipo'] }}: {{ $risorsa['nome'] }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </td>
                <td>
                    @php
                        $categoriaIcon = match($ticket->categoria) {
                            'mezzi' => 'truck',
                            'dpi' => 'shield-check',
                            'magazzino' => 'boxes',
                            'strutture' => 'building',
                            'informatica' => 'laptop',
                            'formazione' => 'mortarboard',
                            'amministrativo' => 'file-text',
                            'sicurezza' => 'shield-exclamation',
                            default => 'ticket-perforated'
                        };
                    @endphp
                    <div class="d-flex align-items-center">
                        <i class="bi bi-{{ $categoriaIcon }} me-1"></i>
                        <span class="small">{{ $ticket->categoria_label }}</span>
                    </div>
                </td>
                <td>
                    <span class="badge bg-{{ $ticket->colore_priorita }}">
                        {{ $ticket->priorita_label }}
                    </span>
                    @if($ticket->in_ritardo)
                        <small class="text-danger d-block">
                            <i class="bi bi-clock"></i> In ritardo
                        </small>
                    @endif
                </td>
                <td>
                    <div>
                        <span class="badge bg-{{ $ticket->colore_stato }}">
                            {{ $ticket->stato_label }}
                        </span>
                        <div class="progress mt-1" style="height: 3px;">
                            <div class="progress-bar bg-{{ $ticket->colore_stato }}" 
                                 style="width: {{ $ticket->progresso }}%"></div>
                        </div>
                    </div>
                </td>
                <td>
                    @if($ticket->assegnatario)
                        <div class="d-flex align-items-center">
                            <img src="{{ $ticket->assegnatario->avatar_url }}" 
                                 alt="Avatar" 
                                 class="rounded-circle me-1" 
                                 width="24" height="24">
                            <span class="small">{{ $ticket->assegnatario->nome_completo }}</span>
                        </div>
                    @else
                        <span class="text-muted">Non assegnato</span>
                    @endif
                </td>
                <td>
                    <div>
                        <span class="fw-semibold">{{ $ticket->data_apertura->format('d/m/Y') }}</span>
                        <small class="text-muted d-block">{{ $ticket->data_apertura->format('H:i') }}</small>
                        <small class="text-muted">{{ $ticket->data_apertura->diffForHumans() }}</small>
                    </div>
                </td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="{{ route('tickets.show', $ticket->id) }}" 
                           class="btn btn-outline-primary" 
                           title="Visualizza">
                            <i class="bi bi-eye"></i>
                        </a>
                        
                        @if(!$ticket->assegnatario && auth()->user()->hasPermission('tickets', 'modifica'))
                        <button type="button" 
                                class="btn btn-outline-success" 
                                onclick="assignTicket({{ $ticket->id }})"
                                title="Assegna">
                            <i class="bi bi-person-plus"></i>
                        </button>
                        @endif
                        
                        @if($ticket->assegnato_a === auth()->id() || auth()->user()->hasPermission('tickets', 'modifica'))
                        <div class="btn-group">
                            <button type="button" 
                                    class="btn btn-outline-secondary dropdown-toggle" 
                                    data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots"></i>
                            </button>
                            <ul class="dropdown-menu">
                                @if(in_array($ticket->stato, ['aperto', 'assegnato']))
                                <li>
                                    <a class="dropdown-item" href="#" onclick="changeTicketStatus({{ $ticket->id }}, 'inizia_lavori')">
                                        <i class="bi bi-play"></i> Inizia Lavori
                                    </a>
                                </li>
                                @endif
                                @if($ticket->stato === 'in_corso')
                                <li>
                                    <a class="dropdown-item" href="#" onclick="resolveTicket({{ $ticket->id }})">
                                        <i class="bi bi-check-circle"></i> Risolvi
                                    </a>
                                </li>
                                @endif
                                @if($ticket->stato === 'risolto' && $ticket->user_id === auth()->id())
                                <li>
                                    <a class="dropdown-item" href="#" onclick="closeTicket({{ $ticket->id }})">
                                        <i class="bi bi-x-circle"></i> Chiudi
                                    </a>
                                </li>
                                @endif
                                @can('permission', ['tickets', 'elimina'])
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="#" onclick="deleteTicket({{ $ticket->id }})">
                                        <i class="bi bi-trash"></i> Elimina
                                    </a>
                                </li>
                                @endcan
                            </ul>
                        </div>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center py-4">
                    <div class="text-muted">
                        <i class="bi bi-ticket-perforated fs-1 d-block mb-2"></i>
                        Nessun ticket trovato
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
function assignTicket(ticketId) {
    // Implementa modal per assegnazione ticket
    console.log('Assegna ticket:', ticketId);
}

function changeTicketStatus(ticketId, action) {
    if (confirm('Confermi questa azione?')) {
        fetch(`/tickets/${ticketId}/status`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ azione: action })
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

function resolveTicket(ticketId) {
    const soluzione = prompt('Inserisci la soluzione adottata:');
    if (soluzione) {
        fetch(`/tickets/${ticketId}/status`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ 
                azione: 'risolvi',
                soluzione: soluzione 
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Errore: ' + data.message);
            }
        });
    }
}

function closeTicket(ticketId) {
    if (confirm('Sei soddisfatto della soluzione?')) {
        fetch(`/tickets/${ticketId}/status`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ azione: 'chiudi' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Errore: ' + data.message);
            }
        });
    }
}

function deleteTicket(ticketId) {
    if (confirm('Sei sicuro di voler eliminare questo ticket?')) {
        fetch(`/tickets/${ticketId}`, {
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
        });
    }
}
</script>