<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Helpers\Session;
use App\Helpers\ForgeLogger; // Adicionado para logging

class AuthController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
        // Ensure session is started for CSRF and flash messages
        Session::init();
    }

    /**
     * Displays the login form.
     */
    public function showLoginForm(): void
    {
        // Generate CSRF token for the form
        $csrfToken = Session::generateCsrfToken();
        // Render the login view
        require_once BASE_PATH . '/app/views/auth/login.php';
    }

    /**
     * Handles the login attempt (web form).
     */
    public function login(): void
    {
        // Check CSRF token
        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken((string)$_POST['csrf_token'])) {
            Session::flash('error', 'Token CSRF inválido. Tente novamente.');
            header('Location: ' . ROUTE_BASE . '/login');
            exit();
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
        $input = $this->getJsonInput();
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
                $this->jsonResponse(['message' => 'Por favor, preencha todos os campos.'], 400);
            } else {
                Session::flash('error', 'Por favor, preencha todos os campos.');
                header('Location: ' . ROUTE_BASE . '/login');
            }
            exit();
        }

        $user = $this->userModel->findByEmail($email);

        if ($user && $this->userModel->verifyPassword($password, $user['password'])) {
            // Authentication successful
            session_regenerate_id(true);
            Session::set('user_id', $user['id']);
            Session::set('user_name', $user['name']);
            Session::set('user_role', $user['role']);
            ForgeLogger::logAction('Usuário ' . $user['email'] . ' fez login.'); // Log login

            if ($isApi) {
                $this->jsonResponse(['message' => 'Login realizado com sucesso!', 'user' => ['id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']]], 200);
            } else {
                Session::flash('success', 'Login realizado com sucesso!');
                header('Location: ' . ROUTE_BASE . '/dashboard'); // Redirect to dashboard or intended page
            }
            exit();
        } else {
            // Authentication failed
            if ($isApi) {
                $this->jsonResponse(['message' => 'Email ou senha inválidos.'], 401);
            } else {
                Session::flash('error', 'Email ou senha inválidos.');
                header('Location: ' . ROUTE_BASE . '/login');
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
        header('Location: ' . ROUTE_BASE . '/login');
        exit();
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
            $this->jsonResponse(['message' => 'Desconectado com sucesso.'], 200);
        } else {
            $this->jsonResponse(['message' => 'Nenhum usuário autenticado.'], 401);
        }
    }

    /**
     * Respond with JSON data and exit.
     */
    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    /**
     * Get JSON input from the request body.
     */
    private function getJsonInput(): array
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->jsonResponse(['message' => 'Invalid JSON input'], 400);
        }
        return $data ?? [];
    }
}

