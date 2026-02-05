<!-- Search and Filter Bar -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-center">
            <div class="col-md-4">
                <div class="input-icon">
                    <span class="input-icon-addon">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="searchClients" class="form-control" placeholder="Buscar clientes...">
                </div>
            </div>
            <div class="col-md-3">
                <select id="filterOrder" class="form-select">
                    <option value="name_asc">Nome (A-Z)</option>
                    <option value="name_desc">Nome (Z-A)</option>
                    <option value="recent">Mais recentes</option>
                    <option value="oldest">Mais antigos</option>
                </select>
            </div>
            <div class="col-md-5 text-md-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mainModal" data-url="<?= ROUTE_BASE ?>/clients/create" data-title="Novo Cliente">
                    <i class="bi bi-plus-lg me-2"></i>
                    Novo Cliente
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Clients List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="bi bi-people me-2"></i>
            Clientes
        </h3>
        <div class="card-actions">
            <span class="badge bg-primary-lt" id="clientCount">
                <?= count($clients) ?> cliente(s)
            </span>
        </div>
    </div>

    <?php if (empty($clients)): ?>
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-people"></i>
                </div>
                <p class="empty-state-title">Nenhum cliente cadastrado</p>
                <p class="empty-state-description">Comece adicionando seu primeiro cliente ao sistema.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mainModal" data-url="<?= ROUTE_BASE ?>/clients/create" data-title="Novo Cliente">
                    <i class="bi bi-plus-lg me-2"></i>
                    Adicionar Cliente
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table card-table table-vcenter" id="clientsTable">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Contato</th>
                        <th>Endereço</th>
                        <th>Cadastrado em</th>
                        <th class="w-1 text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                        <tr class="client-row"
                            data-name="<?= htmlspecialchars(strtolower($client['name'])) ?>"
                            data-email="<?= htmlspecialchars(strtolower($client['email'] ?? '')) ?>"
                            data-created="<?= $client['created_at'] ?>">
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-md bg-primary-lt text-primary me-3">
                                        <?= strtoupper(substr($client['name'], 0, 2)) ?>
                                    </span>
                                    <div>
                                        <a href="<?= ROUTE_BASE ?>/clients/<?= htmlspecialchars($client['id']) ?>" class="text-reset fw-medium">
                                            <?= htmlspecialchars($client['name']) ?>
                                        </a>
                                        <div class="text-muted small">#<?= htmlspecialchars($client['id']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($client['email'])): ?>
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="bi bi-envelope text-muted me-2"></i>
                                        <a href="mailto:<?= htmlspecialchars($client['email']) ?>" class="text-reset">
                                            <?= htmlspecialchars($client['email']) ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($client['phone'])): ?>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-telephone text-muted me-2"></i>
                                        <a href="tel:<?= htmlspecialchars($client['phone']) ?>" class="text-reset">
                                            <?= htmlspecialchars($client['phone']) ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <?php if (empty($client['email']) && empty($client['phone'])): ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($client['address'])): ?>
                                    <div class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($client['address']) ?>">
                                        <i class="bi bi-geo-alt text-muted me-1"></i>
                                        <?= htmlspecialchars($client['address']) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="text-muted">
                                    <?= (new DateTime($client['created_at']))->format('d/m/Y') ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-actions justify-content-end">
                                    <a href="<?= ROUTE_BASE ?>/clients/<?= htmlspecialchars($client['id']) ?>" class="btn btn-sm btn-ghost-primary" title="Ver detalhes">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button class="btn btn-sm btn-ghost-primary" title="Editar"
                                            data-bs-toggle="modal"
                                            data-bs-target="#mainModal"
                                            data-url="<?= ROUTE_BASE ?>/clients/<?= htmlspecialchars($client['id']) ?>/edit"
                                            data-title="Editar: <?= htmlspecialchars($client['name']) ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="<?= ROUTE_BASE ?>/clients/<?= htmlspecialchars($client['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este cliente?');">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <input type="hidden" name="csrf_token" value="<?= App\Helpers\Session::generateCsrfToken() ?>">
                                        <button type="submit" class="btn btn-sm btn-ghost-danger" title="Excluir">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchClients');
    const filterOrder = document.getElementById('filterOrder');
    const clientRows = document.querySelectorAll('.client-row');
    const clientCount = document.getElementById('clientCount');

    function filterClients() {
        const searchTerm = searchInput.value.toLowerCase();
        let visibleCount = 0;

        clientRows.forEach(row => {
            const name = row.dataset.name;
            const email = row.dataset.email;
            const matches = name.includes(searchTerm) || email.includes(searchTerm);

            row.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });

        clientCount.textContent = visibleCount + ' cliente(s)';
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterClients);
    }
});
</script>
