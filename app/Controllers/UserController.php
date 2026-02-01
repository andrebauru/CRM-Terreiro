<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Session;
use App\Models\User;

class UserController
{
    private User $userModel;

    public function __construct()
    {
        // Redirect to login if not authenticated
        if (!Session::exists('user_id')) {
            header('Location: /login');
            exit();
        }
        // Only admin can access user management (non-admin can only edit/update own profile)
        if (Session::get('user_role') !== 'admin') {
            $requestPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
            $requestMethod = $_SERVER['REQUEST_METHOD'];
            if (isset($_POST['_method'])) {
                $requestMethod = strtoupper($_POST['_method']);
            }

            $sessionUserId = (int) Session::get('user_id');
            $isSelfEdit = false;
            $isSelfUpdate = false;

            if ($requestMethod === 'GET' && preg_match('#^users/(\d+)/edit$#', $requestPath, $matches)) {
                $isSelfEdit = ((int) $matches[1] === $sessionUserId);
            }
            if ($requestMethod === 'PUT' && preg_match('#^users/(\d+)$#', $requestPath, $matches)) {
                $isSelfUpdate = ((int) $matches[1] === $sessionUserId);
            }

            if (!$isSelfEdit && !$isSelfUpdate) {
                Session::flash('error', 'Você não tem permissão para acessar esta área.');
                header('Location: /dashboard');
                exit();
            }
        }
        $this->userModel = new User();
    }

    /**
     * Display a listing of the users.
     */
    public function index(): void
    {
        $users = $this->userModel->all();
        $title = "Gerenciar Usuários";
        ob_start();
        require_once BASE_PATH . '/app/views/users/index.php';
        $content = ob_get_clean();
        require_once BASE_PATH . '/app/views/layout.php';
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): void
    {
        $title = "Novo Usuário";
        $csrfToken = Session::generateCsrfToken();
        ob_start();
        require_once BASE_PATH . '/app/views/users/create.php';
        $content = ob_get_clean();
        require_once BASE_PATH . '/app/views/layout.php';
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(): void
    {
        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            Session::flash('error', 'Token CSRF inválido.');
            header('Location: /users/create');
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'staff';

        if (empty($name) || empty($email) || empty($password)) {
            Session::flash('error', 'Por favor, preencha todos os campos obrigatórios (Nome, E-mail, Senha).');
            header('Location: /users/create');
            exit();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'O e-mail fornecido não é válido.');
            header('Location: /users/create');
            exit();
        }
        if (strlen($password) < 6) {
            Session::flash('error', 'A senha deve ter no mínimo 6 caracteres.');
            header('Location: /users/create');
            exit();
        }
        // Check if email already exists
        if ($this->userModel->findByEmail($email)) {
            Session::flash('error', 'Este e-mail já está em uso.');
            header('Location: /users/create');
            exit();
        }

        $data = [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
        ];

        $userId = $this->userModel->create($data);

        if ($userId) {
            Session::flash('success', 'Usuário criado com sucesso!');
            header('Location: /users');
            exit();
        } else {
            Session::flash('error', 'Erro ao criar usuário.');
            header('Location: /users/create');
            exit();
        }
    }

    /**
     * Show the form for editing the specified user.
     *
     * @param int $id
     */
    public function edit(int $id): void
    {
        $isAdmin = Session::get('user_role') === 'admin';
        if (!$isAdmin && $id !== (int) Session::get('user_id')) {
            Session::flash('error', 'Você não tem permissão para acessar esta área.');
            header('Location: /dashboard');
            exit();
        }

        $user = $this->userModel->findById($id);

        if (!$user) {
            Session::flash('error', 'Usuário não encontrado.');
            header('Location: ' . ($isAdmin ? '/users' : '/dashboard'));
            exit();
        }

        $title = $isAdmin ? "Editar Usuário: " . $user['name'] : "Editar Perfil";
        $csrfToken = Session::generateCsrfToken();
        ob_start();
        require_once BASE_PATH . '/app/views/users/edit.php';
        $content = ob_get_clean();
        require_once BASE_PATH . '/app/views/layout.php';
    }

    /**
     * Update the specified user in storage.
     *
     * @param int $id
     */
    public function update(int $id): void
    {
        $isAdmin = Session::get('user_role') === 'admin';
        if (!$isAdmin && $id !== (int) Session::get('user_id')) {
            Session::flash('error', 'Você não tem permissão para acessar esta área.');
            header('Location: /dashboard');
            exit();
        }

        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            Session::flash('error', 'Token CSRF inválido.');
            header('Location: ' . ($isAdmin ? '/users/' . $id . '/edit' : '/users/' . $id . '/edit'));
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $isAdmin ? ($_POST['role'] ?? 'staff') : (string) Session::get('user_role');

        if (empty($name) || empty($email)) {
            Session::flash('error', 'Por favor, preencha todos os campos obrigatórios (Nome, E-mail).');
            header('Location: /users/' . $id . '/edit');
            exit();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'O e-mail fornecido não é válido.');
            header('Location: /users/' . $id . '/edit');
            exit();
        }
        if (!empty($password) && strlen($password) < 6) {
            Session::flash('error', 'A senha deve ter no mínimo 6 caracteres.');
            header('Location: /users/' . $id . '/edit');
            exit();
        }

        $data = [
            'name' => $name,
            'email' => $email,
            'role' => $role,
        ];
        if (!empty($password)) {
            $data['password'] = $password;
        }

        if ($this->userModel->update($id, $data)) {
            Session::flash('success', $isAdmin ? 'Usuário atualizado com sucesso!' : 'Perfil atualizado com sucesso!');
            header('Location: ' . ($isAdmin ? '/users' : '/dashboard'));
            exit();
        } else {
            Session::flash('error', $isAdmin ? 'Erro ao atualizar usuário.' : 'Erro ao atualizar perfil.');
            header('Location: ' . ($isAdmin ? '/users/' . $id . '/edit' : '/users/' . $id . '/edit'));
            exit();
        }
    }

    /**
     * Remove the specified user from storage.
     *
     * @param int $id
     */
    public function destroy(int $id): void
    {
        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            Session::flash('error', 'Token CSRF inválido.');
            header('Location: /users');
            exit();
        }

        // Prevent admin from deleting themselves
        if ($id === Session::get('user_id')) {
            Session::flash('error', 'Você não pode excluir seu próprio usuário.');
            header('Location: /users');
            exit();
        }

        if ($this->userModel->delete($id)) {
            Session::flash('success', 'Usuário excluído com sucesso!');
        } else {
            Session::flash('error', 'Erro ao excluir usuário.');
        }
        header('Location: /users');
        exit();
    }
}
