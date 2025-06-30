<div class="table-responsive">
    <table class="table table-hover">
        <thead class="table-light">
            <tr>
                <th>
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'nome_articolo', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}" 
                       class="text-decoration-none text-dark">
                        Articolo
                        @if(request('sort') === 'nome_articolo')
                            <i class="bi bi-arrow-{{ request('direction') === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </a>
                </th>
                <th>Categoria</th>
                <th>Codice</th>
                <th>Quantità</th>
                <th>Unità</th>
                <th>Prezzo Unit.</th>
                <th>Valore Stock</th>
                <th>Stato</th>
                <th>Scadenza</th>
                <th width="150">Azioni</th>
            </tr>
        </thead>
        <tbody>
            @forelse($articoli as $articolo)
            <tr class="{{ $articolo->sottoscorta ? 'table-warning' : '' }}">
                <td>
                    <div class="d-flex align-items-center">
                        @if($articolo->foto)
                            <img src="{{ asset('storage/' . $articolo->foto) }}" 
                                 alt="Foto" 
                                 class="rounded me-2" 
                                 width="40" height="40">
                        @else
                            <div class="bg-light rounded d-flex align-items">
                                <div class="bg-light rounded d-flex align-items-center justify-content-center me-2" 
                                style="width: 40px; height: 40px;">
                               <i class="bi bi-box text-muted"></i>
                           </div>
                       @endif
                       <div>
                           <div class="fw-semibold">{{ $articolo->nome_articolo }}</div>
                           @if($articolo->descrizione)
                               <small class="text-muted">{{ Str::limit($articolo->descrizione, 50) }}</small>
                           @endif
                       </div>
                   </div>
               </td>
               <td>
                   @php
                       $catIcon = match($articolo->categoria) {
                           'farmaci' => 'capsule',
                           'dispositivi_medici' => 'heart-pulse',
                           'consumabili' => 'box',
                           'dpi' => 'shield-check',
                           'pulizia' => 'spray-can',
                           'ufficio' => 'pencil',
                           default => 'box'
                       };
                       $catColor = match($articolo->categoria) {
                           'farmaci' => 'text-danger',
                           'dispositivi_medici' => 'text-primary',
                           'dpi' => 'text-warning',
                           default => 'text-secondary'
                       };
                   @endphp
                   <div class="d-flex align-items-center">
                       <i class="bi bi-{{ $catIcon }} {{ $catColor }} me-1"></i>
                       <span class="small">{{ ucfirst($articolo->categoria) }}</span>
                   </div>
                   @if($articolo->farmaco)
                       <span class="badge bg-danger">Farmaco</span>
                   @endif
                   @if($articolo->dispositivo_medico)
                       <span class="badge bg-primary">DM</span>
                   @endif
               </td>
               <td>
                   <div>
                       @if($articolo->codice_interno)
                           <span class="fw-bold">{{ $articolo->codice_interno }}</span>
                       @endif
                       @if($articolo->codice_articolo)
                           <small class="text-muted d-block">{{ $articolo->codice_articolo }}</small>
                       @endif
                       @if($articolo->lotto)
                           <small class="text-info d-block">Lotto: {{ $articolo->lotto }}</small>
                       @endif
                   </div>
               </td>
               <td>
                   <div class="text-center">
                       <span class="fs-5 fw-bold {{ $articolo->sottoscorta ? 'text-danger' : ($articolo->quantita_attuale > 0 ? 'text-success' : 'text-muted') }}">
                           {{ $articolo->quantita_attuale }}
                       </span>
                       @if($articolo->quantita_minima > 0)
                           <small class="text-muted d-block">
                               Min: {{ $articolo->quantita_minima }}
                           </small>
                       @endif
                       @if($articolo->sottoscorta)
                           <small class="text-danger d-block">
                               <i class="bi bi-exclamation-triangle"></i> Sottoscorta
                           </small>
                       @endif
                   </div>
               </td>
               <td>
                   <span class="badge bg-light text-dark">{{ $articolo->unita_misura }}</span>
               </td>
               <td>
                   @if($articolo->prezzo_unitario)
                       <span class="fw-semibold">€ {{ number_format($articolo->prezzo_unitario, 2) }}</span>
                       @if($articolo->costo_ultimo_acquisto && $articolo->costo_ultimo_acquisto != $articolo->prezzo_unitario)
                           <small class="text-muted d-block">
                               Ultimo: € {{ number_format($articolo->costo_ultimo_acquisto, 2) }}
                           </small>
                       @endif
                   @else
                       <span class="text-muted">Non definito</span>
                   @endif
               </td>
               <td>
                   @if($articolo->valore_stock > 0)
                       <span class="fw-bold text-success">€ {{ number_format($articolo->valore_stock, 2) }}</span>
                   @else
                       <span class="text-muted">€ 0,00</span>
                   @endif
               </td>
               <td>
                   <span class="badge bg-{{ $articolo->colore_stato }}">
                       {{ $articolo->stato_descrizione }}
                   </span>
                   @if(!$articolo->attivo)
                       <small class="text-muted d-block">Non attivo</small>
                   @endif
               </td>
               <td>
                   @if($articolo->scadenza)
                       @php
                           $giorni = now()->diffInDays($articolo->scadenza, false);
                           $badgeClass = $giorni < 0 ? 'bg-danger' : ($giorni <= 30 ? 'bg-warning' : 'bg-success');
                       @endphp
                       <span class="badge {{ $badgeClass }}">
                           {{ $articolo->scadenza->format('d/m/Y') }}
                       </span>
                       @if($giorni >= 0 && $giorni <= 60)
                           <small class="text-muted d-block">{{ $giorni }} giorni</small>
                       @elseif($giorni < 0)
                           <small class="text-danger d-block">Scaduto</small>
                       @endif
                   @else
                       <span class="text-muted">Non definita</span>
                   @endif
               </td>
               <td>
                   <div class="btn-group btn-group-sm" role="group">
                       <a href="{{ route('magazzino.show', $articolo->id) }}" 
                          class="btn btn-outline-primary" 
                          title="Visualizza">
                           <i class="bi bi-eye"></i>
                       </a>
                       
                       @can('permission', ['magazzino', 'modifica'])
                       <div class="btn-group">
                           <button type="button" 
                                   class="btn btn-outline-secondary dropdown-toggle" 
                                   data-bs-toggle="dropdown">
                               <i class="bi bi-three-dots"></i>
                           </button>
                           <ul class="dropdown-menu">
                               <li>
                                   <a class="dropdown-item" href="#" onclick="showCaricoModal({{ $articolo->id }})">
                                       <i class="bi bi-plus-circle text-success"></i> Carico
                                   </a>
                               </li>
                               @if($articolo->quantita_attuale > 0)
                               <li>
                                   <a class="dropdown-item" href="#" onclick="showScaricoModal({{ $articolo->id }})">
                                       <i class="bi bi-dash-circle text-danger"></i> Scarico
                                   </a>
                               </li>
                               @endif
                               <li><hr class="dropdown-divider"></li>
                               <li>
                                   <a class="dropdown-item" href="{{ route('magazzino.edit', $articolo->id) }}">
                                       <i class="bi bi-pencil"></i> Modifica
                                   </a>
                               </li>
                               <li>
                                   <a class="dropdown-item" href="{{ route('magazzino.movimenti', $articolo->id) }}">
                                       <i class="bi bi-arrow-left-right"></i> Movimenti
                                   </a>
                               </li>
                               @can('permission', ['magazzino', 'elimina'])
                               <li><hr class="dropdown-divider"></li>
                               <li>
                                   <a class="dropdown-item text-danger" href="#" onclick="deleteArticolo({{ $articolo->id }})">
                                       <i class="bi bi-trash"></i> Elimina
                                   </a>
                               </li>
                               @endcan
                           </ul>
                       </div>
                       @endcan
                   </div>
               </td>
           </tr>
           @empty
           <tr>
               <td colspan="10" class="text-center py-4">
                   <div class="text-muted">
                       <i class="bi bi-boxes fs-1 d-block mb-2"></i>
                       Nessun articolo trovato
                   </div>
               </td>
           </tr>
           @endforelse
       </tbody>
   </table>
</div>

<!-- Modal Carico -->
<div class="modal fade" id="caricoModal" tabindex="-1">
   <div class="modal-dialog">
       <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title">Registra Carico</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
           </div>
           <form id="caricoForm">
               <div class="modal-body">
                   <input type="hidden" id="carico_articolo_id">
                   <div class="mb-3">
                       <label for="carico_quantita" class="form-label">Quantità</label>
                       <input type="number" class="form-control" id="carico_quantita" min="1" required>
                   </div>
                   <div class="mb-3">
                       <label for="carico_motivo" class="form-label">Motivo</label>
                       <input type="text" class="form-control" id="carico_motivo" placeholder="Es: Acquisto, Donazione..." required>
                   </div>
                   <div class="mb-3">
                       <label for="carico_prezzo" class="form-label">Prezzo Unitario (opzionale)</label>
                       <input type="number" class="form-control" id="carico_prezzo" step="0.01" min="0">
                   </div>
                   <div class="mb-3">
                       <label for="carico_data" class="form-label">Data Movimento</label>
                       <input type="date" class="form-control" id="carico_data" value="{{ date('Y-m-d') }}" required>
                   </div>
               </div>
               <div class="modal-footer">
                   <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                   <button type="submit" class="btn btn-success">Registra Carico</button>
               </div>
           </form>
       </div>
   </div>
</div>

<!-- Modal Scarico -->
<div class="modal fade" id="scaricoModal" tabindex="-1">
   <div class="modal-dialog">
       <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title">Registra Scarico</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
           </div>
           <form id="scaricoForm">
               <div class="modal-body">
                   <input type="hidden" id="scarico_articolo_id">
                   <div class="mb-3">
                       <label for="scarico_quantita" class="form-label">Quantità</label>
                       <input type="number" class="form-control" id="scarico_quantita" min="1" required>
                       <small id="scarico_max" class="form-text text-muted"></small>
                   </div>
                   <div class="mb-3">
                       <label for="scarico_motivo" class="form-label">Motivo</label>
                       <input type="text" class="form-control" id="scarico_motivo" placeholder="Es: Utilizzo, Scadenza..." required>
                   </div>
                   <div class="mb-3">
                       <label for="scarico_data" class="form-label">Data Movimento</label>
                       <input type="date" class="form-control" id="scarico_data" value="{{ date('Y-m-d') }}" required>
                   </div>
               </div>
               <div class="modal-footer">
                   <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                   <button type="submit" class="btn btn-danger">Registra Scarico</button>
               </div>
           </form>
       </div>
   </div>
</div>

<script>
function showCaricoModal(articoloId) {
   document.getElementById('carico_articolo_id').value = articoloId;
   document.getElementById('caricoForm').reset();
   document.getElementById('carico_articolo_id').value = articoloId;
   new bootstrap.Modal(document.getElementById('caricoModal')).show();
}

function showScaricoModal(articoloId) {
   document.getElementById('scarico_articolo_id').value = articoloId;
   document.getElementById('scaricoForm').reset();
   document.getElementById('scarico_articolo_id').value = articoloId;
   
   // Trova la quantità massima disponibile dalla tabella
   const row = document.querySelector(`button[onclick="showScaricoModal(${articoloId})"]`).closest('tr');
   const quantitaText = row.querySelector('td:nth-child(4) .fs-5').textContent.trim();
   const maxQuantita = parseInt(quantitaText);
   
   document.getElementById('scarico_quantita').setAttribute('max', maxQuantita);
   document.getElementById('scarico_max').textContent = `Massimo disponibile: ${maxQuantita}`;
   
   new bootstrap.Modal(document.getElementById('scaricoModal')).show();
}

document.getElementById('caricoForm').addEventListener('submit', function(e) {
   e.preventDefault();
   
   const articoloId = document.getElementById('carico_articolo_id').value;
   const formData = {
       quantita: document.getElementById('carico_quantita').value,
       motivo: document.getElementById('carico_motivo').value,
       prezzo_unitario: document.getElementById('carico_prezzo').value || null,
       data_movimento: document.getElementById('carico_data').value
   };
   
   fetch(`/magazzino/${articoloId}/carico`, {
       method: 'POST',
       headers: {
           'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
           'Content-Type': 'application/json',
           'Accept': 'application/json'
       },
       body: JSON.stringify(formData)
   })
   .then(response => response.json())
   .then(data => {
       if (data.success) {
           bootstrap.Modal.getInstance(document.getElementById('caricoModal')).hide();
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

document.getElementById('scaricoForm').addEventListener('submit', function(e) {
   e.preventDefault();
   
   const articoloId = document.getElementById('scarico_articolo_id').value;
   const formData = {
       quantita: document.getElementById('scarico_quantita').value,
       motivo: document.getElementById('scarico_motivo').value,
       data_movimento: document.getElementById('scarico_data').value
   };
   
   fetch(`/magazzino/${articoloId}/scarico`, {
       method: 'POST',
       headers: {
           'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
           'Content-Type': 'application/json',
           'Accept': 'application/json'
       },
       body: JSON.stringify(formData)
   })
   .then(response => response.json())
   .then(data => {
       if (data.success) {
           bootstrap.Modal.getInstance(document.getElementById('scaricoModal')).hide();
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

function deleteArticolo(id) {
   if (confirm('Sei sicuro di voler eliminare questo articolo?')) {
       fetch(`/magazzino/${id}`, {
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
                            