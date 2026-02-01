<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?= htmlspecialchars($title) ?></h3>
    </div>
    <div class="card-body">
        <?php $isAdmin = \App\Helpers\Session::get('user_role') === 'admin'; ?>
        <form action="/users/<?= htmlspecialchars($user['id']) ?>" method="POST">
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="mb-3">
                <label class="form-label">Nome Completo</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" placeholder="Seu nome completo" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" placeholder="seu@email.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Senha (deixe em branco para não alterar)</label>
                <input type="password" name="password" class="form-control" placeholder="Nova Senha">
            </div>
            <?php if ($isAdmin): ?>
                <div class="mb-3">
                    <label class="form-label">Função</label>
                    <select name="role" class="form-select" required>
                        <option value="staff" <?= $user['role'] == 'staff' ? 'selected' : '' ?>>Staff</option>
                        <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
            <?php endif; ?>
            <div class="form-footer">
                <button type="submit" class="btn btn-primary"><?= $isAdmin ? 'Atualizar Usuário' : 'Atualizar Perfil' ?></button>
                <a href="<?= $isAdmin ? '/users' : '/dashboard' ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
