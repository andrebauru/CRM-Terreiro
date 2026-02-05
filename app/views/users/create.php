<div class="form-errors"></div>
<form action="<?= ROUTE_BASE ?>/users" method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <div class="mb-3">
        <label class="form-label required">Nome Completo</label>
        <input type="text" name="name" class="form-control" placeholder="Seu nome completo" required>
    </div>
    <div class="mb-3">
        <label class="form-label required">Email</label>
        <input type="email" name="email" class="form-control" placeholder="seu@email.com" required>
    </div>
    <div class="mb-3">
        <label class="form-label required">Senha</label>
        <input type="password" name="password" class="form-control" placeholder="Senha" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Função</label>
        <select name="role" class="form-select" required>
            <option value="staff">Staff</option>
            <option value="admin">Admin</option>
        </select>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar Usuário</button>
    </div>
</form>
