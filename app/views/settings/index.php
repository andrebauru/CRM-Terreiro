<div class="card">
    <div class="card-header">
        <h3 class="card-title">Configurações da Empresa</h3>
    </div>
    <div class="card-body">
        <form action="/settings" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="mb-3">
                <label class="form-label">Nome do Cliente</label>
                <input type="text" name="client_name" class="form-control" value="<?= htmlspecialchars($settings['client_name'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Nome da Empresa</label>
                <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($settings['company_name'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Logo</label>
                <input type="file" name="logo" class="form-control" accept="image/png,image/jpeg,image/webp">
                <small class="form-text text-muted">Imagem será convertida para JPG com menos de 6MB.</small>
            </div>

            <?php if (!empty($settings['logo_path'])): ?>
                <div class="mb-3">
                    <label class="form-label">Logo atual</label>
                    <div>
                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($settings['logo_path']) ?>" alt="Logo" style="max-height: 80px;">
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-footer">
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>
