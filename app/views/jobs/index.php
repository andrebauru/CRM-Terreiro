<!-- Search and Filter Bar -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-center">
            <div class="col-md-3">
                <div class="input-icon">
                    <span class="input-icon-addon">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="searchJobs" class="form-control" placeholder="Buscar trabalhos...">
                </div>
            </div>
            <div class="col-md-2">
                <select id="filterStatus" class="form-select">
                    <option value="">Todos os Status</option>
                    <option value="pending">Pendente</option>
                    <option value="in_progress">Em Andamento</option>
                    <option value="completed">Concluído</option>
                    <option value="cancelled">Cancelado</option>
                </select>
            </div>
            <div class="col-md-2">
                <select id="filterPriority" class="form-select">
                    <option value="">Todas Prioridades</option>
                    <option value="high">Alta</option>
                    <option value="medium">Média</option>
                    <option value="low">Baixa</option>
                </select>
            </div>
            <div class="col-md-5 text-md-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mainModal" data-url="<?= ROUTE_BASE ?>/jobs/create" data-title="Novo Trabalho">
                    <i class="bi bi-plus-lg me-2"></i>
                    Novo Trabalho
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-row mb-3">
    <?php
    $pendingCount = count(array_filter($jobs, fn($j) => $j['status'] === 'pending'));
    $inProgressCount = count(array_filter($jobs, fn($j) => $j['status'] === 'in_progress'));
    $completedCount = count(array_filter($jobs, fn($j) => $j['status'] === 'completed'));
    ?>
    <div class="card card-stats hover-lift">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-warning-lt text-warning me-3">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <div class="stat-value"><?= $pendingCount ?></div>
                    <div class="stat-label">Pendentes</div>
                </div>
            </div>
        </div>
    </div>
    <div class="card card-stats hover-lift">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-info-lt text-info me-3">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
                <div>
                    <div class="stat-value"><?= $inProgressCount ?></div>
                    <div class="stat-label">Em Andamento</div>
                </div>
            </div>
        </div>
    </div>
    <div class="card card-stats hover-lift">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-success-lt text-success me-3">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <div class="stat-value"><?= $completedCount ?></div>
                    <div class="stat-label">Concluídos</div>
                </div>
            </div>
        </div>
    </div>
    <div class="card card-stats hover-lift">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-primary-lt text-primary me-3">
                    <i class="bi bi-list-check"></i>
                </div>
                <div>
                    <div class="stat-value"><?= count($jobs) ?></div>
                    <div class="stat-label">Total</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Jobs List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="bi bi-clipboard-check me-2"></i>
            Trabalhos
        </h3>
        <div class="card-actions">
            <span class="badge bg-primary-lt" id="jobCount">
                <?= count($jobs) ?> trabalho(s)
            </span>
        </div>
    </div>

    <?php if (empty($jobs)): ?>
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <p class="empty-state-title">Nenhum trabalho cadastrado</p>
                <p class="empty-state-description">Comece criando um novo trabalho para seus clientes.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mainModal" data-url="<?= ROUTE_BASE ?>/jobs/create" data-title="Novo Trabalho">
                    <i class="bi bi-plus-lg me-2"></i>
                    Criar Trabalho
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table card-table table-vcenter" id="jobsTable">
                <thead>
                    <tr>
                        <th>Trabalho</th>
                        <th>Cliente</th>
                        <th>Status</th>
                        <th>Prioridade</th>
                        <th>Vencimento</th>
                        <th class="w-1 text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $job): ?>
                        <?php
                        $statusClass = match($job['status']) {
                            'completed' => 'success',
                            'in_progress' => 'info',
                            'pending' => 'warning',
                            'cancelled' => 'danger',
                            default => 'secondary'
                        };
                        $statusLabel = match($job['status']) {
                            'completed' => 'Concluído',
                            'in_progress' => 'Em Andamento',
                            'pending' => 'Pendente',
                            'cancelled' => 'Cancelado',
                            default => $job['status']
                        };
                        $priorityClass = match($job['priority']) {
                            'high' => 'danger',
                            'medium' => 'warning',
                            'low' => 'secondary',
                            default => 'secondary'
                        };
                        $priorityLabel = match($job['priority']) {
                            'high' => 'Alta',
                            'medium' => 'Média',
                            'low' => 'Baixa',
                            default => $job['priority']
                        };
                        ?>
                        <tr class="job-row"
                            data-title="<?= htmlspecialchars(strtolower($job['title'])) ?>"
                            data-client="<?= htmlspecialchars(strtolower($job['client_name'])) ?>"
                            data-status="<?= htmlspecialchars($job['status']) ?>"
                            data-priority="<?= htmlspecialchars($job['priority']) ?>">
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-md bg-<?= $statusClass ?>-lt text-<?= $statusClass ?> me-3">
                                        <i class="bi bi-briefcase"></i>
                                    </span>
                                    <div>
                                        <a href="<?= ROUTE_BASE ?>/jobs/<?= htmlspecialchars($job['id']) ?>" class="text-reset fw-medium">
                                            <?= htmlspecialchars($job['title']) ?>
                                        </a>
                                        <div class="text-muted small">
                                            <?= htmlspecialchars($job['service_name']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="<?= ROUTE_BASE ?>/clients/<?= htmlspecialchars($job['client_id']) ?>" class="text-reset">
                                    <?= htmlspecialchars($job['client_name']) ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-status bg-<?= $statusClass ?>-lt text-<?= $statusClass ?>">
                                    <?= $statusLabel ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $priorityClass ?>-lt text-<?= $priorityClass ?>">
                                    <?= $priorityLabel ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($job['due_date'])): ?>
                                    <?php
                                    $dueDate = new DateTime($job['due_date']);
                                    $today = new DateTime();
                                    $isOverdue = $dueDate < $today && $job['status'] !== 'completed';
                                    ?>
                                    <span class="<?= $isOverdue ? 'text-danger fw-medium' : 'text-muted' ?>">
                                        <?php if ($isOverdue): ?>
                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                        <?php endif; ?>
                                        <?= $dueDate->format('d/m/Y') ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-actions justify-content-end">
                                    <a href="<?= ROUTE_BASE ?>/jobs/<?= htmlspecialchars($job['id']) ?>" class="btn btn-sm btn-ghost-primary" title="Ver detalhes">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button class="btn btn-sm btn-ghost-primary" title="Editar"
                                            data-bs-toggle="modal"
                                            data-bs-target="#mainModal"
                                            data-url="<?= ROUTE_BASE ?>/jobs/<?= htmlspecialchars($job['id']) ?>/edit"
                                            data-title="Editar: <?= htmlspecialchars($job['title']) ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="<?= ROUTE_BASE ?>/jobs/<?= htmlspecialchars($job['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este trabalho?');">
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
    const searchInput = document.getElementById('searchJobs');
    const filterStatus = document.getElementById('filterStatus');
    const filterPriority = document.getElementById('filterPriority');
    const jobRows = document.querySelectorAll('.job-row');
    const jobCount = document.getElementById('jobCount');

    function filterJobs() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        const statusFilter = filterStatus ? filterStatus.value : '';
        const priorityFilter = filterPriority ? filterPriority.value : '';
        let visibleCount = 0;

        jobRows.forEach(row => {
            const title = row.dataset.title;
            const client = row.dataset.client;
            const status = row.dataset.status;
            const priority = row.dataset.priority;

            const matchesSearch = title.includes(searchTerm) || client.includes(searchTerm);
            const matchesStatus = !statusFilter || status === statusFilter;
            const matchesPriority = !priorityFilter || priority === priorityFilter;

            const matches = matchesSearch && matchesStatus && matchesPriority;
            row.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });

        if (jobCount) {
            jobCount.textContent = visibleCount + ' trabalho(s)';
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterJobs);
    if (filterStatus) filterStatus.addEventListener('change', filterJobs);
    if (filterPriority) filterPriority.addEventListener('change', filterJobs);
});
</script>
