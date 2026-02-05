<div class="form-errors"></div>
<form action="<?= ROUTE_BASE ?>/jobs" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <div class="mb-3">
        <label class="form-label required">Título do Trabalho</label>
        <input type="text" name="title" class="form-control" placeholder="Título do Trabalho" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Descrição</label>
        <textarea name="description" class="form-control" rows="3" placeholder="Descrição detalhada do trabalho"></textarea>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label required">Cliente</label>
            <select name="client_id" class="form-select" required>
                <option value="">Selecione um cliente</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?= htmlspecialchars($client['id']) ?>"><?= htmlspecialchars($client['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label required">Serviço</label>
            <select name="service_id" class="form-select" required>
                <option value="">Selecione um serviço</option>
                <?php foreach ($services as $service): ?>
                    <option value="<?= htmlspecialchars($service['id']) ?>"><?= htmlspecialchars($service['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="pending">Pendente</option>
                <option value="in_progress">Em Andamento</option>
                <option value="completed">Concluído</option>
                <option value="cancelled">Cancelado</option>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Prioridade</label>
            <select name="priority" class="form-select">
                <option value="low">Baixa</option>
                <option value="medium">Média</option>
                <option value="high">Alta</option>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Data de Início</label>
            <input type="date" name="start_date" class="form-control">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Data de Vencimento</label>
            <input type="date" name="due_date" class="form-control">
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Parcelas</label>
            <input type="number" name="installments" class="form-control" min="1" value="1">
        </div>
        <div class="col-md-8 mb-3">
            <label class="form-label">Valor da Parcela</label>
            <input type="number" name="installment_value" class="form-control" step="0.01" min="0" placeholder="Opcional">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Atribuído a</label>
        <select name="assigned_to" class="form-select">
            <option value="">Ninguém</option>
            <?php foreach ($users as $user): ?>
                <option value="<?= htmlspecialchars($user['id']) ?>"><?= htmlspecialchars($user['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Anexos</label>
        <input type="file" name="attachments[]" class="form-control" multiple>
        <small class="form-text text-muted">Max 6MB per file. Allowed types: PNG, JPG, JPEG, WEBP.</small>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar Trabalho</button>
    </div>
</form>

