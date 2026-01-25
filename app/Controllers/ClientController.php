<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Session;
use App\Helpers\ForgeLogger; // Adicionado para logging
use App\Models\Client;

class ClientController
{
    private Client $clientModel;

    public function __construct()
    {
        // Redirect to login if not authenticated
        if (!Session::exists('user_id')) {
            header('Location: /login');
            exit();
        }
        $this->clientModel = new Client();
    }

    /**
     * Display a listing of the clients.
     */
    public function index(): void
    {
        $clients = $this->clientModel->all();
        $title = "Clientes";
        ob_start();
        require_once BASE_PATH . '/app/views/clients/index.php';
        $content = ob_get_clean();
        require_once BASE_PATH . '/app/views/layout.php';
    }

    /**
     * Show the form for creating a new client.
     */
    public function create(): void
    {
        $title = "Novo Cliente";
        $csrfToken = Session::generateCsrfToken();
        ob_start();
        require_once BASE_PATH . '/app/views/clients/create.php';
        $content = ob_get_clean();
        require_once BASE_PATH . '/app/views/layout.php';
    }

    /**
     * Store a newly created client in storage.
     */
    public function store(): void
    {
        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            Session::flash('error', 'Token CSRF inválido.');
            header('Location: /clients/create');
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($name)) {
            Session::flash('error', 'O nome do cliente é obrigatório.');
            header('Location: /clients/create');
            exit();
        }

        // Basic email validation
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'O e-mail fornecido não é válido.');
            header('Location: /clients/create');
            exit();
        }

        $data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
        ];

        $clientId = $this->clientModel->create($data);

        if ($clientId) {
            ForgeLogger::logAction('Cliente ' . $name . ' (ID: ' . $clientId . ') criado pelo usuário ' . Session::get('user_name') . '.'); // Log action
            Session::flash('success', 'Cliente criado com sucesso!');
            header('Location: /clients/' . $clientId);
            exit();
        } else {
            Session::flash('error', 'Erro ao criar cliente.');
            header('Location: /clients/create');
            exit();
        }
    }

    /**
     * Display the specified client.
     *
     * @param int $id
     */
    public function show(int $id): void
    {
        $client = $this->clientModel->find($id);

        if (!$client) {
            Session::flash('error', 'Cliente não encontrado.');
            header('Location: /clients');
            exit();
        }

        $title = "Detalhes do Cliente: " . $client['name'];
        ob_start();
        require_once BASE_PATH . '/app/views/clients/show.php';
        $content = ob_get_clean();
        require_once BASE_PATH . '/app/views/layout.php';
    }

    /**
     * Show the form for editing the specified client.
     *
     * @param int $id
     */
    public function edit(int $id): void
    {
        $client = $this->clientModel->find($id);

        if (!$client) {
            Session::flash('error', 'Cliente não encontrado.');
            header('Location: /clients');
            exit();
        }

        $title = "Editar Cliente: " . $client['name'];
        $csrfToken = Session::generateCsrfToken();
        ob_start();
        require_once BASE_PATH . '/app/views/clients/edit.php';
        $content = ob_get_clean();
        require_once BASE_PATH . '/app/views/layout.php';
    }

    /**
     * Update the specified client in storage.
     *
     * @param int $id
     */
    public function update(int $id): void
    {
        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            Session::flash('error', 'Token CSRF inválido.');
            header('Location: /clients/' . $id . '/edit');
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($name)) {
            Session::flash('error', 'O nome do cliente é obrigatório.');
            header('Location: /clients/' . $id . '/edit');
            exit();
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'O e-mail fornecido não é válido.');
            header('Location: /clients/' . $id . '/edit');
            exit();
        }

        $data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
        ];

        if ($this->clientModel->update($id, $data)) {
            ForgeLogger::logAction('Cliente ' . $name . ' (ID: ' . $id . ') atualizado pelo usuário ' . Session::get('user_name') . '.'); // Log action
            Session::flash('success', 'Cliente atualizado com sucesso!');
            header('Location: /clients/' . $id);
            exit();
        } else {
            Session::flash('error', 'Erro ao atualizar cliente.');
            header('Location: /clients/' . $id . '/edit');
            exit();
        }
    }

    /**
     * Remove the specified client from storage.
     *
     * @param int $id
     */
    public function destroy(int $id): void
    {
        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            Session::flash('error', 'Token CSRF inválido.');
            header('Location: /clients');
            exit();
        }

        if ($this->clientModel->delete($id)) {
            ForgeLogger::logAction('Cliente (ID: ' . $id . ') excluído pelo usuário ' . Session::get('user_name') . '.'); // Log action
            Session::flash('success', 'Cliente excluído com sucesso!');
        } else {
            Session::flash('error', 'Erro ao excluir cliente.');
        }
        header('Location: /clients');
        exit();
    }
}
