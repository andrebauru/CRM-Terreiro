<!-- Search Bar -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-center">
            <div class="col-md-4">
                <div class="input-icon">
                    <span class="input-icon-addon">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="searchUsers" class="form-control" placeholder="Buscar usuários...">
                </div>
            </div>
            <div class="col-md-3">
                <select id="filterRole" class="form-select">
                    <option value="">Todas as Funções</option>
                    <option value="admin">Admin</option>
                    <option value="staff">Staff</option>
                </select>
            </div>
            <div class="col-md-5 text-md-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mainModal" data-url="<?= ROUTE_BASE ?>/users/create" data-title="Novo Usuário">
                    <i class="bi bi-plus-lg me-2"></i>
                    Novo Usuário
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Users List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="bi bi-person-gear me-2"></i>
            Usuários do Sistema
        </h3>
        <div class="card-actions">
            <span class="badge bg-primary-lt" id="userCount">
                <?= count($users) ?> usuário(s)
            </span>
        </div>
    </div>

    <?php if (empty($users)): ?>
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-person-gear"></i>
                </div>
                <p class="empty-state-title">Nenhum usuário cadastrado</p>
                <p class="empty-state-description">Adicione usuários para gerenciar o sistema.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mainModal" data-url="<?= ROUTE_BASE ?>/users/create" data-title="Novo Usuário">
                    <i class="bi bi-plus-lg me-2"></i>
                    Adicionar Usuário
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table card-table table-vcenter" id="usersTable">
                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th>Função</th>
                        <th>Status</th>
                        <th>Cadastrado em</th>
                        <th class="w-1 text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <?php
                        $isCurrentUser = $user['id'] == App\Helpers\Session::get('user_id');
                        $roleClass = $user['role'] === 'admin' ? 'purple' : 'blue';
                        ?>
                        <tr class="user-row"
                            data-name="<?= htmlspecialchars(strtolower($user['name'])) ?>"
                            data-email="<?= htmlspecialchars(strtolower($user['email'])) ?>"
                            data-role="<?= htmlspecialchars($user['role']) ?>">
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-md bg-<?= $roleClass ?>-lt text-<?= $roleClass ?> me-3">
                                        <?= strtoupper(substr($user['name'], 0, 2)) ?>
                                    </span>
                                    <div>
                                        <div class="fw-medium">
                                            <?= htmlspecialchars($user['name']) ?>
                                            <?php if ($isCurrentUser): ?>
                                                <span class="badge bg-green-lt ms-1">Você</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted small"><?= htmlspecialchars($user['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?= $roleClass ?>-lt text-<?= $roleClass ?>">
                                    <?= $user['role'] === 'admin' ? 'Administrador' : 'Colaborador' ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $user['is_active'] ? 'success' : 'secondary' ?>-lt text-<?= $user['is_active'] ? 'success' : 'secondary' ?>">
                                    <?= $user['is_active'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td>
                                <span class="text-muted">
                                    <?= (new DateTime($user['created_at']))->format('d/m/Y') ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-actions justify-content-end">
                                    <button class="btn btn-sm btn-ghost-primary" title="Editar"
                                            data-bs-toggle="modal"
                                            data-bs-target="#mainModal"
                                            data-url="<?= ROUTE_BASE ?>/users/<?= htmlspecialchars($user['id']) ?>/edit"
                                            data-title="Editar: <?= htmlspecialchars($user['name']) ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if (!$isCurrentUser): ?>
                                        <form action="<?= ROUTE_BASE ?>/users/<?= htmlspecialchars($user['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este usuário?');">
                                            <input type="hidden" name="_method" value="DELETE">
                                            <input type="hidden" name="csrf_token" value="<?= App\Helpers\Session::generateCsrfToken() ?>">
                                            <button type="submit" class="btn btn-sm btn-ghost-danger" title="Excluir">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
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
    const searchInput = document.getElementById('searchUsers');
    const filterRole = document.getElementById('filterRole');
    const userRows = document.querySelectorAll('.user-row');
    const userCount = document.getElementById('userCount');

    function filterUsers() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        const roleFilter = filterRole ? filterRole.value : '';
        let visibleCount = 0;

        userRows.forEach(row => {
            const name = row.dataset.name;
            const email = row.dataset.email;
            const role = row.dataset.role;

            const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
            const matchesRole = !roleFilter || role === roleFilter;

            const matches = matchesSearch && matchesRole;
            row.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });

        if (userCount) {
            userCount.textContent = visibleCount + ' usuário(s)';
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterUsers);
    if (filterRole) filterRole.addEventListener('change', filterUsers);
});
</script>
