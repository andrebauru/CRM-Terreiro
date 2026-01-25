<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?= htmlspecialchars($title) ?></h3>
    </div>
    <div class="card-body">
        <form action="/jobs" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="mb-3">
                <label class="form-label">Título da Tarefa</label>
                <input type="text" name="title" class="form-control" placeholder="Título da Tarefa" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Descrição</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Descrição detalhada da tarefa"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Cliente</label>
                <select name="client_id" class="form-select" required>
                    <option value="">Selecione um cliente</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= htmlspecialchars($client['id']) ?>"><?= htmlspecialchars($client['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Serviço</label>
                <select name="service_id" class="form-select" required>
                    <option value="">Selecione um serviço</option>
                    <?php foreach ($services as $service): ?>
                        <option value="<?= htmlspecialchars($service['id']) ?>"><?= htmlspecialchars($service['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="pending">Pendente</option>
                    <option value="in_progress">Em Andamento</option>
                    <option value="completed">Concluída</option>
                    <option value="cancelled">Cancelada</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Prioridade</label>
                <select name="priority" class="form-select">
                    <option value="low">Baixa</option>
                    <option value="medium">Média</option>
                    <option value="high">Alta</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Canal</label>
                <input type="text" name="channel" class="form-control" placeholder="Ex: Email, Telefone, WhatsApp">
            </div>
            <div class="mb-3">
                <label class="form-label">Data de Início</label>
                <input type="date" name="start_date" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">Data de Vencimento</label>
                <input type="date" name="due_date" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">Atribuído a</label>
                <select name="assigned_to" class="form-select">
                    <option value="">Ninguém</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= htmlspecialchars($user['id']) ?>"><?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Anexos</label>
                <input type="file" name="attachments[]" class="form-control" multiple>
                <small class="form-text text-muted">Max 5MB per file. Allowed types: PNG, JPG, JPEG, WEBP.</small>
            </div>
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">Salvar Tarefa</button>
                <a href="/jobs" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
