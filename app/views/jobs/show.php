<div class="card">
    <div class="card-header">
        <h3 class="card-title">Detalhes da Tarefa: <?= htmlspecialchars($job['title']) ?></h3>
        <div class="card-actions">
            <a href="/jobs/<?= htmlspecialchars($job['id']) ?>/edit" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
                Editar
            </a>
            <form action="/jobs/<?= htmlspecialchars($job['id']) ?>/delete" method="POST" style="display:inline-block;" onsubmit="return confirm('Tem certeza que deseja excluir esta tarefa?');">
                <input type="hidden" name="csrf_token" value="<?= App\Helpers\Session::generateCsrfToken() ?>">
                <button type="submit" class="btn btn-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                    Excluir
                </button>
            </form>
            <a href="/jobs" class="btn btn-secondary">Voltar para a lista</a>
        </div>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-3">ID:</dt>
            <dd class="col-9"><?= htmlspecialchars($job['id']) ?></dd>

            <dt class="col-3">Título:</dt>
            <dd class="col-9"><?= htmlspecialchars($job['title']) ?></dd>

            <dt class="col-3">Descrição:</dt>
            <dd class="col-9"><?= nl2br(htmlspecialchars($job['description'])) ?></dd>

            <dt class="col-3">Cliente:</dt>
            <dd class="col-9"><a href="/clients/<?= htmlspecialchars($job['client_id']) ?>"><?= htmlspecialchars($job['client_name']) ?></a></dd>

            <dt class="col-3">Serviço:</dt>
            <dd class="col-9"><a href="/services/<?= htmlspecialchars($job['service_id']) ?>"><?= htmlspecialchars($job['service_name']) ?></a></dd>

            <dt class="col-3">Status:</dt>
            <dd class="col-9">
                <span class="badge bg-<?=
                    ($job['status'] == 'completed') ? 'success' :
                    (($job['status'] == 'in_progress') ? 'info' :
                    (($job['status'] == 'pending') ? 'warning' : 'danger'))
                    ?>-lt"><?= htmlspecialchars(ucfirst($job['status'])) ?></span>
            </dd>

            <dt class="col-3">Prioridade:</dt>
            <dd class="col-9">
                <span class="badge bg-<?=
                    ($job['priority'] == 'high') ? 'danger' :
                    (($job['priority'] == 'medium') ? 'warning' : 'secondary'))
                    ?>-lt"><?= htmlspecialchars(ucfirst($job['priority'])) ?></span>
            </dd>

            <dt class="col-3">Canal:</dt>
            <dd class="col-9"><?= htmlspecialchars($job['channel']) ?></dd>

            <dt class="col-3">Data de Início:</dt>
            <dd class="col-9"><?= htmlspecialchars($job['start_date']) ?></dd>

            <dt class="col-3">Data de Vencimento:</dt>
            <dd class="col-9"><?= htmlspecialchars($job['due_date']) ?></dd>

            <dt class="col-3">Criado por:</dt>
            <dd class="col-9"><?= htmlspecialchars($job['created_by_name']) ?></dd>

            <dt class="col-3">Atribuído a:</dt>
            <dd class="col-9"><?= htmlspecialchars($job['assigned_to_name'] ?? 'Ninguém') ?></dd>

            <dt class="col-3">Concluído em:</dt>
            <dd class="col-9"><?= htmlspecialchars($job['completed_at'] ?? 'N/A') ?></dd>

            <dt class="col-3">Criado em:</dt>
            <dd class="col-9"><?= htmlspecialchars($job['created_at']) ?></dd>

            <dt class="col-3">Última atualização:</dt>
            <dd class="col-9"><?= htmlspecialchars($job['updated_at']) ?></dd>
        </dl>

        <?php if (!empty($attachments)): ?>
            <h4 class="mt-4">Anexos:</h4>
            <div class="list-group">
                <?php foreach ($attachments as $attachment): ?>
                    <a href="<?= BASE_URL ?>/<?= htmlspecialchars($attachment['filepath']) ?>" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <?= htmlspecialchars($attachment['filename']) ?>
                        <small class="text-muted"><?= round($attachment['file_size'] / 1024 / 1024, 2) ?> MB</small>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted mt-4">Nenhum anexo para esta tarefa.</p>
        <?php endif; ?>

        <h4 class="mt-4">Notas:</h4>
        <div class="card mb-3">
            <div class="card-body">
                <form action="/jobs/<?= htmlspecialchars($job['id']) ?>/notes" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= App\Helpers\Session::generateCsrfToken() ?>">
                    <div class="mb-3">
                        <label class="form-label">Adicionar Nova Nota</label>
                        <textarea name="note" class="form-control" rows="3" placeholder="Digite sua nota aqui..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Adicionar Nota</button>
                </form>
            </div>
        </div>

        <?php if (!empty($notes)): ?>
            <div class="divide-y">
                <?php foreach ($notes as $note): ?>
                    <div>
                        <div class="row">
                            <div class="col-auto">
                                <div class="avatar avatar-sm" style="background-image: url(<?= BASE_URL ?>/static/000m.jpg)"></div>
                            </div>
                            <div class="col">
                                <div class="text-truncate">
                                    <strong><?= htmlspecialchars($note['user_name']) ?></strong>
                                    <span class="text-muted text-truncate ms-2"><?= nl2br(htmlspecialchars($note['note'])) ?></span>
                                </div>
                                <div class="text-muted"><?= htmlspecialchars((new DateTime($note['created_at']))->format('d/m/Y H:i')) ?></div>
                            </div>
                            <?php if (App\Helpers\Session::get('user_id') == $note['user_id'] || App\Helpers\Session::get('user_role') == 'admin'): ?>
                                <div class="col-auto">
                                    <form action="/jobs/notes/<?= htmlspecialchars($note['id']) ?>" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta nota?');">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <input type="hidden" name="csrf_token" value="<?= App\Helpers\Session::generateCsrfToken() ?>">
                                        <input type="hidden" name="job_id" value="<?= htmlspecialchars($job['id']) ?>">
                                        <button type="submit" class="btn btn-sm btn-icon btn-danger" title="Excluir Nota">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">Nenhuma nota para esta tarefa.</p>
        <?php endif; ?>
    </div>
</div>
