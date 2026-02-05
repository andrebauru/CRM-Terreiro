<?php use App\Helpers\Format; ?>

<!-- Search and Filter Bar -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-center">
            <div class="col-md-4">
                <div class="input-icon">
                    <span class="input-icon-addon">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="searchServices" class="form-control" placeholder="Buscar serviços...">
                </div>
            </div>
            <div class="col-md-3">
                <select id="filterStatus" class="form-select">
                    <option value="">Todos</option>
                    <option value="active">Ativos</option>
                    <option value="inactive">Inativos</option>
                </select>
            </div>
            <div class="col-md-5 text-md-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mainModal" data-url="<?= ROUTE_BASE ?>/services/create" data-title="Novo Serviço">
                    <i class="bi bi-plus-lg me-2"></i>
                    Novo Serviço
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Services List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="bi bi-box-seam me-2"></i>
            Serviços
        </h3>
        <div class="card-actions">
            <span class="badge bg-primary-lt" id="serviceCount">
                <?= count($services) ?> serviço(s)
            </span>
        </div>
    </div>

    <?php if (empty($services)): ?>
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-box-seam"></i>
                </div>
                <p class="empty-state-title">Nenhum serviço cadastrado</p>
                <p class="empty-state-description">Cadastre os serviços que sua empresa oferece.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mainModal" data-url="<?= ROUTE_BASE ?>/services/create" data-title="Novo Serviço">
                    <i class="bi bi-plus-lg me-2"></i>
                    Adicionar Serviço
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table card-table table-vcenter" id="servicesTable">
                <thead>
                    <tr>
                        <th>Serviço</th>
                        <th>Descrição</th>
                        <th>Preço</th>
                        <th>Status</th>
                        <th class="w-1 text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $service): ?>
                        <tr class="service-row"
                            data-name="<?= htmlspecialchars(strtolower($service['name'])) ?>"
                            data-status="<?= $service['is_active'] ? 'active' : 'inactive' ?>">
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-md bg-<?= $service['is_active'] ? 'primary' : 'secondary' ?>-lt text-<?= $service['is_active'] ? 'primary' : 'secondary' ?> me-3">
                                        <i class="bi bi-box"></i>
                                    </span>
                                    <div>
                                        <a href="<?= ROUTE_BASE ?>/services/<?= htmlspecialchars($service['id']) ?>" class="text-reset fw-medium">
                                            <?= htmlspecialchars($service['name']) ?>
                                        </a>
                                        <div class="text-muted small">#<?= htmlspecialchars($service['id']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($service['description'])): ?>
                                    <div class="text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($service['description']) ?>">
                                        <?= htmlspecialchars($service['description']) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="fw-medium">
                                    <?= htmlspecialchars(Format::currency((float)$service['price'], $settings ?? null)) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $service['is_active'] ? 'success' : 'secondary' ?>-lt text-<?= $service['is_active'] ? 'success' : 'secondary' ?>">
                                    <?= $service['is_active'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-actions justify-content-end">
                                    <button class="btn btn-sm btn-ghost-primary" title="Editar"
                                            data-bs-toggle="modal"
                                            data-bs-target="#mainModal"
                                            data-url="<?= ROUTE_BASE ?>/services/<?= htmlspecialchars($service['id']) ?>/edit"
                                            data-title="Editar: <?= htmlspecialchars($service['name']) ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="<?= ROUTE_BASE ?>/services/<?= htmlspecialchars($service['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este serviço?');">
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
    const searchInput = document.getElementById('searchServices');
    const filterStatus = document.getElementById('filterStatus');
    const serviceRows = document.querySelectorAll('.service-row');
    const serviceCount = document.getElementById('serviceCount');

    function filterServices() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        const statusFilter = filterStatus ? filterStatus.value : '';
        let visibleCount = 0;

        serviceRows.forEach(row => {
            const name = row.dataset.name;
            const status = row.dataset.status;

            const matchesSearch = name.includes(searchTerm);
            const matchesStatus = !statusFilter || status === statusFilter;

            const matches = matchesSearch && matchesStatus;
            row.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });

        if (serviceCount) {
            serviceCount.textContent = visibleCount + ' serviço(s)';
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterServices);
    if (filterStatus) filterStatus.addEventListener('change', filterServices);
});
</script>
