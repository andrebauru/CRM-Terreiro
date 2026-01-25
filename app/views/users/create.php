<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?= htmlspecialchars($title) ?></h3>
    </div>
    <div class="card-body">
        <form action="/users" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="mb-3">
                <label class="form-label">Nome Completo</label>
                <input type="text" name="name" class="form-control" placeholder="Seu nome completo" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="seu@email.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Senha</label>
                <input type="password" name="password" class="form-control" placeholder="Senha" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Função</label>
                <select name="role" class="form-select" required>
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">Salvar Usuário</button>
                <a href="/users" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
