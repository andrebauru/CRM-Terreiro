<?php
// Ensure Session helper is loaded
use App\Helpers\Session;

$title = "Login - " . APP_NAME;
$csrfToken = Session::generateCsrfToken();
?>

<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title><?= htmlspecialchars($title) ?></title>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #dc2626;
            --primary-hover: #b91c1c;
            --primary-light: rgba(220, 38, 38, 0.08);
            --bg-page: #f1f5f9;
            --bg-card: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --input-bg: #f8fafc;
            --success-color: #10b981;
            --error-color: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-page);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Background Pattern - Subtle warm dots */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 20% 80%, rgba(220, 38, 38, 0.06) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(220, 38, 38, 0.04) 0%, transparent 50%);
            pointer-events: none;
        }

        /* Floating Shapes */
        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.06), rgba(239, 68, 68, 0.04));
            animation: float 20s infinite ease-in-out;
        }

        .shape:nth-child(1) {
            width: 300px;
            height: 300px;
            top: -150px;
            right: -100px;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 200px;
            height: 200px;
            bottom: -100px;
            left: -50px;
            animation-delay: -5s;
        }

        .shape:nth-child(3) {
            width: 150px;
            height: 150px;
            top: 50%;
            right: 10%;
            animation-delay: -10s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 1;
        }

        .login-card {
            background: var(--bg-card);
            border-radius: 24px;
            padding: 48px 40px;
            box-shadow:
                0 4px 6px -1px rgba(0, 0, 0, 0.05),
                0 10px 30px -5px rgba(0, 0, 0, 0.08),
                0 0 0 1px var(--border-color);
        }

        .logo-container {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-container img {
            width: 120px;
            height: 120px;
            object-fit: contain;
            filter: drop-shadow(0 4px 12px rgba(220, 38, 38, 0.15));
            transition: transform 0.3s ease;
        }

        .logo-container img:hover {
            transform: scale(1.05);
        }

        .login-title {
            text-align: center;
            margin-bottom: 8px;
        }

        .login-title h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .login-title p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.25);
            color: #059669;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: #dc2626;
        }

        .alert i {
            font-size: 1.25rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.1rem;
            transition: color 0.2s ease;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px 14px 48px;
            font-size: 1rem;
            font-family: inherit;
            color: var(--text-primary);
            background: var(--input-bg);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-control::placeholder {
            color: var(--text-muted);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px var(--primary-light);
            background: #fff;
        }

        .form-control:focus + i,
        .input-wrapper:focus-within i {
            color: var(--primary-color);
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
            font-size: 1.1rem;
            transition: color 0.2s ease;
        }

        .password-toggle:hover {
            color: var(--text-secondary);
        }

        .btn-login {
            width: 100%;
            padding: 16px 24px;
            font-size: 1rem;
            font-weight: 600;
            font-family: inherit;
            color: white;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px -10px rgba(220, 38, 38, 0.4);
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login i {
            font-size: 1.2rem;
        }

        .footer-text {
            text-align: center;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .footer-text p {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .footer-text .brand {
            color: var(--text-secondary);
            font-weight: 600;
        }

        /* Register info box */
        .register-info {
            background: #fef3c7;
            border: 1px solid #fde68a;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 12px;
            font-size: 0.85rem;
            color: #92400e;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            line-height: 1.5;
        }

        .register-info i {
            margin-top: 2px;
            flex-shrink: 0;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .form-group:nth-child(1) { animation: fadeInUp 0.6s ease-out 0.1s both; }
        .form-group:nth-child(2) { animation: fadeInUp 0.6s ease-out 0.2s both; }
        .btn-login { animation: fadeInUp 0.6s ease-out 0.3s both; }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 36px 24px;
                border-radius: 20px;
            }

            .logo-container img {
                width: 100px;
                height: 100px;
            }

            .login-title h1 {
                font-size: 1.5rem;
            }
        }

        /* Loading state */
        .btn-login.loading {
            pointer-events: none;
            opacity: 0.8;
        }

        .btn-login.loading .btn-text {
            visibility: hidden;
        }

        .btn-login.loading::after {
            content: '';
            position: absolute;
            width: 24px;
            height: 24px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Floating Background Shapes -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="login-container">
        <div class="login-card">
            <!-- Logo -->
            <div class="logo-container">
                <img src="<?= BASE_URL ?>/static/logo-quimbanda.png" alt="<?= htmlspecialchars(APP_NAME) ?>">
            </div>

            <!-- Title -->
            <div class="login-title">
                <h1>Bem-vindo de volta</h1>
                <p>Entre com suas credenciais para acessar o sistema</p>
            </div>

            <!-- Flash Messages -->
            <?php $flashSuccess = Session::getFlash('success'); ?>
            <?php if (!empty($flashSuccess)): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?= htmlspecialchars($flashSuccess) ?></span>
                </div>
            <?php endif; ?>

            <?php $flashError = Session::getFlash('error'); ?>
            <?php if (!empty($flashError)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span><?= htmlspecialchars($flashError) ?></span>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="<?= ROUTE_BASE ?>/login" method="POST" autocomplete="off" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="form-group">
                    <label class="form-label">E-mail</label>
                    <div class="input-wrapper">
                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            placeholder="seu@email.com"
                            autocomplete="email"
                            required
                        >
                        <i class="bi bi-envelope"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Senha</label>
                    <div class="input-wrapper">
                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            placeholder="Digite sua senha"
                            autocomplete="current-password"
                            id="passwordInput"
                            required
                        >
                        <i class="bi bi-lock"></i>
                        <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Mostrar senha">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <span class="btn-text">Entrar no Sistema</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <!-- Footer -->
            <div class="footer-text">
                <p style="margin-bottom: 10px;">
                    <a href="#" onclick="toggleRegister(event)" style="color: var(--primary-color); text-decoration: none; font-weight: 500;">
                        Não tem conta? Cadastre-se
                    </a>
                </p>
                <p><span class="brand"><?= htmlspecialchars(APP_NAME) ?></span> &mdash; Sistema de Gestão</p>
            </div>
        </div>

        <!-- Registration Card (hidden by default) -->
        <div class="login-card" id="registerCard" style="display: none;">
            <div class="logo-container">
                <img src="<?= BASE_URL ?>/static/logo-quimbanda.png" alt="<?= htmlspecialchars(APP_NAME) ?>">
            </div>
            <div class="login-title">
                <h1>Criar Conta</h1>
                <p>Preencha os dados para se cadastrar</p>
            </div>
            <div id="registerAlert" style="display:none;" class="alert"></div>
            <form autocomplete="off" id="registerForm">
                <div class="form-group">
                    <label class="form-label">Nome completo</label>
                    <div class="input-wrapper">
                        <input type="text" name="reg_name" class="form-control" placeholder="Seu nome" required>
                        <i class="bi bi-person"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">E-mail</label>
                    <div class="input-wrapper">
                        <input type="email" name="reg_email" class="form-control" placeholder="seu@email.com" required>
                        <i class="bi bi-envelope"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Telefone</label>
                    <div class="input-wrapper">
                        <input type="tel" name="reg_phone" class="form-control" placeholder="(00) 00000-0000">
                        <i class="bi bi-telephone"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Senha (mín. 6 caracteres)</label>
                    <div class="input-wrapper">
                        <input type="password" name="reg_password" class="form-control" placeholder="Crie uma senha" minlength="6" required>
                        <i class="bi bi-lock"></i>
                    </div>
                </div>
                <button type="submit" class="btn-login" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <span class="btn-text">Cadastrar</span>
                    <i class="bi bi-person-plus"></i>
                </button>
            </form>
            <div class="footer-text">
                <div class="register-info">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>Sua conta ficará <strong>inativa</strong> até o administrador ativá-la. Após a ativação, você terá acesso ao módulo Financeiro.</span>
                </div>
                <p>
                    <a href="#" onclick="toggleRegister(event)" style="color: var(--primary-color); text-decoration: none; font-weight: 500;">
                        Já tem conta? Faça login
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Password toggle
        function togglePassword() {
            const passwordInput = document.getElementById('passwordInput');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }

        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = this.querySelector('.btn-login');
            btn.classList.add('loading');
        });

        // Focus first input on load
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.querySelector('input[name="email"]');
            if (emailInput && !emailInput.value) {
                emailInput.focus();
            }
        });

        // Toggle between login and register
        function toggleRegister(e) {
            e.preventDefault();
            const loginCard = document.querySelector('.login-card:not(#registerCard)');
            const registerCard = document.getElementById('registerCard');
            if (registerCard.style.display === 'none') {
                loginCard.style.display = 'none';
                registerCard.style.display = '';
            } else {
                registerCard.style.display = 'none';
                loginCard.style.display = '';
            }
        }

        // Registration form submit
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const alert = document.getElementById('registerAlert');
            alert.style.display = 'none';
            const name = this.querySelector('[name="reg_name"]').value.trim();
            const email = this.querySelector('[name="reg_email"]').value.trim();
            const phone = this.querySelector('[name="reg_phone"]').value.trim();
            const password = this.querySelector('[name="reg_password"]').value;
            try {
                const res = await fetch('api/users.php', {
                    method: 'POST',
                    body: new URLSearchParams({ action: 'register', name, email, phone, password })
                });
                const data = await res.json();
                alert.style.display = 'flex';
                if (data.ok) {
                    alert.className = 'alert alert-success';
                    alert.innerHTML = '<i class="bi bi-check-circle-fill"></i><span>' + data.message + '</span>';
                    this.reset();
                } else {
                    alert.className = 'alert alert-danger';
                    alert.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i><span>' + (data.message || 'Erro ao cadastrar') + '</span>';
                }
            } catch (err) {
                alert.style.display = 'flex';
                alert.className = 'alert alert-danger';
                alert.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i><span>Erro de conexão</span>';
            }
        });
    </script>
</body>
</html>
