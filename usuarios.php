<?php
$pageTitle = 'CRM Terreiro - Usuários';
$activePage = 'usuarios';
require_once __DIR__ . '/app/views/partials/tw-head.php';

// ═══════════════════════════════════════════════════════════════
// SEGURANÇA: Forçar ID de sessão para usuários comuns
// Usuários comuns (role='user') só podem ver/editar seu próprio perfil
// ═══════════════════════════════════════════════════════════════
session_start();
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$currentUserRole = $_SESSION['user_role'] ?? 'staff';
$isAdmin = $currentUserRole === 'admin';
$isCommonUser = $currentUserRole === 'user';

// Se não está autenticado, redirecionar
if ($currentUserId <= 0) {
    header('Location: login.php');
    exit;
}

// Se for usuário comum e tentar acessar usuários, redirecionar para perfil pessoal
if ($isCommonUser) {
    // Verificar se há mudanças pendentes
    try {
        $pdo = require_once __DIR__ . '/app/database.php';
        $dbConnection = getPDOConnection();
        
        // Contar pendências
        $stmt = $dbConnection->prepare(
            "SELECT COUNT(*) as count FROM profile_pending_changes 
             WHERE user_id = ? AND status = 'pending'"
        );
        $stmt->execute([$currentUserId]);
        $pendencyCount = (int)($stmt->fetch()['count'] ?? 0);
        
        // Se há pendências, mostrar um aviso em vez da lista de usuários
        $hasPendencies = $pendencyCount > 0;
    } catch (Throwable $e) {
        $hasPendencies = false;
    }
}
?>
<body class="bg-[#f8fafc] font-sans text-slate-900">
    <!-- Overlay de segurança para print/captura -->
    <div id="printBlockOverlay" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.85);z-index:9999;color:#fff;font-size:1.3rem;align-items:center;justify-content:center;text-align:center;backdrop-filter:blur(2px);">
      <div>
        <i class="fa-solid fa-shield-halved" style="font-size:2.5rem;margin-bottom:16px;"></i><br>
        <b>Tentativa de captura, impressão ou cópia detectada.</b><br>
        Por segurança, o conteúdo foi ocultado temporariamente.<br>
        <button onclick="hidePrintBlockOverlay()" style="margin-top:24px;padding:12px 24px;border-radius:8px;background:#dc2626;color:#fff;font-weight:bold;border:none;">Voltar ao conteúdo</button>
      </div>
    </div>
  <div class="min-h-screen flex overflow-x-hidden">
    <?php require_once __DIR__ . '/app/views/partials/tw-sidebar.php'; ?>

    <main class="flex-1 min-w-0 p-4 pt-16 md:p-8">
      <header class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-2xl font-bold">Usuários</h1>
          <p class="text-slate-500">Controle de acessos</p>
        </div>
        <?php if ($isAdmin): ?>
        <button id="openModal" class="px-4 py-2 rounded-lg bg-accent text-white font-medium">Adicionar Usuário</button>
        <?php endif; ?>
      </header>

      <!-- INTERFACE PARA USUÁRIOS COMUNS -->
      <?php if ($isCommonUser): ?>
      
      <!-- Aviso de Pendências -->
      <?php if ($hasPendencies): ?>
      <div class="mb-6 bg-amber-50 border border-amber-200 rounded-2xl p-6 shadow-lg">
        <div class="flex items-start gap-4">
          <div class="flex-shrink-0">
            <i class="fa-solid fa-hourglass-end text-amber-600 text-2xl"></i>
          </div>
          <div class="flex-1">
            <h3 class="text-lg font-semibold text-amber-900 mb-1">Alterações Aguardando Aprovação</h3>
            <p class="text-sm text-amber-700 mb-3">
              Você enviou alterações ao seu perfil que estão aguardando revisão do administrador. 
              Você será notificado quando forem aprovadas ou rejeitadas.
            </p>
            <button 
              onclick="document.getElementById('viewPendenciesModal').style.display='flex'"
              class="text-sm text-amber-600 font-medium hover:text-amber-700 underline"
            >
              Ver detalhes das alterações pendentes →
            </button>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Card de Edição de Perfil -->
      <section class="bg-white border border-slate-200 rounded-2xl p-6 max-w-2xl">
        <h2 class="text-lg font-semibold mb-6">Meu Perfil</h2>
        <div id="commonUserProfileCard">
          <p class="text-slate-600">Carregando suas informações...</p>
        </div>
      </section>

      <!-- Modal de Pendências -->
      <div id="viewPendenciesModal" style="display:none" class="fixed inset-0 items-center justify-center bg-black/60 px-4 z-[60]">
        <div class="bg-white rounded-2xl w-full max-w-lg p-6 border border-slate-200 max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">Alterações Pendentes</h2>
            <button onclick="document.getElementById('viewPendenciesModal').style.display='none'" class="text-slate-400 hover:text-slate-600">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
          <div id="pendenciesContent">Carregando...</div>
        </div>
      </div>

      <?php else: ?>
      <!-- INTERFACE PARA ADMIN -->

      <section class="bg-white border border-slate-200 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold">Lista de Usuários</h2>
          <input id="searchInput" class="px-3 py-2 border border-slate-200 rounded-lg text-sm" placeholder="Buscar..." />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="text-slate-500">
              <tr>
                <th class="text-left pb-3">Nome</th>
                <th class="text-left pb-3">Email</th>
                <th class="text-left pb-3">Telefone</th>
                <th class="text-left pb-3">Perfil</th>
                <th class="text-left pb-3">Status</th>
                <th class="text-right pb-3">Ações</th>
              </tr>
            </thead>
            <tbody id="usersTable">
              <tr>
                <td class="py-3" colspan="6">Carregando...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <?php endif; ?>
    </main>
  </div>

  <div id="modal" class="fixed inset-0 hidden items-center justify-center bg-black/60 px-4 z-[60]">
    <div class="bg-white rounded-2xl w-full max-w-lg p-6 border border-slate-200 max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold" id="modalTitle">Novo Usuário</h2>
        <button id="closeModal" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <form id="userForm" class="space-y-4">
        <input type="hidden" id="userId" />
        <div>
          <label class="text-sm font-medium text-slate-700">Nome</label>
          <input id="userName" required class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Email</label>
          <input id="userEmail" type="email" required class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Telefone</label>
          <input id="userPhone" type="tel" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2" placeholder="(00) 00000-0000" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Perfil</label>
          <select id="userRole" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2">
            <option value="admin">Administrador</option>
            <option value="staff">Equipe</option>
            <option value="user">Usuário (só Financeiro)</option>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Status</label>
          <select id="userActive" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2">
            <option value="1">Ativo</option>
            <option value="0">Inativo</option>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Senha (opcional)</label>
          <input id="userPassword" type="password" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2" placeholder="Deixe vazio para manter" />
        </div>
        <div id="allowedPagesSection">
          <label class="text-sm font-medium text-slate-700 block mb-2">Páginas Permitidas <span class="text-slate-400 text-xs">(admin vê tudo)</span></label>
          <div id="allowedPagesGrid" class="grid grid-cols-2 gap-2">
            <?php
            $allPages = [
              'atendimentos' => 'Atendimentos',
              'gastos' => 'Gastos',
              'trabalhos' => 'Trabalhos',
              'clientes' => 'Clientes',
              'filhos' => 'Filhos',
              'quimbandeiro' => 'Quimbandeiro',
              'mensalidades' => 'Mensalidades',
              'giras' => 'Registro de Giras',
              'servicos' => 'Serviços',
              'avisos' => 'Avisos',
              'chat' => 'Chat Interno',
              'financeiro' => 'Financeiro',
              'usuarios' => 'Usuários',
              'relatorios' => 'Relatórios',
              'configuracoes' => 'Configurações',
            ];
            foreach ($allPages as $pageKey => $pageLabel): ?>
              <label class="flex items-center gap-2 text-sm text-slate-600 bg-slate-50 rounded-lg px-3 py-2 cursor-pointer hover:bg-slate-100">
                <input type="checkbox" class="page-check rounded border-slate-300" value="<?= $pageKey ?>" />
                <?= htmlspecialchars($pageLabel) ?>
              </label>
            <?php endforeach; ?>
          </div>
          <div class="flex gap-2 mt-2">
            <button type="button" onclick="checkAllPages(true)" class="text-xs text-blue-600 hover:underline">Marcar todos</button>
            <button type="button" onclick="checkAllPages(false)" class="text-xs text-blue-600 hover:underline">Desmarcar todos</button>
          </div>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" id="cancelModal" class="px-4 py-2 rounded-lg border border-slate-200">Cancelar</button>
          <button type="submit" class="px-4 py-2 rounded-lg bg-accent text-white">Salvar</button>
        </div>
      </form>
    </div>
  </div>

  <?php require_once __DIR__ . '/app/views/partials/tw-scripts.php'; ?>
  <script>
    initSensitivePageProtection('usuarios');
    
    // ═══════════════════════════════════════════════════════════════
    // Variáveis globais
    // ═══════════════════════════════════════════════════════════════
    const isAdmin = <?= json_encode($isAdmin); ?>;
    const isCommonUser = <?= json_encode($isCommonUser); ?>;
    const currentUserId = <?= json_encode($currentUserId); ?>;

    // ═══════════════════════════════════════════════════════════════
    // LÓGICA PARA USUÁRIOS COMUNS
    // ═══════════════════════════════════════════════════════════════
    if (isCommonUser) {
      // Carregar perfil do próprio usuário
      async function loadCommonUserProfile() {
        try {
          const response = await fetch(`api/users.php?action=list`, { cache: 'no-store' });
          const data = await response.json();
          const userProfile = data.data?.[0];
          
          if (!userProfile) {
            document.getElementById('commonUserProfileCard').innerHTML = 
              '<p class="text-red-600">Erro ao carregar perfil.</p>';
            return;
          }
          
          document.getElementById('commonUserProfileCard').innerHTML = `
            <div class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="text-sm font-medium text-slate-700">Nome</label>
                  <p class="mt-2 text-slate-900">${escapeHtml(userProfile.name)}</p>
                </div>
                <div>
                  <label class="text-sm font-medium text-slate-700">Email</label>
                  <p class="mt-2 text-slate-900">${escapeHtml(userProfile.email)}</p>
                </div>
                <div>
                  <label class="text-sm font-medium text-slate-700">Telefone</label>
                  <p class="mt-2 text-slate-900">${userProfile.phone || '-'}</p>
                </div>
              </div>
              <button 
                onclick="editCommonUserProfile()"
                class="mt-6 px-4 py-2 rounded-lg bg-accent text-white font-medium hover:opacity-90"
              >
                <i class="fa-solid fa-edit mr-2"></i>Solicitar Alterações
              </button>
            </div>
          `;
          
          // Carregar pendências se houver
          loadPendencies();
        } catch (error) {
          console.error('Erro ao carregar perfil:', error);
        }
      }
      
      // Carregar alterações pendentes
      async function loadPendencies() {
        try {
          const response = await fetch(`api/profile-changes.php?action=list&status=pending`, { 
            cache: 'no-store' 
          });
          const data = await response.json();
          const pendencies = data.data || [];
          const userPendencies = pendencies.filter(p => p.user_id === currentUserId);
          
          if (userPendencies.length > 0) {
            const pendency = userPendencies[0];
            let detailsHtml = '<div class="space-y-3">';
            
            if (pendency.name) {
              detailsHtml += `<div>
                <label class="text-sm font-medium text-slate-700">Nome</label>
                <p class="text-sm text-amber-700">${escapeHtml(pendency.name)}</p>
              </div>`;
            }
            if (pendency.email) {
              detailsHtml += `<div>
                <label class="text-sm font-medium text-slate-700">Email</label>
                <p class="text-sm text-amber-700">${escapeHtml(pendency.email)}</p>
              </div>`;
            }
            
            detailsHtml += `<div class="pt-2 border-t border-amber-200">
              <p class="text-xs text-slate-500">Enviado em: ${new Date(pendency.requested_at).toLocaleDateString('pt-BR')}</p>
            </div></div>`;
            
            document.getElementById('pendenciesContent').innerHTML = detailsHtml;
          }
        } catch (error) {
          console.error('Erro ao carregar pendências:', error);
        }
      }
      
      // Modal de edição para usuário comum
      function editCommonUserProfile() {
        const name = prompt('Novo nome (deixe vazio para não alterar):');
        if (name === null) return;
        
        const email = prompt('Novo email (deixe vazio para não alterar):');
        if (email === null) return;
        
        const payload = new URLSearchParams({
          action: 'request',
          user_id: currentUserId,
          name: name || '',
          email: email || '',
        });
        
        fetch('api/profile-changes.php', { method: 'POST', body: payload })
          .then(res => res.json())
          .then(data => {
            if (data.ok) {
              alert('✓ Alterações enviadas para aprovação do administrador. Você será notificado em breve.');
              loadCommonUserProfile();
            } else {
              alert('✗ Erro: ' + (data.message || 'Erro desconhecido'));
            }
          });
      }
      
      function escapeHtml(text) {
        const map = {
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#039;'
        };
        return String(text || '').replace(/[&<>"']/g, m => map[m]);
      }
      
      // Carregar ao inicializar
      loadCommonUserProfile();
    } else {
      // ═══════════════════════════════════════════════════════════════
      // LÓGICA PARA ADMIN (mantém o comportamento original)
      // ═══════════════════════════════════════════════════════════════
      const modal = document.getElementById('modal');
      const openModal = document.getElementById('openModal');
      const closeModal = document.getElementById('closeModal');
      const cancelModal = document.getElementById('cancelModal');
      const userForm = document.getElementById('userForm');
      const usersTable = document.getElementById('usersTable');
      const searchInput = document.getElementById('searchInput');
      const modalTitle = document.getElementById('modalTitle');

      const userId = document.getElementById('userId');
      const userName = document.getElementById('userName');
      const userEmail = document.getElementById('userEmail');
      const userPhone = document.getElementById('userPhone');
      const userRole = document.getElementById('userRole');
      const userActive = document.getElementById('userActive');
      const userPassword = document.getElementById('userPassword');

      let usersCache = [];

      const openFn = (show) => toggleModal(modal, show);

      function checkAllPages(checked) {
        document.querySelectorAll('.page-check').forEach(cb => cb.checked = checked);
      }

      function getSelectedPages() {
        return Array.from(document.querySelectorAll('.page-check:checked')).map(cb => cb.value).join(',');
      }

      function setSelectedPages(csv) {
        document.querySelectorAll('.page-check').forEach(cb => cb.checked = false);
        if (!csv) return;
        const pages = csv.split(',').map(s => s.trim());
        document.querySelectorAll('.page-check').forEach(cb => {
          if (pages.includes(cb.value)) cb.checked = true;
        });
      }

      const resetForm = () => {
        userId.value = '';
        userName.value = '';
        userEmail.value = '';
        userPhone.value = '';
        userRole.value = 'staff';
        userActive.value = '1';
        userPassword.value = '';
        checkAllPages(false);
      };

      const loadUsers = async () => {
        usersTable.innerHTML = '<tr><td class="py-3" colspan="6">Carregando...</td></tr>';
        const response = await fetch(`api/users.php?action=list&t=${Date.now()}`, { cache: 'no-store' });
        const data = await response.json();
        usersCache = data.data || [];
        renderUsers(usersCache);
      };

      const renderUsers = (rows) => {
        const html = rows.map((user) => `
          <tr class="border-t border-slate-100">
            <td class="py-3">${user.name}</td>
            <td class="py-3">${user.email}</td>
            <td class="py-3 text-slate-500 text-xs">${user.phone || '-'}</td>
            <td class="py-3">${user.role}</td>
            <td class="py-3">${user.is_active == 1 ? 'Ativo' : 'Inativo'}</td>
            <td class="py-3 text-right">
              <button class="text-accent" data-edit="${user.id}">Editar</button>
              <button class="text-red-500 ml-3" data-delete="${user.id}">Excluir</button>
            </td>
          </tr>
        `).join('');
        usersTable.innerHTML = html || '<tr><td class="py-3" colspan="6">Nenhum usuário encontrado.</td></tr>';
      };

      openModal.addEventListener('click', () => {
        resetForm();
        modalTitle.textContent = 'Novo Usuário';
        openFn(true);
      });

      [closeModal, cancelModal].forEach((btn) => btn.addEventListener('click', () => openFn(false)));

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') openFn(false);
      });

      usersTable.addEventListener('click', (event) => {
        const editId = event.target.getAttribute('data-edit');
        const deleteId = event.target.getAttribute('data-delete');

        if (editId) {
          const user = usersCache.find((item) => String(item.id) === editId);
          if (!user) return;
          userId.value = user.id;
          userName.value = user.name || '';
          userEmail.value = user.email || '';
          userPhone.value = user.phone || '';
          userRole.value = user.role || 'staff';
          userActive.value = String(user.is_active ?? 1);
          userPassword.value = '';
          setSelectedPages(user.allowed_pages || '');
          modalTitle.textContent = 'Editar Usuário';
          openFn(true);
        }

        if (deleteId) {
          if (!confirm('Deseja excluir este usuário?')) return;
          fetch('api/users.php', {
            method: 'POST',
            body: new URLSearchParams({ action: 'delete', id: deleteId }),
          }).then(() => loadUsers());
        }
      });

      userForm.addEventListener('submit', (event) => {
        event.preventDefault();
        const payload = new URLSearchParams({
          action: userId.value ? 'update' : 'create',
          id: userId.value,
          name: userName.value,
          email: userEmail.value,
          phone: userPhone.value,
          role: userRole.value,
          is_active: userActive.value,
          password: userPassword.value,
          allowed_pages: getSelectedPages(),
        });
        fetch('api/users.php', { method: 'POST', body: payload })
          .then(() => {
            openFn(false);
            loadUsers();
          });
      });

      searchInput.addEventListener('input', () => {
        const term = searchInput.value.toLowerCase();
        const filtered = usersCache.filter((user) =>
          [user.name, user.email, user.role].some((value) => (value || '').toLowerCase().includes(term))
        );
        renderUsers(filtered);
      });

      loadUsers();
    }
  </script>
</body>
</html>