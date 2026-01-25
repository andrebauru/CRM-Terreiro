<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Helpers\Session;

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
     * Handles the login attempt.
     */
    public function login(): void
    {
        // Check CSRF token
        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken((string)$_POST['csrf_token'])) {
            Session::flash('error', 'Token CSRF inválido. Tente novamente.');
            header('Location: /login');
            exit();
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            Session::flash('error', 'Por favor, preencha todos os campos.');
            header('Location: /login');
            exit();
        }

        $user = $this->userModel->findByEmail($email);

        if ($user && $this->userModel->verifyPassword($password, $user['password'])) {
            // Authentication successful
            Session::set('user_id', $user['id']);
            Session::set('user_name', $user['name']);
            Session::set('user_role', $user['role']);
            Session::flash('success', 'Login realizado com sucesso!');
            header('Location: /dashboard'); // Redirect to dashboard or intended page
            exit();
        } else {
            // Authentication failed
            Session::flash('error', 'Email ou senha inválidos.');
            header('Location: /login');
            exit();
        }
    }

    /**
     * Handles user logout.
     */
    public function logout(): void
    {
        Session::destroy();
        Session::flash('success', 'Você foi desconectado com sucesso.');
        header('Location: /login');
        exit();
    }
}
