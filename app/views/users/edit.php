<div class="form-errors"></div>
<?php $isAdmin = \App\Helpers\Session::get('user_role') === 'admin'; ?>
<form action="<?= ROUTE_BASE ?>/users/<?= htmlspecialchars($user['id']) ?>" method="POST">
    <input type="hidden" name="_method" value="PUT">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <div class="mb-3">
        <label class="form-label required">Nome Completo</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" placeholder="Seu nome completo" required>
    </div>
    <div class="mb-3">
        <label class="form-label required">Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" placeholder="seu@email.com" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Senha</label>
        <input type="password" name="password" class="form-control" placeholder="Deixe em branco para não alterar">
        <small class="form-text">A senha deve ter no mínimo 6 caracteres.</small>
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
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary"><?= $isAdmin ? 'Atualizar Usuário' : 'Atualizar Perfil' ?></button>
    </div>
</form>
