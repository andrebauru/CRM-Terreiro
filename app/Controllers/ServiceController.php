<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Session;
use App\Helpers\ForgeLogger; // Adicionado para logging
use App\Models\Service;
use App\Models\Setting;

class ServiceController
{
    private Service $serviceModel;

    public function __construct()
    {
        // Redirect to login if not authenticated
        if (!Session::exists('user_id')) {
            header('Location: ' . ROUTE_BASE . '/login');
            exit();
        }
        $this->serviceModel = new Service();
    }

    /**
     * Display a listing of the services.
     */
    public function index(): void
    {
        $services = $this->serviceModel->all();
        $settings = (new Setting())->get();
        $title = "Serviços";
        ob_start();
        require_once BASE_PATH . '/app/views/services/index.php';
        $content = ob_get_clean();
        require_once BASE_PATH . '/app/views/layout.php';
    }

    /**
     * Show the form for creating a new service.
     */
    public function create(): void
    {
        $title = "Novo Serviço";
        $csrfToken = Session::generateCsrfToken();

        if ($this->isAjax()) {
            ob_start();
            require_once BASE_PATH . '/app/views/services/create.php';
            echo ob_get_clean();
            return;
        }

        ob_start();
        require_once BASE_PATH . '/app/views/services/create.php';
        $content = ob_get_clean();
        require_once BASE_PATH . '/app/views/layout.php';
    }

    /**
     * Store a newly created service in storage.
     */
    public function store(): void
    {
        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            $this->handleError('Token CSRF inválido.');
        }

        $errors = $this->validateServiceData($_POST);
        if (!empty($errors)) {
            $this->handleError(implode('<br>', $errors));
        }

        $data = $this->prepareServiceData($_POST);
        $serviceId = $this->serviceModel->create($data);

        if ($serviceId) {
            ForgeLogger::logAction('Serviço "' . $data['name'] . '" (ID: ' . $serviceId . ') criado pelo usuário ' . Session::get('user_name') . '.');
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Serviço criado com sucesso!']);
                exit();
            }
            Session::flash('success', 'Serviço criado com sucesso!');
            header('Location: ' . ROUTE_BASE . '/services');
            exit();
        } else {
            $this->handleError('Erro ao criar serviço.');
        }
    }

    /**
     * Display the specified service.
     *
     * @param int $id
     */
    public function show(int $id): void
    {
        $service = $this->serviceModel->find($id);

        if (!$service) {
            Session::flash('error', 'Serviço não encontrado.');
            header('Location: ' . ROUTE_BASE . '/services');
            exit();
        }

        $settings = (new Setting())->get();
        $title = "Detalhes do Serviço: " . $service['name'];
        ob_start();
        require_once BASE_PATH . '/app/views/services/show.php';
        $content = ob_get_clean();
        require_once BASE_PATH . '/app/views/layout.php';
    }

    /**
     * Show the form for editing the specified service.
     *
     * @param int $id
     */
    public function edit(int $id): void
    {
        $service = $this->serviceModel->find($id);

        if (!$service) {
            Session::flash('error', 'Serviço não encontrado.');
            header('Location: ' . ROUTE_BASE . '/services');
            exit();
        }

        $title = "Editar Serviço: " . htmlspecialchars($service['name']);
        $csrfToken = Session::generateCsrfToken();

        if ($this->isAjax()) {
            ob_start();
            require_once BASE_PATH . '/app/views/services/edit.php';
            echo ob_get_clean();
            return;
        }

        ob_start();
        require_once BASE_PATH . '/app/views/services/edit.php';
        $content = ob_get_clean();
        require_once BASE_PATH . '/app/views/layout.php';
    }

    /**
     * Update the specified service in storage.
     *
     * @param int $id
     */
    public function update(int $id): void
    {
        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            $this->handleError('Token CSRF inválido.', $id);
        }

        $service = $this->serviceModel->find($id);
        if (!$service) {
            $this->handleError('Serviço não encontrado.', $id);
        }

        $errors = $this->validateServiceData($_POST);
        if (!empty($errors)) {
            $this->handleError(implode('<br>', $errors), $id);
        }

        $data = $this->prepareServiceData($_POST);

        if ($this->serviceModel->update($id, $data)) {
            ForgeLogger::logAction('Serviço "' . $data['name'] . '" (ID: ' . $id . ') atualizado pelo usuário ' . Session::get('user_name') . '.');
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Serviço atualizado com sucesso!']);
                exit();
            }
            Session::flash('success', 'Serviço atualizado com sucesso!');
            header('Location: ' . ROUTE_BASE . '/services');
            exit();
        } else {
            $this->handleError('Erro ao atualizar serviço.', $id);
        }
    }

    /**
     * Remove the specified service from storage.
     *
     * @param int $id
     */
    public function destroy(int $id): void
    {
        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            Session::flash('error', 'Token CSRF inválido.');
            header('Location: ' . ROUTE_BASE . '/services');
            exit();
        }

        if ($this->serviceModel->delete($id)) {
            ForgeLogger::logAction('Serviço (ID: ' . $id . ') excluído pelo usuário ' . Session::get('user_name') . '.');
            Session::flash('success', 'Serviço excluído com sucesso!');
        } else {
            Session::flash('error', 'Erro ao excluir serviço.');
        }
        header('Location: ' . ROUTE_BASE . '/services');
        exit();
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function validateServiceData(array $post): array
    {
        $errors = [];
        if (empty(trim($post['name'] ?? ''))) {
            $errors[] = 'O nome do serviço é obrigatório.';
        }
        if (isset($post['price']) && (float)$post['price'] < 0) {
            $errors[] = 'O preço não pode ser negativo.';
        }
        return $errors;
    }

    private function prepareServiceData(array $post): array
    {
        return [
            'name' => trim($post['name'] ?? ''),
            'description' => trim($post['description'] ?? ''),
            'price' => (float)($post['price'] ?? 0.00),
            'is_active' => isset($post['is_active']) ? 1 : 0,
        ];
    }

    private function handleError(string $message, ?int $serviceId = null): void
    {
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            http_response_code(422); // Unprocessable Entity
            echo json_encode(['success' => false, 'errors' => [$message]]);
            exit();
        }
        Session::flash('error', $message);
        $location = ROUTE_BASE . ($serviceId ? '/services/' . $serviceId . '/edit' : '/services/create');
        header('Location: ' . $location);
        exit();
    }
}
