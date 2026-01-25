<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?= htmlspecialchars($title) ?></h3>
    </div>
    <div class="card-body">
        <form action="/services" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="mb-3">
                <label class="form-label">Nome do Serviço</label>
                <input type="text" name="name" class="form-control" placeholder="Nome do Serviço" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Descrição</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Descrição detalhada do serviço"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Preço</label>
                <input type="number" name="price" class="form-control" step="0.01" min="0" value="0.00">
            </div>
            <div class="mb-3">
                <label class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                    <span class="form-check-label">Serviço Ativo</span>
                </label>
            </div>
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">Salvar Serviço</button>
                <a href="/services" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
