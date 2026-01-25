<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Session;
use App\Helpers\ForgeLogger; // Adicionado para logging
use App\Models\Service;

class ServiceController
{
    private Service $serviceModel;

    public function __construct()
    {
        // Redirect to login if not authenticated
        if (!Session::exists('user_id')) {
            header('Location: /login');
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
            Session::flash('error', 'Token CSRF inválido.');
            header('Location: /services/create');
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0.00);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name)) {
            Session::flash('error', 'O nome do serviço é obrigatório.');
            header('Location: /services/create');
            exit();
        }
        if ($price < 0) {
            Session::flash('error', 'O preço não pode ser negativo.');
            header('Location: /services/create');
            exit();
        }

        $data = [
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'is_active' => $is_active,
        ];

        $serviceId = $this->serviceModel->create($data);

        if ($serviceId) {
            ForgeLogger::logAction('Serviço ' . $name . ' (ID: ' . $serviceId . ') criado pelo usuário ' . Session::get('user_name') . '.'); // Log action
            Session::flash('success', 'Serviço criado com sucesso!');
            header('Location: /services/' . $serviceId);
            exit();
        } else {
            Session::flash('error', 'Erro ao criar serviço.');
            header('Location: /services/create');
            exit();
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
            header('Location: /services');
            exit();
        }

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
            header('Location: /services');
            exit();
        }

        $title = "Editar Serviço: " . $service['name'];
        $csrfToken = Session::generateCsrfToken();
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
            Session::flash('error', 'Token CSRF inválido.');
            header('Location: /services/' . $id . '/edit');
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0.00);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name)) {
            Session::flash('error', 'O nome do serviço é obrigatório.');
            header('Location: /services/' . $id . '/edit');
            exit();
        }
        if ($price < 0) {
            Session::flash('error', 'O preço não pode ser negativo.');
            header('Location: /services/' . $id . '/edit');
            exit();
        }

        $data = [
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'is_active' => $is_active,
        ];

        if ($this->serviceModel->update($id, $data)) {
            ForgeLogger::logAction('Serviço ' . $name . ' (ID: ' . $id . ') atualizado pelo usuário ' . Session::get('user_name') . '.'); // Log action
            Session::flash('success', 'Serviço atualizado com sucesso!');
            header('Location: /services/' . $id);
            exit();
        } else {
            Session::flash('error', 'Erro ao atualizar serviço.');
            header('Location: /services/' . $id . '/edit');
            exit();
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
            header('Location: /services');
            exit();
        }

        if ($this->serviceModel->delete($id)) {
            ForgeLogger::logAction('Serviço (ID: ' . $id . ') excluído pelo usuário ' . Session::get('user_name') . '.'); // Log action
            Session::flash('success', 'Serviço excluído com sucesso!');
        } else {
            Session::flash('error', 'Erro ao excluir serviço.');
        }
        header('Location: /services');
        exit();
    }
}
