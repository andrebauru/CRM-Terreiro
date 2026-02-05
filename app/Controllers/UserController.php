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
            header('Location: ' . ROUTE_BASE . '/login');
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
                header('Location: ' . ROUTE_BASE . '/dashboard');
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

        if ($this->isAjax()) {
            ob_start();
            require_once BASE_PATH . '/app/views/users/create.php';
            echo ob_get_clean();
            return;
        }

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
            $this->handleError('Token CSRF inválido.');
        }

        $errors = $this->validateUserData($_POST);
        if (!empty($errors)) {
            $this->handleError(implode('<br>', $errors));
        }

        $data = $this->prepareUserData($_POST);
        $userId = $this->userModel->create($data);

        if ($userId) {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Usuário criado com sucesso!']);
                exit();
            }
            Session::flash('success', 'Usuário criado com sucesso!');
            header('Location: ' . ROUTE_BASE . '/users');
            exit();
        } else {
            $this->handleError('Erro ao criar usuário.');
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
            header('Location: ' . ROUTE_BASE . '/dashboard');
            exit();
        }

        $user = $this->userModel->findById($id);

        if (!$user) {
            Session::flash('error', 'Usuário não encontrado.');
            header('Location: ' . ROUTE_BASE . ($isAdmin ? '/users' : '/dashboard'));
            exit();
        }

        $title = $isAdmin ? "Editar Usuário: " . htmlspecialchars($user['name']) : "Editar Perfil";
        $csrfToken = Session::generateCsrfToken();

        if ($this->isAjax()) {
            ob_start();
            require_once BASE_PATH . '/app/views/users/edit.php';
            echo ob_get_clean();
            return;
        }

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
            $this->handleError('Você não tem permissão para acessar esta área.', $id, $isAdmin);
        }

        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            $this->handleError('Token CSRF inválido.', $id, $isAdmin);
        }

        $user = $this->userModel->findById($id);
        if (!$user) {
            $this->handleError('Usuário não encontrado.', $id, $isAdmin);
        }

        $errors = $this->validateUserData($_POST, $id);
        if (!empty($errors)) {
            $this->handleError(implode('<br>', $errors), $id, $isAdmin);
        }

        $data = $this->prepareUserData($_POST, $isAdmin);

        if ($this->userModel->update($id, $data)) {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $isAdmin ? 'Usuário atualizado com sucesso!' : 'Perfil atualizado com sucesso!']);
                exit();
            }
            Session::flash('success', $isAdmin ? 'Usuário atualizado com sucesso!' : 'Perfil atualizado com sucesso!');
            header('Location: ' . ROUTE_BASE . ($isAdmin ? '/users' : '/dashboard'));
            exit();
        } else {
            $this->handleError($isAdmin ? 'Erro ao atualizar usuário.' : 'Erro ao atualizar perfil.', $id, $isAdmin);
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
            header('Location: ' . ROUTE_BASE . '/users');
            exit();
        }

        // Prevent admin from deleting themselves
        if ($id === Session::get('user_id')) {
            Session::flash('error', 'Você não pode excluir seu próprio usuário.');
            header('Location: ' . ROUTE_BASE . '/users');
            exit();
        }

        if ($this->userModel->delete($id)) {
            Session::flash('success', 'Usuário excluído com sucesso!');
        } else {
            Session::flash('error', 'Erro ao excluir usuário.');
        }
        header('Location: ' . ROUTE_BASE . '/users');
        exit();
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function validateUserData(array $post, ?int $userId = null): array
    {
        $errors = [];
        $name = trim($post['name'] ?? '');
        $email = trim($post['email'] ?? '');
        $password = $post['password'] ?? '';

        if (empty($name)) {
            $errors[] = 'O nome é obrigatório.';
        }
        if (empty($email)) {
            $errors[] = 'O e-mail é obrigatório.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'O e-mail fornecido não é válido.';
        } else {
            $existingUser = $this->userModel->findByEmail($email);
            if ($existingUser && $existingUser['id'] !== $userId) {
                $errors[] = 'Este e-mail já está em uso.';
            }
        }

        if ($userId === null) { // Create mode
            if (empty($password)) {
                $errors[] = 'A senha é obrigatória.';
            } elseif (strlen($password) < 6) {
                $errors[] = 'A senha deve ter no mínimo 6 caracteres.';
            }
        } else { // Edit mode
            if (!empty($password) && strlen($password) < 6) {
                $errors[] = 'A nova senha deve ter no mínimo 6 caracteres.';
            }
        }

        return $errors;
    }

    private function prepareUserData(array $post, bool $isAdmin = false): array
    {
        $data = [
            'name' => trim($post['name'] ?? ''),
            'email' => trim($post['email'] ?? ''),
            'role' => $isAdmin ? ($post['role'] ?? 'staff') : (string) Session::get('user_role'),
        ];
        if (!empty($post['password'])) {
            $data['password'] = $post['password'];
        }
        return $data;
    }

    private function handleError(string $message, ?int $userId = null, bool $isAdmin = false): void
    {
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            http_response_code(422);
            echo json_encode(['success' => false, 'errors' => [$message]]);
            exit();
        }
        Session::flash('error', $message);
        if ($userId) {
            $location = ROUTE_BASE . '/users/' . $userId . '/edit';
        } else {
            $location = ROUTE_BASE . '/users/create';
        }
        header('Location: ' . $location);
        exit();
    }
}
