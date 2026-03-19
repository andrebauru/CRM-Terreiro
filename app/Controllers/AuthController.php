<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;

use App\Models\User;
use App\Helpers\Session;
use App\Helpers\ForgeLogger; // Adicionado para logging

class AuthController extends BaseController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Displays the login form.
     */
    public function showLoginForm(): void
    {
        // Generate CSRF token for the form
        $csrfToken = Session::generateCsrfToken();
        // Renderiza diretamente sem layout.php (login.php tem HTML completo próprio)
        $this->renderRaw('auth/login', [
            'csrfToken' => $csrfToken,
            'title' => 'Login'
        ]);
    }

    /**
     * Handles the login attempt (web form).
     */
    public function login(): void
    {
        // Check CSRF token
        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken((string)$_POST['csrf_token'])) {
            Session::flash('error', 'Token CSRF inválido. Tente novamente.');
            $this->redirect('login');
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $this->authenticate($email, $password);
    }

    /**
     * Handles the login attempt (API).
     */
    public function apiLogin(): void
    {
        $input = $this->getJsonInput(); // Usar o getJsonInput do BaseController
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        $this->authenticate($email, $password, true);
    }

    /**
     * Handles user authentication logic.
     */
    private function authenticate(string $email, string $password, bool $isApi = false): void
    {
        if (empty($email) || empty($password)) {
            if ($isApi) {
                $this->json(['message' => 'Por favor, preencha todos os campos.'], 400);
            } else {
                Session::flash('error', 'Por favor, preencha todos os campos.');
                $this->redirect('login');
            }
            exit();
        }

        $user = $this->userModel->findByEmail($email);

        if ($user && $this->userModel->verifyPassword($password, $user['password'])) {
            // Check if user is active
            if (isset($user['is_active']) && (int)$user['is_active'] === 0) {
                if ($isApi) {
                    $this->json(['message' => 'Sua conta está inativa. Aguarde a ativação pelo administrador.'], 403);
                } else {
                    Session::flash('error', 'Sua conta está inativa. Aguarde a ativação pelo administrador.');
                    $this->redirect('login');
                }
                exit();
            }

            // Authentication successful
            session_regenerate_id(true);
            Session::set('user_id', $user['id']);
            Session::set('user_name', $user['name']);
            Session::set('user_role', $user['role']);
            ForgeLogger::logAction('Usuário ' . $user['email'] . ' fez login.'); // Log login

            if ($isApi) {
                $this->json(['message' => 'Login realizado com sucesso!', 'user' => ['id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']]], 200);
            } else {
                Session::flash('success', 'Login realizado com sucesso!');
                $this->redirect('dashboard.php'); // Redireciona para o dashboard legacy
            }
            exit();
        } else {
            // Authentication failed
            if ($isApi) {
                $this->json(['message' => 'Email ou senha inválidos.'], 401);
            } else {
                Session::flash('error', 'Email ou senha inválidos.');
                $this->redirect('login');
            }
            exit();
        }
    }

    /**
     * Handles user logout (web).
     */
    public function logout(): void
    {
        $userName = Session::get('user_name'); // Get user name before destroying session
        Session::destroy();
        ForgeLogger::logAction('Usuário ' . ($userName ?? 'Desconhecido') . ' fez logout.'); // Log logout
        Session::flash('success', 'Você foi desconectado com sucesso.');
        $this->redirect('login');
    }

    /**
     * Handles user logout (API).
     */
    public function apiLogout(): void
    {
        // For API, we just destroy the session if it exists and return success JSON
        if (Session::exists('user_id')) {
            $userName = Session::get('user_name');
            Session::destroy();
            ForgeLogger::logAction('Usuário ' . ($userName ?? 'Desconhecido') . ' fez logout via API.');
            $this->json(['message' => 'Desconectado com sucesso.'], 200);
        } else {
            $this->json(['message' => 'Nenhum usuário autenticado.'], 401);
        }
    }
}

