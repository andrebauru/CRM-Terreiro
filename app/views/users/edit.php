<div class="form-errors"></div>

<?php 
$isAdmin = \App\Helpers\Session::get('user_role') === 'admin'; 
$isEditingOwnProfile = $user['id'] == \App\Helpers\Session::get('user_id');
$isCommonUser = !$isAdmin;
?>

<!-- Aviso para usuários comuns -->
<?php if ($isCommonUser && $isEditingOwnProfile): ?>
<div class="alert alert-warning alert-dismissible fade show" role="alert" id="approval-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>Atenção!</strong> Suas alterações de perfil precisarão ser aprovadas por um administrador antes de serem aplicadas.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<form id="profile-edit-form" action="<?= ROUTE_BASE ?>/users/<?= htmlspecialchars($user['id']) ?>" method="POST">
    <input type="hidden" name="_method" value="PUT">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <input type="hidden" name="action" value="<?= $isCommonUser && $isEditingOwnProfile ? 'request-approval' : 'update' ?>">
    
    <div class="mb-3">
        <label class="form-label required">Nome Completo</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" placeholder="Seu nome completo" required>
    </div>
    <div class="mb-3">
        <label class="form-label required">Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" placeholder="seu@email.com" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Foto de Perfil</label>
        <input type="text" name="foto_perfil" class="form-control" value="<?= htmlspecialchars($user['foto_perfil'] ?? '') ?>" placeholder="URL da foto (opcional)">
        <small class="form-text">Deixe em branco para não alterar.</small>
    </div>
    <div class="mb-3">
        <label class="form-label">Senha</label>
        <input type="password" name="password" class="form-control" placeholder="Deixe em branco para não alterar">
        <small class="form-text">A senha deve ter no mínimo 6 caracteres.</small>
    </div>
    <?php if ($isAdmin): ?>
        <div class="mb-3">
            <label class="form-label">Função</label>
            <select name="role" class="form-select" required>
                <option value="staff" <?= $user['role'] == 'staff' ? 'selected' : '' ?>>Staff</option>
                <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>
    <?php endif; ?>
    
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary" id="submit-btn">
            <?php 
            if ($isAdmin) {
                echo 'Atualizar Usuário';
            } elseif ($isEditingOwnProfile) {
                echo 'Salvar Alterações';
            } else {
                echo 'Atualizar Perfil';
            }
            ?>
        </button>
    </div>
</form>

<script>
document.getElementById('profile-edit-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const isCommonUser = <?= json_encode($isCommonUser && $isEditingOwnProfile); ?>;
    const userId = <?= json_encode($user['id']); ?>;
    const submitBtn = document.getElementById('submit-btn');
    const originalText = submitBtn.textContent;
    
    if (!isCommonUser) {
        // Para admin, enviar normalmente
        this.submit();
        return;
    }
    
    // Para usuários comuns, usar o endpoint de aprovação
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';
    
    try {
        const formData = new FormData(this);
        const data = {
            user_id: userId,
            name: formData.get('name'),
            email: formData.get('email'),
            foto_perfil: formData.get('foto_perfil'),
            password: formData.get('password'),
            action: 'request'
        };
        
        const response = await fetch('/api/profile-changes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams(data)
        });
        
        const result = await response.json();
        
        if (result.ok) {
            submitBtn.classList.remove('btn-primary');
            submitBtn.classList.add('btn-success');
            submitBtn.textContent = 'Aguardando Aprovação de Admin';
            submitBtn.disabled = true;
            
            // Mostrar sucesso por 3 segundos
            setTimeout(() => {
                const modal = this.closest('.modal');
                if (modal) {
                    bootstrap.Modal.getInstance(modal)?.hide();
                }
            }, 2000);
        } else {
            alert('Erro: ' + result.message);
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    } catch (error) {
        alert('Erro ao enviar alterações: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
});
</script>
