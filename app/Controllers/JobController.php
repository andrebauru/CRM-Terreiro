<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Session;
use App\Models\Job;
use App\Models\Client;
use App\Models\Service;
use App\Models\User; // To fetch users for assignment

class JobController
{
    private Job $jobModel;
    private Client $clientModel;
    private Service $serviceModel;
    private User $userModel;

    public function __construct()
    {
        // Redirect to login if not authenticated
        if (!Session::exists('user_id')) {
            header('Location: /login');
            exit();
        }
        $this->jobModel = new Job();
        $this->clientModel = new Client();
        $this->serviceModel = new Service();
        $this->userModel = new User();
    }

    /**
     * Display a listing of the jobs.
     */
    public function index(): void
    {
        $jobs = $this->jobModel->all();
        $title = "Tarefas";
        ob_start();
        require_once BASE_PATH . '/app/views/jobs/index.php';
        $content = ob_get_clean();
        require_once BASE_PATH . '/app/views/layout.php';
    }

    /**
     * Show the form for creating a new job.
     */
    public function create(): void
    {
        $clients = $this->clientModel->all();
        $services = $this->serviceModel->all();
        $users = $this->userModel->all(); // Assuming a method `all()` exists in User model

        $title = "Nova Tarefa";
        $csrfToken = Session::generateCsrfToken();
        ob_start();
        require_once BASE_PATH . '/app/views/jobs/create.php';
        $content = ob_get_clean();
        require_once BASE_PATH . '/app/views/layout.php';
    }

    /**
     * Store a newly created job in storage.
     */
    public function store(): void
    {
        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            Session::flash('error', 'Token CSRF inválido.');
            header('Location: /jobs/create');
            exit();
        }

        $clientId = (int)($_POST['client_id'] ?? 0);
        $serviceId = (int)($_POST['service_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'pending';
        $priority = $_POST['priority'] ?? 'medium';
        $channel = trim($_POST['channel'] ?? '');
        $startDate = $_POST['start_date'] ?? null;
        $dueDate = $_POST['due_date'] ?? null;
        $assignedTo = (int)($_POST['assigned_to'] ?? 0);
        $createdBy = Session::get('user_id');

        if (empty($title) || $clientId === 0 || $serviceId === 0 || $createdBy === null) {
            Session::flash('error', 'Por favor, preencha todos os campos obrigatórios (Título, Cliente, Serviço).');
            header('Location: /jobs/create');
            exit();
        }

        $data = [
            'client_id' => $clientId,
            'service_id' => $serviceId,
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'priority' => $priority,
            'channel' => $channel,
            'start_date' => $startDate,
            'due_date' => $dueDate,
            'created_by' => $createdBy,
            'assigned_to' => ($assignedTo === 0) ? null : $assignedTo, // Store null if not assigned
        ];

        $jobId = $this->jobModel->create($data);

        if ($jobId) {
            Session::flash('success', 'Tarefa criada com sucesso!');
            header('Location: /jobs/' . $jobId);
            exit();
        } else {
            Session::flash('error', 'Erro ao criar tarefa.');
            header('Location: /jobs/create');
            exit();
        }
    }

    /**
     * Display the specified job.
     *
     * @param int $id
     */
    public function show(int $id): void
    {
        $job = $this->jobModel->find($id);

        if (!$job) {
            Session::flash('error', 'Tarefa não encontrada.');
            header('Location: /jobs');
            exit();
        }

        $title = "Detalhes da Tarefa: " . $job['title'];
        ob_start();
        require_once BASE_PATH . '/app/views/jobs/show.php';
        $content = ob_get_clean();
        require_once BASE_PATH . '/app/views/layout.php';
    }

    /**
     * Show the form for editing the specified job.
     *
     * @param int $id
     */
    public function edit(int $id): void
    {
        $job = $this->jobModel->find($id);

        if (!$job) {
            Session::flash('error', 'Tarefa não encontrada.');
            header('Location: /jobs');
            exit();
        }

        $clients = $this->clientModel->all();
        $services = $this->serviceModel->all();
        $users = $this->userModel->all(); // Assuming a method `all()` exists in User model

        $title = "Editar Tarefa: " . $job['title'];
        $csrfToken = Session::generateCsrfToken();
        ob_start();
        require_once BASE_PATH . '/app/views/jobs/edit.php';
        $content = ob_get_clean();
        require_once BASE_PATH . '/app/views/layout.php';
    }

    /**
     * Update the specified job in storage.
     *
     * @param int $id
     */
    public function update(int $id): void
    {
        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            Session::flash('error', 'Token CSRF inválido.');
            header('Location: /jobs/' . $id . '/edit');
            exit();
        }

        $clientId = (int)($_POST['client_id'] ?? 0);
        $serviceId = (int)($_POST['service_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'pending';
        $priority = $_POST['priority'] ?? 'medium';
        $channel = trim($_POST['channel'] ?? '');
        $startDate = $_POST['start_date'] ?? null;
        $dueDate = $_POST['due_date'] ?? null;
        $assignedTo = (int)($_POST['assigned_to'] ?? 0);
        $completedAt = null;
        if ($status === 'completed') {
            $completedAt = date('Y-m-d H:i:s');
        }

        if (empty($title) || $clientId === 0 || $serviceId === 0) {
            Session::flash('error', 'Por favor, preencha todos os campos obrigatórios (Título, Cliente, Serviço).');
            header('Location: /jobs/' . $id . '/edit');
            exit();
        }

        $data = [
            'client_id' => $clientId,
            'service_id' => $serviceId,
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'priority' => $priority,
            'channel' => $channel,
            'start_date' => $startDate,
            'due_date' => $dueDate,
            'assigned_to' => ($assignedTo === 0) ? null : $assignedTo,
            'completed_at' => $completedAt,
        ];

        if ($this->jobModel->update($id, $data)) {
            Session::flash('success', 'Tarefa atualizada com sucesso!');
            header('Location: /jobs/' . $id);
            exit();
        } else {
            Session::flash('error', 'Erro ao atualizar tarefa.');
            header('Location: /jobs/' . $id . '/edit');
            exit();
        }
    }

    /**
     * Remove the specified job from storage.
     *
     * @param int $id
     */
    public function destroy(int $id): void
    {
        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            Session::flash('error', 'Token CSRF inválido.');
            header('Location: /jobs');
            exit();
        }

        if ($this->jobModel->delete($id)) {
            Session::flash('success', 'Tarefa excluída com sucesso!');
        } else {
            Session::flash('error', 'Erro ao excluir tarefa.');
        }
        header('Location: /jobs');
        exit();
    }
}
