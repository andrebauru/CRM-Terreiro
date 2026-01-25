<div class="card">
    <div class="card-header">
        <h3 class="card-title">Listagem de Tarefas</h3>
        <div class="card-actions">
            <a href="/jobs/create" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                Nova Tarefa
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table card-table table-vcenter text-nowrap datatable">
            <thead>
            <tr>
                <th>ID</th>
                <th>Título</th>
                <th>Cliente</th>
                <th>Serviço</th>
                <th>Status</th>
                <th>Prioridade</th>
                <th>Criado em</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($jobs)): ?>
                <tr>
                    <td colspan="8" class="text-center">Nenhuma tarefa encontrada.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($jobs as $job): ?>
                    <tr>
                        <td><?= htmlspecialchars($job['id']) ?></td>
                        <td><a href="/jobs/<?= htmlspecialchars($job['id']) ?>"><?= htmlspecialchars($job['title']) ?></a></td>
                        <td><?= htmlspecialchars($job['client_name']) ?></td>
                        <td><?= htmlspecialchars($job['service_name']) ?></td>
                        <td><span class="badge bg-<?=
                            ($job['status'] == 'completed') ? 'success' :
                            (($job['status'] == 'in_progress') ? 'info' :
                            (($job['status'] == 'pending') ? 'warning' : 'danger'))
                            ?>-lt"><?= htmlspecialchars(ucfirst($job['status'])) ?></span>
                        </td>
                        <td><span class="badge bg-<?=
                            ($job['priority'] == 'high') ? 'danger' :
                            (($job['priority'] == 'medium') ? 'warning' : 'secondary'))
                            ?>-lt"><?= htmlspecialchars(ucfirst($job['priority'])) ?></span>
                        </td>
                        <td><?= htmlspecialchars((new DateTime($job['created_at']))->format('d/m/Y H:i')) ?></td>
                        <td>
                            <a href="/jobs/<?= htmlspecialchars($job['id']) ?>/edit" class="btn btn-sm btn-icon btn-primary" title="Editar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
                            </a>
                            <form action="/jobs/<?= htmlspecialchars($job['id']) ?>/delete" method="POST" style="display:inline-block;" onsubmit="return confirm('Tem certeza que deseja excluir esta tarefa?');">
                                <input type="hidden" name="csrf_token" value="<?= App\Helpers\Session::generateCsrfToken() ?>">
                                <button type="submit" class="btn btn-sm btn-icon btn-danger" title="Excluir">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
