<?php
use App\Helpers\Format;
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Listagem de Serviços</h3>
        <div class="card-actions">
            <a href="/services/create" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                Novo Serviço
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table card-table table-vcenter text-nowrap datatable">
            <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Preço</th>
                <th>Ativo</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($services)): ?>
                <tr>
                    <td colspan="5" class="text-center">Nenhum serviço encontrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($services as $service): ?>
                    <tr>
                        <td><?= htmlspecialchars($service['id']) ?></td>
                        <td><a href="/services/<?= htmlspecialchars($service['id']) ?>"><?= htmlspecialchars($service['name']) ?></a></td>
                        <td><?= htmlspecialchars(Format::currency((float)$service['price'], $settings ?? null)) ?></td>
                        <td>
                            <?php if ($service['is_active']): ?>
                                <span class="badge bg-success me-1">Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-secondary me-1">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/services/<?= htmlspecialchars($service['id']) ?>/edit" class="btn btn-sm btn-icon btn-primary" title="Editar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
                            </a>
                            <form action="/services/<?= htmlspecialchars($service['id']) ?>" method="POST" style="display:inline-block;" onsubmit="return confirm('Tem certeza que deseja excluir este serviço?');">
                                <input type="hidden" name="_method" value="DELETE">
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
