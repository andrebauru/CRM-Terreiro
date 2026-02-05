<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Session;
use App\Helpers\ForgeLogger;
use App\Models\Client;

class ClientController
{
    private Client $clientModel;

    public function __construct()
    {
        if (!Session::exists('user_id')) {
            if (defined('IS_API_REQUEST') && IS_API_REQUEST === true) {
                $this->jsonResponse(['message' => 'Unauthorized'], 401);
            } else {
                header('Location: ' . ROUTE_BASE . '/login');
                exit();
            }
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
     * API: Display a listing of the clients.
     */
    public function apiIndex(): void
    {
        $clients = $this->clientModel->all();
        $this->jsonResponse(['data' => $clients]);
    }

    /**
     * Show the form for creating a new client.
     */
    public function create(): void
    {
        $title = "Novo Cliente";
        $csrfToken = Session::generateCsrfToken();

        if ($this->isAjax()) {
            ob_start();
            require_once BASE_PATH . '/app/views/clients/create.php';
            echo ob_get_clean();
            return;
        }

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
            $this->respondError('Token CSRF inválido.', '/clients/create');
            return;
        }

        $data = $this->extractClientData($_POST);
        $data['created_by'] = Session::get('user_id');
        $errors = $this->validateClientData($data);

        if (!empty($errors)) {
            $this->respondError(implode('<br>', $errors), '/clients/create', $errors);
            return;
        }

        $clientId = $this->clientModel->create($data);

        if ($clientId) {
            ForgeLogger::logAction('Cliente ' . $data['name'] . ' (ID: ' . $clientId . ') criado pelo usuário ' . Session::get('user_name') . '.');
            $this->respondSuccess('Cliente criado com sucesso!', '/clients');
        } else {
            $this->respondError('Erro ao criar cliente.', '/clients/create');
        }
    }

    /**
     * API: Store a newly created client.
     */
    public function apiStore(): void
    {
        $input = $this->getJsonInput();
        
        // CSRF validation for API. You might consider a different API authentication method.
        // For now, we'll skip CSRF for simplicity in API context, or implement a token header check.
        // if (!Session::validateCsrfToken((string)($input['csrf_token'] ?? ''))) {
        //     $this->jsonResponse(['message' => 'Invalid CSRF token'], 403);
        //     return;
        // }

        $data = $this->extractClientData($input);
        $data['created_by'] = Session::get('user_id');
        $errors = $this->validateClientData($data);

        if (!empty($errors)) {
            $this->jsonResponse(['message' => 'Validation Failed', 'errors' => $errors], 422);
            return;
        }

        $clientId = $this->clientModel->create($data);

        if ($clientId) {
            ForgeLogger::logAction('Cliente ' . $data['name'] . ' (ID: ' . $clientId . ') criado pelo usuário ' . Session::get('user_name') . '.');
            $this->jsonResponse(['message' => 'Client created successfully', 'id' => $clientId], 201);
        } else {
            $this->jsonResponse(['message' => 'Error creating client'], 500);
        }
    }

    /**
     * Display the specified client.
     */
    public function show(int $id): void
    {
        $client = $this->clientModel->find($id);

        if (!$client) {
            Session::flash('error', 'Cliente não encontrado.');
            header('Location: ' . ROUTE_BASE . '/clients');
            exit();
        }

        $jobs = $this->clientModel->getJobs($id);
        $history = $this->clientModel->getHistory($id);

        $title = $client['name'];
        $breadcrumb = [
            ['label' => 'Clientes', 'url' => ROUTE_BASE . '/clients'],
            ['label' => $client['name']]
        ];

        ob_start();
        require_once BASE_PATH . '/app/views/clients/show.php';
        $content = ob_get_clean();
        require_once BASE_PATH . '/app/views/layout.php';
    }

    /**
     * API: Display the specified client.
     */
    public function apiShow(int $id): void
    {
        $client = $this->clientModel->find($id);

        if (!$client) {
            $this->jsonResponse(['message' => 'Client not found'], 404);
            return;
        }

        $this->jsonResponse(['data' => $client]);
    }

    /**
     * Show the form for editing the specified client.
     */
    public function edit(int $id): void
    {
        $client = $this->clientModel->find($id);
        if (!$client) {
            Session::flash('error', 'Cliente não encontrado.');
            header('Location: ' . ROUTE_BASE . '/clients');
            exit();
        }

        $title = "Editar Cliente: " . htmlspecialchars($client['name']);
        $csrfToken = Session::generateCsrfToken();

        if ($this->isAjax()) {
            ob_start();
            require_once BASE_PATH . '/app/views/clients/edit.php';
            echo ob_get_clean();
            return;
        }

        ob_start();
        require_once BASE_PATH . '/app/views/clients/edit.php';
        $content = ob_get_clean();
        require_once BASE_PATH . '/app/views/layout.php';
    }

    /**
     * Update the specified client in storage.
     */
    public function update(int $id): void
    {
        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            $this->respondError('Token CSRF inválido.', '/clients/' . $id . '/edit');
            return;
        }

        $data = $this->extractClientData($_POST);
        $data['updated_by'] = Session::get('user_id');
        $errors = $this->validateClientData($data);

        if (!empty($errors)) {
            $this->respondError(implode('<br>', $errors), '/clients/' . $id . '/edit', $errors);
            return;
        }

        if ($this->clientModel->update($id, $data)) {
            ForgeLogger::logAction('Cliente ' . $data['name'] . ' (ID: ' . $id . ') atualizado pelo usuário ' . Session::get('user_name') . '.');
            $this->respondSuccess('Cliente atualizado com sucesso!', '/clients');
        } else {
            $this->respondError('Erro ao atualizar cliente.', '/clients/' . $id . '/edit');
        }
    }

    /**
     * API: Update the specified client.
     */
    public function apiUpdate(int $id): void
    {
        $input = $this->getJsonInput();
        
        // CSRF validation for API
        // if (!Session::validateCsrfToken((string)($input['csrf_token'] ?? ''))) {
        //     $this->jsonResponse(['message' => 'Invalid CSRF token'], 403);
        //     return;
        // }

        $data = $this->extractClientData($input);
        $data['updated_by'] = Session::get('user_id');
        $errors = $this->validateClientData($data);

        if (!empty($errors)) {
            $this->jsonResponse(['message' => 'Validation Failed', 'errors' => $errors], 422);
            return;
        }

        if ($thisthis->clientModel->update($id, $data)) {
            ForgeLogger::logAction('Cliente ' . $data['name'] . ' (ID: ' . $id . ') atualizado pelo usuário ' . Session::get('user_name') . '.');
            $this->jsonResponse(['message' => 'Client updated successfully'], 200);
        } else {
            $this->jsonResponse(['message' => 'Error updating client'], 500);
        }
    }

    /**
     * Remove the specified client from storage.
     */
    public function destroy(int $id): void
    {
        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            Session::flash('error', 'Token CSRF inválido.');
            header('Location: ' . ROUTE_BASE . '/clients');
            exit();
        }

        if ($this->clientModel->delete($id)) {
            ForgeLogger::logAction('Cliente (ID: ' . $id . ') excluído pelo usuário ' . Session::get('user_name') . '.');
            Session::flash('success', 'Cliente excluído com sucesso!');
        } else {
            Session::flash('error', 'Erro ao excluir cliente.');
        }
        header('Location: ' . ROUTE_BASE . '/clients');
        exit();
    }

    /**
     * API: Remove the specified client from storage.
     */
    public function apiDestroy(int $id): void
    {
        // CSRF validation for API
        // if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
        //     $this->jsonResponse(['message' => 'Invalid CSRF token'], 403);
        //     return;
        // }

        if ($this->clientModel->delete($id)) {
            ForgeLogger::logAction('Cliente (ID: ' . $id . ') excluído pelo usuário ' . Session::get('user_name') . '.');
            $this->jsonResponse(['message' => 'Client deleted successfully'], 200);
        } else {
            $this->jsonResponse(['message' => 'Error deleting client'], 500);
        }
    }

    /**
     * Extract client data from POST request (or JSON input).
     */
    private function extractClientData(array $input): array
    {
        return [
            'name' => trim($input['name'] ?? ''),
            'email' => trim($input['email'] ?? '') ?: null,
            'phone' => trim($input['phone'] ?? '') ?: null,
            'phone_secondary' => trim($input['phone_secondary'] ?? '') ?: null,
            'whatsapp' => trim($input['whatsapp'] ?? '') ?: null,
            'address' => trim($input['address'] ?? '') ?: null,
            'city' => trim($input['city'] ?? '') ?: null,
            'state' => trim($input['state'] ?? '') ?: null,
            'zip_code' => trim($input['zip_code'] ?? '') ?: null,
            'document' => trim($input['document'] ?? '') ?: null,
            'birth_date' => trim($input['birth_date'] ?? '') ?: null,
            'source' => trim($input['source'] ?? '') ?: null,
            'notes' => trim($input['notes'] ?? '') ?: null,
            'status' => trim($input['status'] ?? 'active'),
        ];
    }

    /**
     * Validate client data.
     */
    private function validateClientData(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = 'O nome do cliente é obrigatório.';
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'O e-mail fornecido não é válido.';
        }

        return $errors;
    }

    /**
     * Respond with success message (web or API).
     */
    private function respondSuccess(string $message, string $redirect = ''): void
    {
        if (defined('IS_API_REQUEST') && IS_API_REQUEST === true) {
            $this->jsonResponse(['message' => $message], 200);
        } else if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => $message]);
            exit();
        }
        Session::flash('success', $message);
        if (!empty($redirect)) {
            header('Location: ' . $redirect);
        }
        exit();
    }

    /**
     * Respond with error message (web or API).
     */
    private function respondError(string $message, string $redirect = '', array $errors = []): void
    {
        if (defined('IS_API_REQUEST') && IS_API_REQUEST === true) {
            $this->jsonResponse(['message' => $message, 'errors' => $errors ?: [$message]], 400);
        } else if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'errors' => $errors ?: [$message]]);
            exit();
        }
        Session::flash('error', $message);
        if (!empty($redirect)) {
            header('Location: ' . $redirect);
        }
        exit();
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

    /**
     * Check if the request is an AJAX request.
     */
    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
