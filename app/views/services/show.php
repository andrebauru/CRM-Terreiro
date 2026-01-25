<div class="card">
    <div class="card-header">
        <h3 class="card-title">Detalhes do Serviço: <?= htmlspecialchars($service['name']) ?></h3>
        <div class="card-actions">
            <a href="/services/<?= htmlspecialchars($service['id']) ?>/edit" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
                Editar
            </a>
            <form action="/services/<?= htmlspecialchars($service['id']) ?>/delete" method="POST" style="display:inline-block;" onsubmit="return confirm('Tem certeza que deseja excluir este serviço?');">
                <input type="hidden" name="csrf_token" value="<?= App\Helpers\Session::generateCsrfToken() ?>">
                <button type="submit" class="btn btn-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                    Excluir
                </button>
            </form>
            <a href="/services" class="btn btn-secondary">Voltar para a lista</a>
        </div>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-3">ID:</dt>
            <dd class="col-9"><?= htmlspecialchars($service['id']) ?></dd>

            <dt class="col-3">Nome:</dt>
            <dd class="col-9"><?= htmlspecialchars($service['name']) ?></dd>

            <dt class="col-3">Descrição:</dt>
            <dd class="col-9"><?= nl2br(htmlspecialchars($service['description'])) ?></dd>

            <dt class="col-3">Preço:</dt>
            <dd class="col-9"><?= 'R$ ' . number_format($service['price'], 2, ',', '.') ?></dd>

            <dt class="col-3">Ativo:</dt>
            <dd class="col-9">
                <?php if ($service['is_active']): ?>
                    <span class="badge bg-success me-1">Sim</span>
                <?php else: ?>
                    <span class="badge bg-secondary me-1">Não</span>
                <?php endif; ?>
            </dd>

            <dt class="col-3">Criado em:</dt>
            <dd class="col-9"><?= htmlspecialchars($service['created_at']) ?></dd>

            <dt class="col-3">Última atualização:</dt>
            <dd class="col-9"><?= htmlspecialchars($service['updated_at']) ?></dd>
        </dl>
    </div>
</div>
