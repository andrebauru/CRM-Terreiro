<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?= htmlspecialchars($title) ?></h3>
    </div>
    <div class="card-body">
        <form action="/services/<?= htmlspecialchars($service['id']) ?>/update" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="mb-3">
                <label class="form-label">Nome do Serviço</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($service['name']) ?>" placeholder="Nome do Serviço" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Descrição</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Descrição detalhada do serviço"><?= htmlspecialchars($service['description']) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Preço</label>
                <input type="number" name="price" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars(number_format($service['price'], 2, '.', '')) ?>">
            </div>
            <div class="mb-3">
                <label class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" <?= $service['is_active'] ? 'checked' : '' ?>>
                    <span class="form-check-label">Serviço Ativo</span>
                </label>
            </div>
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">Atualizar Serviço</button>
                <a href="/services/<?= htmlspecialchars($service['id']) ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
