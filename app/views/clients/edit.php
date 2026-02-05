<div class="form-errors"></div>
<form action="<?= ROUTE_BASE ?>/clients/<?= htmlspecialchars($client['id']) ?>" method="POST">
    <input type="hidden" name="_method" value="PUT">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <!-- Informações Básicas -->
    <div class="mb-4">
        <h4 class="text-muted mb-3">
            <i class="bi bi-person me-2"></i>Informações Básicas
        </h4>

        <div class="row">
            <div class="col-md-8">
                <div class="mb-3">
                    <label class="form-label required">Nome do Cliente</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($client['name']) ?>" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label class="form-label">CPF/CNPJ</label>
                    <input type="text" name="document" class="form-control" value="<?= htmlspecialchars($client['document'] ?? '') ?>" placeholder="000.000.000-00">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Data de Nascimento</label>
                    <input type="date" name="birth_date" class="form-control" value="<?= htmlspecialchars($client['birth_date'] ?? '') ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Origem do Cliente</label>
                    <select name="source" class="form-select">
                        <option value="">Selecione...</option>
                        <option value="indicacao" <?= ($client['source'] ?? '') === 'indicacao' ? 'selected' : '' ?>>Indicação</option>
                        <option value="site" <?= ($client['source'] ?? '') === 'site' ? 'selected' : '' ?>>Site</option>
                        <option value="instagram" <?= ($client['source'] ?? '') === 'instagram' ? 'selected' : '' ?>>Instagram</option>
                        <option value="facebook" <?= ($client['source'] ?? '') === 'facebook' ? 'selected' : '' ?>>Facebook</option>
                        <option value="whatsapp" <?= ($client['source'] ?? '') === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option>
                        <option value="presencial" <?= ($client['source'] ?? '') === 'presencial' ? 'selected' : '' ?>>Presencial</option>
                        <option value="outro" <?= ($client['source'] ?? '') === 'outro' ? 'selected' : '' ?>>Outro</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Contato -->
    <div class="mb-4">
        <h4 class="text-muted mb-3">
            <i class="bi bi-telephone me-2"></i>Contato
        </h4>

        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($client['email'] ?? '') ?>">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Telefone Principal</label>
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="bi bi-telephone"></i></span>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($client['phone'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Telefone Secundário</label>
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="bi bi-telephone"></i></span>
                        <input type="text" name="phone_secondary" class="form-control" value="<?= htmlspecialchars($client['phone_secondary'] ?? '') ?>">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">
                        <i class="bi bi-whatsapp text-success me-1"></i>WhatsApp
                    </label>
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="bi bi-whatsapp text-success"></i></span>
                        <input type="text" name="whatsapp" class="form-control" value="<?= htmlspecialchars($client['whatsapp'] ?? '') ?>" placeholder="5511999999999">
                    </div>
                    <?php if (!empty($client['whatsapp_link'])): ?>
                        <small class="form-text">
                            <a href="<?= htmlspecialchars($client['whatsapp_link']) ?>" target="_blank" class="text-success">
                                <i class="bi bi-whatsapp me-1"></i>Abrir conversa no WhatsApp
                            </a>
                        </small>
                    <?php else: ?>
                        <small class="form-text">Digite com código do país (55) + DDD + número.</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Endereço -->
    <div class="mb-4">
        <h4 class="text-muted mb-3">
            <i class="bi bi-geo-alt me-2"></i>Endereço
        </h4>

        <div class="mb-3">
            <label class="form-label">Endereço</label>
            <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($client['address'] ?? '') ?>" placeholder="Rua, número, complemento">
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="mb-3">
                    <label class="form-label">Cidade</label>
                    <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($client['city'] ?? '') ?>">
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label class="form-label">Estado</label>
                    <?php $states = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO']; ?>
                    <select name="state" class="form-select">
                        <option value="">Selecione...</option>
                        <?php foreach ($states as $state): ?>
                            <option value="<?= $state ?>" <?= ($client['state'] ?? '') === $state ? 'selected' : '' ?>><?= $state ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">CEP</label>
                    <input type="text" name="zip_code" class="form-control" value="<?= htmlspecialchars($client['zip_code'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Observações -->
    <div class="mb-4">
        <h4 class="text-muted mb-3">
            <i class="bi bi-journal-text me-2"></i>Observações
        </h4>

        <div class="mb-3">
            <label class="form-label">Anotações sobre o Cliente</label>
            <textarea name="notes" class="form-control" rows="4"><?= htmlspecialchars($client['notes'] ?? '') ?></textarea>
            <small class="form-text">Todas as alterações serão registradas no histórico.</small>
        </div>
    </div>

    <!-- Status -->
    <div class="mb-4">
        <h4 class="text-muted mb-3">
            <i class="bi bi-toggles me-2"></i>Status
        </h4>

        <div class="mb-3">
            <select name="status" class="form-select">
                <option value="active" <?= ($client['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Ativo</option>
                <option value="inactive" <?= ($client['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inativo</option>
                <option value="blocked" <?= ($client['status'] ?? '') === 'blocked' ? 'selected' : '' ?>>Bloqueado</option>
            </select>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>
            Atualizar Cliente
        </button>
    </div>
</form>
