<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?= htmlspecialchars($title) ?></h3>
    </div>
    <div class="card-body">
        <form action="/jobs/<?= htmlspecialchars($job['id']) ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="mb-3">
                <label class="form-label">Título da Tarefa</label>
                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($job['title']) ?>" placeholder="Título da Tarefa" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Descrição</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Descrição detalhada da tarefa"><?= htmlspecialchars($job['description']) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Cliente</label>
                <select name="client_id" class="form-select" required>
                    <option value="">Selecione um cliente</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= htmlspecialchars($client['id']) ?>" <?= $client['id'] == $job['client_id'] ? 'selected' : '' ?>><?= htmlspecialchars($client['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Serviço</label>
                <select name="service_id" class="form-select" required>
                    <option value="">Selecione um serviço</option>
                    <?php foreach ($services as $service): ?>
                        <option value="<?= htmlspecialchars($service['id']) ?>" <?= $service['id'] == $job['service_id'] ? 'selected' : '' ?>><?= htmlspecialchars($service['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="pending" <?= $job['status'] == 'pending' ? 'selected' : '' ?>>Pendente</option>
                    <option value="in_progress" <?= $job['status'] == 'in_progress' ? 'selected' : '' ?>>Em Andamento</option>
                    <option value="completed" <?= $job['status'] == 'completed' ? 'selected' : '' ?>>Concluída</option>
                    <option value="cancelled" <?= $job['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelada</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Prioridade</label>
                <select name="priority" class="form-select">
                    <option value="low" <?= $job['priority'] == 'low' ? 'selected' : '' ?>>Baixa</option>
                    <option value="medium" <?= $job['priority'] == 'medium' ? 'selected' : '' ?>>Média</option>
                    <option value="high" <?= $job['priority'] == 'high' ? 'selected' : '' ?>>Alta</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Canal</label>
                <input type="text" name="channel" class="form-control" value="<?= htmlspecialchars($job['channel']) ?>" placeholder="Ex: Email, Telefone, WhatsApp">
            </div>
            <div class="mb-3">
                <label class="form-label">Data de Início</label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($job['start_date']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Data de Vencimento</label>
                <input type="date" name="due_date" class="form-control" value="<?= htmlspecialchars($job['due_date']) ?>">
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Parcelas</label>
                    <input type="number" name="installments" class="form-control" min="1" value="<?= htmlspecialchars($job['installments'] ?? 1) ?>">
                </div>
                <div class="col-md-8 mb-3">
                    <label class="form-label">Valor da Parcela</label>
                    <input type="number" name="installment_value" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($job['installment_value'] ?? '') ?>" placeholder="Opcional">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Atribuído a</label>
                <select name="assigned_to" class="form-select">
                    <option value="">Ninguém</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= htmlspecialchars($user['id']) ?>" <?= $user['id'] == $job['assigned_to'] ? 'selected' : '' ?>><?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Adicionar Anexos</label>
                <input type="file" name="attachments[]" class="form-control" multiple>
                <small class="form-text text-muted">Max 6MB per file. Allowed types: PNG, JPG, JPEG, WEBP.</small>
            </div>

            <?php if (!empty($attachments)): ?>
                <div class="mb-3">
                    <label class="form-label">Anexos Existentes:</label>
                    <div class="list-group">
                        <?php foreach ($attachments as $attachment): ?>
                            <div class="list-group-item d-flex align-items-center">
                                <a href="<?= BASE_URL ?>/<?= htmlspecialchars($attachment['filepath']) ?>" target="_blank" class="text-reset"><?= htmlspecialchars($attachment['filename']) ?></a>
                                <small class="ms-2 text-muted">(<?= round($attachment['file_size'] / 1024 / 1024, 2) ?> MB)</small>
                                <div class="ms-auto">
                                    <form action="/jobs/attachments/<?= htmlspecialchars($attachment['id']) ?>" method="POST" onsubmit="return confirm('Tem certeza que deseja remover este anexo?');">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <input type="hidden" name="csrf_token" value="<?= App\Helpers\Session::generateCsrfToken() ?>">
                                        <input type="hidden" name="job_id" value="<?= htmlspecialchars($job['id']) ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Remover</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-footer">
                <button type="submit" class="btn btn-primary">Atualizar Tarefa</button>
                <a href="/jobs/<?= htmlspecialchars($job['id']) ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

