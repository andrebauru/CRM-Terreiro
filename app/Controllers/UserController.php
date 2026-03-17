<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;

use App\Helpers\Session;
use App\Models\User;

class UserController extends BaseController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Display a listing of the users.
     */
    public function index(): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }
        $this->authorize('admin'); // Apenas administradores podem ver a lista de usuários.

        $users = $this->userModel->all();
        $this->render('users/index', [
            'title' => "Gerenciar Usuários",
            'users' => $users,
            'breadcrumb' => [
                ['label' => 'Usuários']
            ]
        ]);
    }

    /**
     * Display the specified user.
     *
     * @param int $id
     */
    public function show(int $id): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }

        $isAdmin = Session::get('user_role') === 'admin';
        if (!$isAdmin && $id !== (int) Session::get('user_id')) {
            Session::flash('error', 'Você não tem permissão para acessar esta área.');
            $this->redirect('dashboard');
        }

        $user = $this->userModel->findById($id);

        if (!$user) {
            Session::flash('error', 'Usuário não encontrado.');
            $this->redirect($isAdmin ? 'users' : 'dashboard');
        }

        $this->render('users/show', [
            'title' => htmlspecialchars($user['name']),
            'user' => $user,
            'isAdmin' => $isAdmin,
            'breadcrumb' => [
                ['label' => 'Usuários', 'url' => ROUTE_BASE . '/users'],
                ['label' => $user['name']]
            ]
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }
        $this->authorize('admin'); // Apenas administradores podem criar usuários.

        $data = [
            'title' => "Novo Usuário",
            'csrfToken' => Session::generateCsrfToken()
        ];

        if ($this->isAjax()) {
            $this->render('users/create', $data);
            return;
        }

        $this->render('users/create', $data);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }
        $this->authorize('admin'); // Apenas administradores podem criar usuários.

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
                $this->json(['success' => true, 'message' => 'Usuário criado com sucesso!']);
            }
            Session::flash('success', 'Usuário criado com sucesso!');
            $this->redirect('users');
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
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }

        $isAdmin = Session::get('user_role') === 'admin';
        if (!$isAdmin && $id !== (int) Session::get('user_id')) {
            Session::flash('error', 'Você não tem permissão para acessar esta área.');
            $this->redirect('dashboard');
        }

        $user = $this->userModel->findById($id);

        if (!$user) {
            Session::flash('error', 'Usuário não encontrado.');
            $this->redirect($isAdmin ? 'users' : 'dashboard');
        }

        $this->render('users/edit', [
            'title' => $isAdmin ? "Editar Usuário: " . htmlspecialchars($user['name']) : "Editar Perfil",
            'user' => $user,
            'csrfToken' => Session::generateCsrfToken(),
            'isAdmin' => $isAdmin,
            'breadcrumb' => [
                ['label' => 'Usuários', 'url' => ROUTE_BASE . '/users'],
                ['label' => $user['name']]
            ]
        ]);
    }

    /**
     * Update the specified user in storage.
     *
     * @param int $id
     */
    public function update(int $id): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }

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
                $this->json(['success' => true, 'message' => $isAdmin ? 'Usuário atualizado com sucesso!' : 'Perfil atualizado com sucesso!']);
            }
            Session::flash('success', $isAdmin ? 'Usuário atualizado com sucesso!' : 'Perfil atualizado com sucesso!');
            $this->redirect($isAdmin ? 'users' : 'dashboard');
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
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }
        $this->authorize('admin'); // Apenas administradores podem excluir usuários.

        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            Session::flash('error', 'Token CSRF inválido.');
            $this->redirect('users');
        }

        // Prevent admin from deleting themselves
        if ($id === (int)Session::get('user_id')) { // Cast para int para comparação estrita
            Session::flash('error', 'Você não pode excluir seu próprio usuário.');
            $this->redirect('users');
        }

        if ($this->userModel->delete($id)) {
            Session::flash('success', 'Usuário excluído com sucesso!');
        } else {
            Session::flash('error', 'Erro ao excluir usuário.');
        }
        $this->redirect('users');
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
            $this->json(['success' => false, 'errors' => [$message]], 422);
        }
        Session::flash('error', $message);
        if ($userId) {
            $location = 'users/' . $userId . '/edit';
        } else {
            $location = 'users/create';
        }
        $this->redirect($location);
    }
}
