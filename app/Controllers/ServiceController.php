<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;

use App\Helpers\Session;
use App\Helpers\ForgeLogger; // Adicionado para logging
use App\Models\Service;
use App\Models\Setting;

class ServiceController extends BaseController
{
    private Service $serviceModel;

    public function __construct()
    {
        $this->serviceModel = new Service();
    }

    /**
     * Display a listing of the services.
     */
    public function index(): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }

        $services = $this->serviceModel->all();
        $settings = (new Setting())->get();
        $this->render('services/index', [
            'title' => "Serviços",
            'services' => $services,
            'settings' => $settings,
            'breadcrumb' => [
                ['label' => 'Serviços']
            ]
        ]);
    }

    /**
     * Show the form for creating a new service.
     */
    public function create(): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }

        $data = [
            'title' => "Novo Serviço",
            'csrfToken' => Session::generateCsrfToken()
        ];

        if ($this->isAjax()) {
            $this->render('services/create', $data);
            return;
        }

        $this->render('services/create', $data);
    }

    /**
     * Store a newly created service in storage.
     */
    public function store(): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }

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
                $this->json(['success' => true, 'message' => 'Serviço criado com sucesso!']);
            }
            Session::flash('success', 'Serviço criado com sucesso!');
            $this->redirect('servicos.php');
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
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }

        $service = $this->serviceModel->find($id);

        if (!$service) {
            Session::flash('error', 'Serviço não encontrado.');
            $this->redirect('servicos.php');
        }

        $settings = (new Setting())->get();
        $this->render('services/show', [
            'title' => "Detalhes do Serviço: " . $service['name'],
            'service' => $service,
            'settings' => $settings,
            'breadcrumb' => [
                ['label' => 'Serviços', 'url' => ROUTE_BASE . '/services'],
                ['label' => $service['name']]
            ]
        ]);
    }

    /**
     * Show the form for editing the specified service.
     *
     * @param int $id
     */
    public function edit(int $id): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }

        $service = $this->serviceModel->find($id);

        if (!$service) {
            Session::flash('error', 'Serviço não encontrado.');
            $this->redirect('servicos.php');
        }

        $data = [
            'title' => "Editar Serviço: " . htmlspecialchars($service['name']),
            'csrfToken' => Session::generateCsrfToken(),
            'service' => $service
        ];

        if ($this->isAjax()) {
            $this->render('services/edit', $data);
            return;
        }

        $this->render('services/edit', $data);
    }

    /**
     * Update the specified service in storage.
     *
     * @param int $id
     */
    public function update(int $id): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }

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
                $this->json(['success' => true, 'message' => 'Serviço atualizado com sucesso!']);
            }
            Session::flash('success', 'Serviço atualizado com sucesso!');
            $this->redirect('servicos.php');
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
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }

        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            Session::flash('error', 'Token CSRF inválido.');
            $this->redirect('servicos.php');
        }

        if ($this->serviceModel->delete($id)) {
            ForgeLogger::logAction('Serviço (ID: ' . $id . ') excluído pelo usuário ' . Session::get('user_name') . '.');
            Session::flash('success', 'Serviço excluído com sucesso!');
        } else {
            Session::flash('error', 'Erro ao excluir serviço.');
        }
        $this->redirect('servicos.php');
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
        // Use the isAjax from BaseController
        if ($this->isAjax()) {
            $this->json(['success' => false, 'errors' => [$message]], 422);
        }
        Session::flash('error', $message);
        $location = ($serviceId ? 'services/' . $serviceId . '/edit' : 'services/create');
        $this->redirect($location);
    }
}
