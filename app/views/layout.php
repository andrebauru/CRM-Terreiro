<?php
use App\Helpers\Session;
use App\Models\Setting;

$settings = (new Setting())->get();
$companyName = $settings['company_name'] ?? APP_NAME;
$clientName = $settings['client_name'] ?? '';
$logoPath = $settings['logo_path'] ?? null;
$title = $title ?? APP_NAME;

// Theme switcher logic
$currentTheme = 'theme-dark';
if (isset($_GET['theme']) && ($_GET['theme'] === 'dark' || $_GET['theme'] === 'light')) {
    $currentTheme = 'theme-' . $_GET['theme'];
    setcookie('theme_preference', $currentTheme, [
        'expires' => time() + (86400 * 30),
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => false,
    ]);
} elseif (isset($_COOKIE['theme_preference']) && ($_COOKIE['theme_preference'] === 'theme-dark' || $_COOKIE['theme_preference'] === 'theme-light')) {
    $currentTheme = $_COOKIE['theme_preference'];
}

// Get current page for active menu
$currentUri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
// Remove ROUTE_BASE do início da URI para obter a página atual
$routeBase = defined('ROUTE_BASE') ? trim(ROUTE_BASE, '/') : '';
if (!empty($routeBase) && str_starts_with($currentUri, $routeBase)) {
    $currentUri = ltrim(substr($currentUri, strlen($routeBase)), '/');
}
$currentPage = explode('/', $currentUri)[0] ?? '';

// Helper function to check if menu item is active
function isActive($page, $currentPage) {
    return $page === $currentPage ? 'active' : '';
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <?php require_once BASE_PATH . '/app/views/partials/header.php'; ?>
</head>
<body class="layout-fluid <?= $currentTheme ?>">
<div class="page">
    <!-- Sidebar -->
    <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Logo / Brand -->
            <h1 class="navbar-brand navbar-brand-autodark">
                <a href="<?= ROUTE_BASE ?>/dashboard" class="d-flex align-items-center">
                    <?php if (!empty($logoPath)): ?>
                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($logoPath) ?>" height="36" alt="Logo" class="navbar-brand-image">
                    <?php else: ?>
                        <span class="fs-4 fw-bold"><?= htmlspecialchars($companyName) ?></span>
                    <?php endif; ?>
                </a>
            </h1>

            <!-- User Menu (Mobile) -->
            <div class="navbar-nav flex-row order-md-last">
                <!-- Theme Toggle -->
                <div class="nav-item d-none d-md-flex me-3">
                    <div class="btn-list">
                        <a href="?theme=dark" class="btn btn-ghost-light btn-icon <?= ($currentTheme === 'theme-dark') ? 'd-none' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Modo Escuro">
                            <i class="bi bi-moon-stars"></i>
                        </a>
                        <a href="?theme=light" class="btn btn-ghost-light btn-icon <?= ($currentTheme === 'theme-light') ? 'd-none' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Modo Claro">
                            <i class="bi bi-sun"></i>
                        </a>
                    </div>
                </div>

                <!-- User Dropdown -->
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Menu do usuário">
                        <?php
                        $userName = (string) Session::get('user_name');
                        $userRole = (string) Session::get('user_role');
                        $userInitials = $userName !== '' ? strtoupper(substr($userName, 0, 2)) : 'U';
                        ?>
                        <span class="avatar avatar-sm bg-primary text-white">
                            <?= htmlspecialchars($userInitials) ?>
                        </span>
                        <div class="d-none d-xl-block ps-2">
                            <div class="fw-medium"><?= htmlspecialchars($userName) ?></div>
                            <div class="mt-1 small text-muted"><?= htmlspecialchars($userRole !== '' ? ucfirst($userRole) : '') ?></div>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                        <a href="<?= ROUTE_BASE ?>/users/<?= urlencode((string) Session::get('user_id')) ?>/edit" class="dropdown-item">
                            <i class="bi bi-person me-2"></i>Meu Perfil
                        </a>
                        <a href="<?= ROUTE_BASE ?>/settings" class="dropdown-item">
                            <i class="bi bi-gear me-2"></i>Configurações
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="<?= ROUTE_BASE ?>/logout" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-left me-2"></i>Sair
                        </a>
                    </div>
                </div>
            </div>

            <!-- Navigation Menu -->
            <div class="collapse navbar-collapse" id="sidebar-menu">
                <ul class="navbar-nav pt-lg-3">
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('dashboard', $currentPage) ?: isActive('', $currentPage) ?>" href="<?= ROUTE_BASE ?>/dashboard">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="bi bi-grid-1x2"></i>
                            </span>
                            <span class="nav-link-title">Dashboard</span>
                        </a>
                    </li>

                    <!-- Separator -->
                    <li class="nav-item mt-2 mb-1">
                        <small class="text-muted text-uppercase px-3 fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Gestão</small>
                    </li>

                    <!-- Clients -->
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('clients', $currentPage) ?>" href="<?= ROUTE_BASE ?>/clients">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="bi bi-people"></i>
                            </span>
                            <span class="nav-link-title">Clientes</span>
                        </a>
                    </li>

                    <!-- Jobs -->
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('jobs', $currentPage) ?>" href="<?= ROUTE_BASE ?>/jobs">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="bi bi-clipboard-check"></i>
                            </span>
                            <span class="nav-link-title">Trabalhos</span>
                        </a>
                    </li>

                    <!-- Services -->
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('services', $currentPage) ?>" href="<?= ROUTE_BASE ?>/services">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="bi bi-box-seam"></i>
                            </span>
                            <span class="nav-link-title">Serviços</span>
                        </a>
                    </li>

                    <!-- Separator -->
                    <li class="nav-item mt-3 mb-1">
                        <small class="text-muted text-uppercase px-3 fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Sistema</small>
                    </li>

                    <!-- Users -->
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('users', $currentPage) ?>" href="<?= ROUTE_BASE ?>/users">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="bi bi-person-gear"></i>
                            </span>
                            <span class="nav-link-title">Usuários</span>
                        </a>
                    </li>

                    <!-- Settings -->
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('settings', $currentPage) ?>" href="<?= ROUTE_BASE ?>/settings">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="bi bi-sliders"></i>
                            </span>
                            <span class="nav-link-title">Configurações</span>
                        </a>
                    </li>

                    <!-- Logout (Mobile) -->
                    <li class="nav-item d-lg-none mt-3">
                        <a class="nav-link text-danger" href="<?= ROUTE_BASE ?>/logout">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="bi bi-box-arrow-left"></i>
                            </span>
                            <span class="nav-link-title">Sair</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="page-wrapper">
        <!-- Page Header -->
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <!-- Breadcrumb -->
                        <?php if (!empty($breadcrumb)): ?>
                        <nav aria-label="breadcrumb" class="mb-1">
                            <ol class="breadcrumb breadcrumb-arrows">
                                <li class="breadcrumb-item"><a href="<?= ROUTE_BASE ?>/dashboard">Home</a></li>
                                <?php foreach ($breadcrumb as $item): ?>
                                    <?php if (isset($item['url'])): ?>
                                        <li class="breadcrumb-item"><a href="<?= $item['url'] ?>"><?= htmlspecialchars($item['label']) ?></a></li>
                                    <?php else: ?>
                                        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($item['label']) ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ol>
                        </nav>
                        <?php endif; ?>

                        <!-- Page Title -->
                        <h2 class="page-title">
                            <?= htmlspecialchars($title) ?>
                        </h2>
                    </div>

                    <!-- Page Actions -->
                    <?php if (!empty($pageActions)): ?>
                    <div class="col-auto ms-auto d-print-none">
                        <div class="btn-list">
                            <?= $pageActions ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Page Body -->
        <div class="page-body">
            <div class="container-xl">
                <!-- Flash Messages -->
                <?php if (Session::has('success')): ?>
                    <div class="alert alert-success alert-dismissible animate-fade-in" role="alert">
                        <div class="d-flex">
                            <div class="me-2">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div><?= Session::get('success') ?></div>
                        </div>
                        <a class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></a>
                    </div>
                    <?php Session::remove('success'); ?>
                <?php endif; ?>

                <?php if (Session::has('error')): ?>
                    <div class="alert alert-danger alert-dismissible animate-fade-in" role="alert">
                        <div class="d-flex">
                            <div class="me-2">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <div><?= Session::get('error') ?></div>
                        </div>
                        <a class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></a>
                    </div>
                    <?php Session::remove('error'); ?>
                <?php endif; ?>

                <?php if (Session::has('warning')): ?>
                    <div class="alert alert-warning alert-dismissible animate-fade-in" role="alert">
                        <div class="d-flex">
                            <div class="me-2">
                                <i class="bi bi-exclamation-circle"></i>
                            </div>
                            <div><?= Session::get('warning') ?></div>
                        </div>
                        <a class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></a>
                    </div>
                    <?php Session::remove('warning'); ?>
                <?php endif; ?>

                <!-- Main Content -->
                <div class="animate-fade-in">
                    <?= $content ?>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <?php require_once BASE_PATH . '/app/views/partials/footer.php'; ?>
    </div>
</div>

<!-- Main Modal -->
<div class="modal fade" id="mainModal" tabindex="-1" aria-labelledby="mainModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mainModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="modal-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <span class="text-muted">Carregando...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Delete Modal -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
                <h3 class="mt-3">Tem certeza?</h3>
                <p class="text-muted" id="confirmDeleteMessage">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Excluir</button>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/static/js/app.js" defer></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var mainModal = new bootstrap.Modal(document.getElementById('mainModal'));
        var mainModalTitle = document.getElementById('mainModalLabel');
        var mainModalBody = document.querySelector('#mainModal .modal-body');
        var modalLoading = document.createElement('div'); // Create a new loading element
        modalLoading.classList.add('modal-loading');
        modalLoading.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <span class="text-muted">Carregando...</span>
        `;
        
        document.body.addEventListener('click', function(e) {
            var trigger = e.target.closest('[data-bs-toggle="modal"][data-target="#mainModal"]');
            if (trigger) {
                e.preventDefault();
                var url = trigger.getAttribute('data-url');
                var title = trigger.getAttribute('data-title') || 'Carregando...';

                mainModalTitle.textContent = title;
                mainModalBody.innerHTML = ''; // Clear previous content
                mainModalBody.appendChild(modalLoading); // Add loading spinner
                modalLoading.style.display = 'flex'; // Ensure it's visible

                mainModal.show();

                if (url) {
                    fetch(url)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.text();
                        })
                        .then(html => {
                            mainModalBody.innerHTML = html;
                        })
                        .catch(error => {
                            console.error('Erro ao carregar conteúdo do modal:', error);
                            mainModalBody.innerHTML = '<div class="alert alert-danger" role="alert">Erro ao carregar conteúdo. Tente novamente.</div>';
                        })
                        .finally(() => {
                            modalLoading.style.display = 'none'; // Hide loading spinner
                        });
                }
            }
        });

        // Ensure loading spinner is shown when modal is hidden, ready for next load
        document.getElementById('mainModal').addEventListener('hidden.bs.modal', function () {
            // Remove previous content, show loading for next time
            mainModalBody.innerHTML = '';
            mainModalBody.appendChild(modalLoading);
            modalLoading.style.display = 'flex';
        });
    });
</script>
</body>
</html>
