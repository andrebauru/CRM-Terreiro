<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Session;
use App\Helpers\ForgeLogger;
use App\Models\Client;

class ClientController extends BaseController
{
    private Client $clientModel;

    public function __construct()
    {
        $this->clientModel = new Client();
    }

    /**
     * Display a listing of the clients.
     */
    public function index(): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }

        $clients = $this->clientModel->all();
        $this->render('clients/index', [
            'title' => "Clientes",
            'clients' => $clients,
            'breadcrumb' => [
                ['label' => 'Clientes']
            ]
        ]);
    }

    /**
     * API: Display a listing of the clients.
     */
    public function apiIndex(): void
    {
        if (!Session::exists('user_id')) { // API precisa de autenticação também
            $this->json(['message' => 'Unauthorized'], 401);
        }
        $clients = $this->clientModel->all();
        $this->json(['data' => $clients]);
    }

    /**
     * Show the form for creating a new client.
     */
    public function create(): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }

        $data = [
            'title' => "Novo Cliente",
            'csrfToken' => Session::generateCsrfToken()
        ];

        if ($this->isAjax()) {
            $this->render('clients/create', $data);
            return;
        }

        $this->render('clients/create', $data);
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
        if (!Session::exists('user_id')) {
            $this->json(['message' => 'Unauthorized'], 401);
        }

        $input = $this->getJsonInput();
        
        // CSRF validation for API. For simplicity, keeping it here, but generally
        // API authentication (e.g., Bearer Token) replaces CSRF for APIs.
        // if (!Session::validateCsrfToken((string)($input['csrf_token'] ?? ''))) {
        //     $this->json(['message' => 'Invalid CSRF token'], 403);
        //     return;
        // }

        $data = $this->extractClientData($input);
        $data['created_by'] = Session::get('user_id');
        $errors = $this->validateClientData($data);

        if (!empty($errors)) {
            $this->json(['message' => 'Validation Failed', 'errors' => $errors], 422);
            return;
        }

        $clientId = $this->clientModel->create($data);

        if ($clientId) {
            ForgeLogger::logAction('Cliente ' . $data['name'] . ' (ID: ' . $clientId . ') criado pelo usuário ' . Session::get('user_name') . '.');
            $this->json(['message' => 'Client created successfully', 'id' => $clientId], 201);
        } else {
            $this->json(['message' => 'Error creating client'], 500);
        }
    }

    /**
     * Display the specified client.
     */
    public function show(int $id): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }

        $client = $this->clientModel->find($id);

        if (!$client) {
            Session::flash('error', 'Cliente não encontrado.');
            $this->redirect('clients');
            return;
        }

        $jobs = $this->clientModel->getJobs($id);
        $history = $this->clientModel->getHistory($id);

        $this->render('clients/show', [
            'title' => $client['name'],
            'client' => $client,
            'jobs' => $jobs,
            'history' => $history,
            'breadcrumb' => [
                ['label' => 'Clientes', 'url' => ROUTE_BASE . '/clients'],
                ['label' => $client['name']]
            ]
        ]);
    }

    /**
     * API: Display the specified client.
     */
    public function apiShow(int $id): void
    {
        if (!Session::exists('user_id')) {
            $this->json(['message' => 'Unauthorized'], 401);
        }

        $client = $this->clientModel->find($id);

        if (!$client) {
            $this->json(['message' => 'Client not found'], 404);
            return;
        }

        $this->json(['data' => $client]);
    }

    /**
     * Show the form for editing the specified client.
     */
    public function edit(int $id): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }

        $client = $this->clientModel->find($id);
        if (!$client) {
            Session::flash('error', 'Cliente não encontrado.');
            $this->redirect('clients');
            return;
        }

        $data = [
            'title' => "Editar Cliente: " . htmlspecialchars($client['name']),
            'client' => $client,
            'csrfToken' => Session::generateCsrfToken()
        ];

        if ($this->isAjax()) {
            $this->render('clients/edit', $data);
            return;
        }

        $this->render('clients/edit', $data);
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
        if (!Session::exists('user_id')) {
            $this->json(['message' => 'Unauthorized'], 401);
        }

        $input = $this->getJsonInput();
        
        // CSRF validation for API
        // if (!Session::validateCsrfToken((string)($input['csrf_token'] ?? ''))) {
        //     $this->json(['message' => 'Invalid CSRF token'], 403);
        //     return;
        // }

        $data = $this->extractClientData($input);
        $data['updated_by'] = Session::get('user_id');
        $errors = $this->validateClientData($data);

        if (!empty($errors)) {
            $this->json(['message' => 'Validation Failed', 'errors' => $errors], 422);
            return;
        }

        if ($this->clientModel->update($id, $data)) {
            ForgeLogger::logAction('Cliente ' . $data['name'] . ' (ID: ' . $id . ') atualizado pelo usuário ' . Session::get('user_name') . '.');
            $this->json(['message' => 'Client updated successfully'], 200);
        } else {
            $this->json(['message' => 'Error updating client'], 500);
        }
    }

    /**
     * Remove the specified client from storage.
     */
    public function destroy(int $id): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }

        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            Session::flash('error', 'Token CSRF inválido.');
            $this->redirect('clients');
            return;
        }

        if ($this->clientModel->delete($id)) {
            ForgeLogger::logAction('Cliente (ID: ' . $id . ') excluído pelo usuário ' . Session::get('user_name') . '.');
            Session::flash('success', 'Cliente excluído com sucesso!');
        } else {
            Session::flash('error', 'Erro ao excluir cliente.');
        }
        $this->redirect('clients');
    }

    /**
     * API: Remove the specified client from storage.
     */
    public function apiDestroy(int $id): void
    {
        if (!Session::exists('user_id')) {
            $this->json(['message' => 'Unauthorized'], 401);
        }
    {
        // CSRF validation for API
        // if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
        //     $this->jsonResponse(['message' => 'Invalid CSRF token'], 403);
        //     return;
        // }

        if ($this->clientModel->delete($id)) {
            ForgeLogger::logAction('Cliente (ID: ' . $id . ') excluído pelo usuário ' . Session::get('user_name') . '.');
            $this->json(['message' => 'Client deleted successfully'], 200);
        } else {
            $this->json(['message' => 'Error deleting client'], 500);
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
        if ($this->isAjax() || (defined('IS_API_REQUEST') && IS_API_REQUEST === true)) {
            $this->json(['success' => true, 'message' => $message]);
        }
        Session::flash('success', $message);
        if (!empty($redirect)) {
            $this->redirect($redirect);
        }
        exit(); // Ensure script terminates after redirect or JSON response
    }

    /**
     * Respond with error message (web or API).
     */
    private function respondError(string $message, string $redirect = '', array $errors = []): void
    {
        if ($this->isAjax() || (defined('IS_API_REQUEST') && IS_API_REQUEST === true)) {
            $this->json(['success' => false, 'message' => $message, 'errors' => $errors ?: [$message]], 400);
        }
        Session::flash('error', $message);
        if (!empty($redirect)) {
            $this->redirect($redirect);
        }
        exit(); // Ensure script terminates after redirect or JSON response
    }



    /**
     * Get JSON input from the request body.
     * Moved from BaseController to allow for specific override if needed.
     */
    private function getJsonInput(): array
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Log the error
            ForgeLogger::error("Invalid JSON input: " . $input);
            $this->json(['message' => 'Invalid JSON input'], 400);
        }
        return $data ?? [];
    }

    /**
     * Check if the request is an AJAX request.
     * Moved from BaseController to allow for specific override if needed.
     */
    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
