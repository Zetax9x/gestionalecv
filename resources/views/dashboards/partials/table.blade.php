<div class="table-responsive">
    <table class="table table-hover">
        <thead class="table-light">
            <tr>
                <th>Nome</th>
                <th>Descrizione</th>
                <th>Creato il</th>
                <th width="150">Azioni</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dashboards as $dashboard)
            <tr>
                <td>{{ $dashboard->name }}</td>
                <td>{{ $dashboard->description }}</td>
                <td>{{ $dashboard->created_at->format('d/m/Y') }}</td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="{{ route('dashboards.show', $dashboard->id) }}" class="btn btn-outline-primary" title="Visualizza">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('dashboards.edit', $dashboard->id) }}" class="btn btn-outline-warning" title="Modifica">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form action="{{ route('dashboards.destroy', $dashboard->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Eliminare questa dashboard?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-outline-danger" title="Elimina">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center py-4">
                    <div class="text-muted">
                        <i class="bi bi-layout-text-window-reverse fs-1 d-block mb-2"></i>
                        Nessuna dashboard trovata
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
