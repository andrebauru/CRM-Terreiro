<?php
// Verificar autenticação e autorização
if (!isset($adminOnly) || !$adminOnly) {
    echo '<div class="alert alert-danger">Acesso negado</div>';
    exit;
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Alterações de Perfil Pendentes</h2>
                <button class="btn btn-primary" id="refresh-btn">
                    <i class="bi bi-arrow-clockwise me-2"></i>Atualizar
                </button>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Filtrar por Status</label>
                            <select id="status-filter" class="form-select">
                                <option value="pending">Pendentes</option>
                                <option value="approved">Aprovadas</option>
                                <option value="rejected">Rejeitadas</option>
                                <option value="all">Todas</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button class="btn btn-secondary w-100" id="apply-filter-btn">
                                <i class="bi bi-funnel me-2"></i>Filtrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabela de Alterações -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="changes-table">
                        <thead class="table-light">
                            <tr>
                                <th>Usuário</th>
                                <th>Email Atual</th>
                                <th>Novo Email</th>
                                <th>Solicitado em</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="changes-tbody">
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Carregando...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Revisão -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Revisar Alteração de Perfil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="review-content"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="reject-btn">
                    <i class="bi bi-x-circle me-2"></i>Rejeitar
                </button>
                <button type="button" class="btn btn-success" id="approve-btn">
                    <i class="bi bi-check-circle me-2"></i>Aprovar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentChangeId = null;
    const reviewModal = new bootstrap.Modal(document.getElementById('reviewModal'));

    function formatDate(dateStr) {
        return new Date(dateStr).toLocaleDateString('pt-BR', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function getStatusBadge(status) {
        const badges = {
            pending: '<span class="badge bg-warning">Pendente</span>',
            approved: '<span class="badge bg-success">Aprovada</span>',
            rejected: '<span class="badge bg-danger">Rejeitada</span>'
        };
        return badges[status] || `<span class="badge bg-secondary">${status}</span>`;
    }

    async function loadChanges() {
        const status = document.getElementById('status-filter').value;
        const filterStatus = status === 'all' ? 'pending' : status;

        try {
            const response = await fetch(`/api/profile-changes.php?action=list&status=${filterStatus}`);
            const data = await response.json();

            const tbody = document.getElementById('changes-tbody');

            if (!data.ok) {
                tbody.innerHTML = `<tr><td colspan="6" class="alert alert-danger mb-0">${data.message}</td></tr>`;
                return;
            }

            if (data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Nenhuma alteração encontrada</td></tr>';
                return;
            }

            tbody.innerHTML = data.data.map(change => `
                <tr>
                    <td>
                        <strong>${change.user_name}</strong><br>
                        <small class="text-muted">#${change.user_id}</small>
                    </td>
                    <td>${change.user_email}</td>
                    <td>${change.email ? `<strong>${change.email}</strong>` : '<em class="text-muted">Sem alteração</em>'}</td>
                    <td>${formatDate(change.requested_at)}</td>
                    <td>${getStatusBadge(change.status)}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="showReview(${change.id})">
                            <i class="bi bi-eye me-1"></i>Ver
                        </button>
                    </td>
                </tr>
            `).join('');
        } catch (error) {
            alert('Erro ao carregar alterações: ' + error.message);
        }
    }

    function showReview(changeId) {
        currentChangeId = changeId;
        
        // Simular carregamento (você teria que criar um endpoint para buscar detalhes da alteração)
        const content = document.getElementById('review-content');
        content.innerHTML = '<div class="spinner-border" role="status"><span class="visually-hidden">Carregando...</span></div>';
        
        // Aqui você faria um fetch para pegar os detalhes da alteração
        reviewModal.show();
    }

    document.getElementById('approve-btn').addEventListener('click', async () => {
        if (!currentChangeId) return;

        const reviewNotes = prompt('Notas (opcional):');
        
        try {
            const response = await fetch('/api/profile-changes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'approve',
                    change_id: currentChangeId,
                    review_notes: reviewNotes || ''
                })
            });

            const data = await response.json();

            if (data.ok) {
                alert('Alteração aprovada com sucesso');
                reviewModal.hide();
                loadChanges();
            } else {
                alert('Erro: ' + data.message);
            }
        } catch (error) {
            alert('Erro ao aprovar: ' + error.message);
        }
    });

    document.getElementById('reject-btn').addEventListener('click', async () => {
        if (!currentChangeId) return;

        const reviewNotes = prompt('Motivo da rejeição (opcional):');
        if (reviewNotes === null) return; // Cancelou

        try {
            const response = await fetch('/api/profile-changes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'reject',
                    change_id: currentChangeId,
                    review_notes: reviewNotes || ''
                })
            });

            const data = await response.json();

            if (data.ok) {
                alert('Alteração rejeitada');
                reviewModal.hide();
                loadChanges();
            } else {
                alert('Erro: ' + data.message);
            }
        } catch (error) {
            alert('Erro ao rejeitar: ' + error.message);
        }
    });

    document.getElementById('refresh-btn').addEventListener('click', loadChanges);
    document.getElementById('apply-filter-btn').addEventListener('click', loadChanges);

    // Carregar ao iniciar
    loadChanges();
</script>
