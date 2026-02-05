<?php use App\Helpers\Format; ?>

<div class="row g-4">
    <!-- Client Info Card -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center py-4">
                <span class="avatar avatar-xl bg-primary-lt text-primary mb-3" style="font-size: 2rem;">
                    <?= strtoupper(substr($client['name'], 0, 2)) ?>
                </span>
                <h3 class="mb-1"><?= htmlspecialchars($client['name']) ?></h3>
                <p class="text-muted mb-2">Cliente #<?= htmlspecialchars($client['id']) ?></p>

                <?php
                $statusClass = match($client['status'] ?? 'active') {
                    'active' => 'success',
                    'inactive' => 'secondary',
                    'blocked' => 'danger',
                    default => 'secondary'
                };
                $statusLabel = match($client['status'] ?? 'active') {
                    'active' => 'Ativo',
                    'inactive' => 'Inativo',
                    'blocked' => 'Bloqueado',
                    default => 'Ativo'
                };
                ?>
                <span class="badge bg-<?= $statusClass ?>-lt text-<?= $statusClass ?> mb-3"><?= $statusLabel ?></span>

                <div class="btn-list justify-content-center">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mainModal"
                            data-url="<?= ROUTE_BASE ?>/clients/<?= htmlspecialchars($client['id']) ?>/edit"
                            data-title="Editar Cliente">
                        <i class="bi bi-pencil me-1"></i> Editar
                    </button>
                    <?php if (!empty($client['whatsapp_link'])): ?>
                        <a href="<?= htmlspecialchars($client['whatsapp_link']) ?>" target="_blank" class="btn btn-success">
                            <i class="bi bi-whatsapp me-1"></i> WhatsApp
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="card-body border-top">
                <h4 class="card-title mb-3">
                    <i class="bi bi-telephone me-2"></i>Contato
                </h4>

                <?php if (!empty($client['email'])): ?>
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar avatar-sm bg-primary-lt text-primary me-3">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <div>
                        <div class="text-muted small">Email</div>
                        <a href="mailto:<?= htmlspecialchars($client['email']) ?>" class="text-reset">
                            <?= htmlspecialchars($client['email']) ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($client['phone'])): ?>
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar avatar-sm bg-success-lt text-success me-3">
                        <i class="bi bi-telephone"></i>
                    </span>
                    <div>
                        <div class="text-muted small">Telefone Principal</div>
                        <a href="tel:<?= htmlspecialchars($client['phone']) ?>" class="text-reset">
                            <?= htmlspecialchars($client['phone']) ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($client['phone_secondary'])): ?>
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar avatar-sm bg-info-lt text-info me-3">
                        <i class="bi bi-telephone"></i>
                    </span>
                    <div>
                        <div class="text-muted small">Telefone Secundário</div>
                        <a href="tel:<?= htmlspecialchars($client['phone_secondary']) ?>" class="text-reset">
                            <?= htmlspecialchars($client['phone_secondary']) ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($client['whatsapp'])): ?>
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar avatar-sm bg-success text-white me-3">
                        <i class="bi bi-whatsapp"></i>
                    </span>
                    <div>
                        <div class="text-muted small">WhatsApp</div>
                        <a href="<?= htmlspecialchars($client['whatsapp_link'] ?? '#') ?>" target="_blank" class="text-success fw-medium">
                            <?= htmlspecialchars($client['whatsapp']) ?>
                            <i class="bi bi-box-arrow-up-right ms-1" style="font-size: 0.75rem;"></i>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Address -->
            <?php if (!empty($client['address']) || !empty($client['city'])): ?>
            <div class="card-body border-top">
                <h4 class="card-title mb-3">
                    <i class="bi bi-geo-alt me-2"></i>Endereço
                </h4>
                <div class="text-muted">
                    <?php if (!empty($client['address'])): ?>
                        <?= htmlspecialchars($client['address']) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($client['city']) || !empty($client['state'])): ?>
                        <?= htmlspecialchars($client['city'] ?? '') ?>
                        <?= !empty($client['state']) ? ' - ' . htmlspecialchars($client['state']) : '' ?>
                    <?php endif; ?>
                    <?php if (!empty($client['zip_code'])): ?>
                        <br>CEP: <?= htmlspecialchars($client['zip_code']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Additional Info -->
            <div class="card-body border-top">
                <h4 class="card-title mb-3">
                    <i class="bi bi-info-circle me-2"></i>Informações
                </h4>
                <div class="datagrid">
                    <?php if (!empty($client['document'])): ?>
                    <div class="datagrid-item">
                        <div class="datagrid-title">CPF/CNPJ</div>
                        <div class="datagrid-content"><?= htmlspecialchars($client['document']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($client['birth_date'])): ?>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Data de Nascimento</div>
                        <div class="datagrid-content"><?= (new DateTime($client['birth_date']))->format('d/m/Y') ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($client['source'])): ?>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Origem</div>
                        <div class="datagrid-content"><?= htmlspecialchars(ucfirst($client['source'])) ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Cadastrado em</div>
                        <div class="datagrid-content"><?= (new DateTime($client['created_at']))->format('d/m/Y H:i') ?></div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Última atualização</div>
                        <div class="datagrid-content"><?= (new DateTime($client['updated_at']))->format('d/m/Y H:i') ?></div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <?php if (!empty($client['notes'])): ?>
            <div class="card-body border-top">
                <h4 class="card-title mb-3">
                    <i class="bi bi-journal-text me-2"></i>Observações
                </h4>
                <div class="text-muted" style="white-space: pre-wrap;"><?= htmlspecialchars($client['notes']) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Danger Zone -->
        <div class="card mt-3 border-danger">
            <div class="card-header bg-danger-lt">
                <h3 class="card-title text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>Zona de Perigo
                </h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">A exclusão é permanente e removerá todos os trabalhos associados.</p>
                <form action="<?= ROUTE_BASE ?>/clients/<?= htmlspecialchars($client['id']) ?>" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este cliente? Esta ação não pode ser desfeita.');">
                    <input type="hidden" name="_method" value="DELETE">
                    <input type="hidden" name="csrf_token" value="<?= App\Helpers\Session::generateCsrfToken() ?>">
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="bi bi-trash me-2"></i>Excluir Cliente
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Jobs, History -->
    <div class="col-lg-8">
        <!-- Stats -->
        <div class="stats-row mb-4">
            <?php
            $totalJobs = isset($jobs) ? count($jobs) : 0;
            $completedJobs = isset($jobs) ? count(array_filter($jobs, fn($j) => $j['status'] === 'completed')) : 0;
            $pendingJobs = isset($jobs) ? count(array_filter($jobs, fn($j) => $j['status'] === 'pending' || $j['status'] === 'in_progress')) : 0;
            ?>
            <div class="card card-stats">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary-lt text-primary me-3">
                            <i class="bi bi-briefcase"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $totalJobs ?></div>
                            <div class="stat-label">Total</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card card-stats">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-success-lt text-success me-3">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $completedJobs ?></div>
                            <div class="stat-label">Concluídos</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card card-stats">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-warning-lt text-warning me-3">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $pendingJobs ?></div>
                            <div class="stat-label">Em Aberto</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Jobs List -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-clipboard-check me-2"></i>Trabalhos
                </h3>
                <div class="card-actions">
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#mainModal" data-url="<?= ROUTE_BASE ?>/jobs/create?client_id=<?= htmlspecialchars($client['id']) ?>" data-title="Novo Trabalho">
                        <i class="bi bi-plus-lg me-1"></i>Novo
                    </button>
                </div>
            </div>

            <?php if (empty($jobs)): ?>
                <div class="card-body">
                    <div class="empty-state py-4">
                        <div class="empty-state-icon"><i class="bi bi-clipboard"></i></div>
                        <p class="empty-state-title">Nenhum trabalho</p>
                        <p class="empty-state-description">Este cliente ainda não possui trabalhos.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($jobs as $job): ?>
                        <?php
                        $statusClass = match($job['status']) {
                            'completed' => 'success',
                            'in_progress' => 'info',
                            'pending' => 'warning',
                            'cancelled' => 'danger',
                            default => 'secondary'
                        };
                        $statusLabel = match($job['status']) {
                            'completed' => 'Concluído',
                            'in_progress' => 'Em Andamento',
                            'pending' => 'Pendente',
                            'cancelled' => 'Cancelado',
                            default => $job['status']
                        };
                        ?>
                        <a href="<?= ROUTE_BASE ?>/jobs/<?= htmlspecialchars($job['id']) ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm bg-<?= $statusClass ?>-lt text-<?= $statusClass ?> me-3">
                                        <i class="bi bi-briefcase"></i>
                                    </span>
                                    <div>
                                        <h6 class="mb-0"><?= htmlspecialchars($job['title']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($job['service_name'] ?? '') ?></small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-<?= $statusClass ?>-lt text-<?= $statusClass ?> mb-1"><?= $statusLabel ?></span>
                                    <div class="small text-muted"><?= (new DateTime($job['created_at']))->format('d/m/Y') ?></div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- History -->
        <?php if (!empty($history)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-clock-history me-2"></i>Histórico de Alterações
                </h3>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($history as $entry): ?>
                        <?php
                        $actionIcon = match($entry['action']) {
                            'created' => 'plus-circle',
                            'updated' => 'pencil',
                            'status_changed' => 'toggles',
                            'deleted' => 'trash',
                            default => 'circle'
                        };
                        $actionColor = match($entry['action']) {
                            'created' => 'success',
                            'updated' => 'info',
                            'status_changed' => 'warning',
                            'deleted' => 'danger',
                            default => 'secondary'
                        };
                        ?>
                        <div class="timeline-item">
                            <div class="d-flex">
                                <span class="avatar avatar-sm bg-<?= $actionColor ?>-lt text-<?= $actionColor ?> me-3">
                                    <i class="bi bi-<?= $actionIcon ?>"></i>
                                </span>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <strong>
                                            <?php if ($entry['action'] === 'created'): ?>
                                                Cliente criado
                                            <?php elseif ($entry['action'] === 'status_changed'): ?>
                                                Status alterado
                                            <?php elseif ($entry['field_changed']): ?>
                                                Campo "<?= htmlspecialchars($entry['field_changed']) ?>" alterado
                                            <?php else: ?>
                                                <?= ucfirst($entry['action']) ?>
                                            <?php endif; ?>
                                        </strong>
                                        <small class="text-muted">
                                            <?= (new DateTime($entry['created_at']))->format('d/m/Y H:i') ?>
                                        </small>
                                    </div>
                                    <?php if (!empty($entry['old_value']) || !empty($entry['new_value'])): ?>
                                        <div class="small text-muted mt-1">
                                            <?php if (!empty($entry['old_value'])): ?>
                                                <span class="text-danger"><del><?= htmlspecialchars(substr($entry['old_value'], 0, 100)) ?></del></span>
                                                <i class="bi bi-arrow-right mx-1"></i>
                                            <?php endif; ?>
                                            <span class="text-success"><?= htmlspecialchars(substr($entry['new_value'], 0, 100)) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($entry['user_name'])): ?>
                                        <small class="text-muted">por <?= htmlspecialchars($entry['user_name']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Back Button -->
<div class="mt-4">
    <a href="<?= ROUTE_BASE ?>/clients" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i>Voltar para Clientes
    </a>
</div>
