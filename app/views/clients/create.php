<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?= htmlspecialchars($title) ?></h3>
    </div>
    <div class="card-body">
        <form action="/clients" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="mb-3">
                <label class="form-label">Nome do Cliente</label>
                <input type="text" name="name" class="form-control" placeholder="Nome Completo" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="email@exemplo.com">
            </div>
            <div class="mb-3">
                <label class="form-label">Telefone</label>
                <input type="text" name="phone" class="form-control" placeholder="(XX) XXXXX-XXXX">
            </div>
            <div class="mb-3">
                <label class="form-label">Endereço</label>
                <textarea name="address" class="form-control" rows="3" placeholder="Endereço Completo"></textarea>
            </div>
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">Salvar Cliente</button>
                <a href="/clients" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
