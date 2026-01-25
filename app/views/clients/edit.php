<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?= htmlspecialchars($title) ?></h3>
    </div>
    <div class="card-body">
        <form action="/clients/<?= htmlspecialchars($client['id']) ?>/update" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="mb-3">
                <label class="form-label">Nome do Cliente</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($client['name']) ?>" placeholder="Nome Completo" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($client['email']) ?>" placeholder="email@exemplo.com">
            </div>
            <div class="mb-3">
                <label class="form-label">Telefone</label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($client['phone']) ?>" placeholder="(XX) XXXXX-XXXX">
            </div>
            <div class="mb-3">
                <label class="form-label">Endereço</label>
                <textarea name="address" class="form-control" rows="3" placeholder="Endereço Completo"><?= htmlspecialchars($client['address']) ?></textarea>
            </div>
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">Atualizar Cliente</button>
                <a href="/clients/<?= htmlspecialchars($client['id']) ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
