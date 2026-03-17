<div class="row g-4">
    <!-- User Info Card -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center py-4">
                <span class="avatar avatar-xl bg-primary-lt text-primary mb-3" style="font-size: 2rem;">
                    <?= strtoupper(substr($user['name'], 0, 2)) ?>
                </span>
                <h3 class="mb-1"><?= htmlspecialchars($user['name']) ?></h3>
                <p class="text-muted mb-2">Usuário #<?= htmlspecialchars((string)$user['id']) ?></p>

                <?php
                $roleClass = $user['role'] === 'admin' ? 'danger' : 'primary';
                $roleLabel = $user['role'] === 'admin' ? 'Administrador' : 'Staff';
                ?>
                <span class="badge bg-<?= $roleClass ?>-lt text-<?= $roleClass ?> mb-3"><?= $roleLabel ?></span>

                <div class="btn-list justify-content-center">
                    <?php if ($isAdmin || (int)$user['id'] === (int)\App\Helpers\Session::get('user_id')): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mainModal"
                                data-url="<?= ROUTE_BASE ?>/users/<?= htmlspecialchars((string)$user['id']) ?>/edit"
                                data-title="Editar Usuário">
                            <i class="bi bi-pencil me-1"></i> Editar
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="card-body border-top">
                <h4 class="card-title mb-3">
                    <i class="bi bi-person me-2"></i>Informações
                </h4>

                <?php if (!empty($user['email'])): ?>
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar avatar-sm bg-primary-lt text-primary me-3">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <div>
                        <div class="text-muted small">Email</div>
                        <a href="mailto:<?= htmlspecialchars($user['email']) ?>" class="text-reset">
                            <?= htmlspecialchars($user['email']) ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <div class="d-flex align-items-center mb-3">
                    <span class="avatar avatar-sm bg-<?= $roleClass ?>-lt text-<?= $roleClass ?> me-3">
                        <i class="bi bi-shield-lock"></i>
                    </span>
                    <div>
                        <div class="text-muted small">Função</div>
                        <span><?= $roleLabel ?></span>
                    </div>
                </div>

                <?php if (!empty($user['created_at'])): ?>
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar avatar-sm bg-secondary-lt text-secondary me-3">
                        <i class="bi bi-calendar-plus"></i>
                    </span>
                    <div>
                        <div class="text-muted small">Criado em</div>
                        <span><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($user['updated_at'])): ?>
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar avatar-sm bg-secondary-lt text-secondary me-3">
                        <i class="bi bi-clock-history"></i>
                    </span>
                    <div>
                        <div class="text-muted small">Atualizado em</div>
                        <span><?= date('d/m/Y H:i', strtotime($user['updated_at'])) ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
