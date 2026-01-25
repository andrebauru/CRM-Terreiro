<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Session;
use App\Helpers\Upload; // Adicionado para lidar com uploads
use App\Helpers\ForgeLogger; // Adicionado para logging
use App\Models\Job;
use App\Models\Client;
use App\Models\Service;
use App\Models\User; // To fetch users for assignment
use App\Models\JobAttachment; // Adicionado para gerenciar anexos
use App\Models\JobNote; // Adicionado para gerenciar notas

class JobController
{
    private Job $jobModel;
    private Client $clientModel;
    private Service $serviceModel;
    private User $userModel;
    private JobAttachment $jobAttachmentModel; // Instância do modelo JobAttachment
    private JobNote $jobNoteModel; // Instância do modelo JobNote

    // Constantes para upload de arquivos
    private const ALLOWED_MIME_TYPES = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

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
        $this->jobAttachmentModel = new JobAttachment(); // Instancia o modelo JobAttachment
        $this->jobNoteModel = new JobNote(); // Instancia o modelo JobNote
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
            ForgeLogger::logAction('Tarefa "' . $title . '" (ID: ' . $jobId . ') criada pelo usuário ' . Session::get('user_name') . '.'); // Log action
            // Handle file uploads
            if (isset($_FILES['attachments']) && is_array($_FILES['attachments'])) {
                foreach ($_FILES['attachments']['name'] as $key => $name) {
                    if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                        $uploadedFile = [
                            'name' => $_FILES['attachments']['name'][$key],
                            'type' => $_FILES['attachments']['type'][$key],
                            'tmp_name' => $_FILES['attachments']['tmp_name'][$key],
                            'error' => $_FILES['attachments']['error'][$key],
                            'size' => $_FILES['attachments']['size'][$key],
                        ];

                        $uploadResult = Upload::handleUpload($uploadedFile, UPLOAD_PATH, self::ALLOWED_MIME_TYPES, self::MAX_FILE_SIZE);

                        if ($uploadResult) {
                            $this->jobAttachmentModel->create([
                                'job_id' => $jobId,
                                'user_id' => $createdBy,
                                'filename' => $uploadResult['filename'],
                                'filepath' => $uploadResult['filepath'],
                                'file_type' => $uploadResult['file_type'],
                                'file_size' => $uploadResult['file_size'],
                            ]);
                        }
                    }
                }
            }

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

        $attachments = $this->jobAttachmentModel->getByJobId($id); // Fetch attachments
        $notes = $this->jobNoteModel->getByJobId($id); // Fetch notes

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
        $attachments = $this->jobAttachmentModel->getByJobId($id); // Fetch existing attachments

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
            ForgeLogger::logAction('Tarefa "' . $title . '" (ID: ' . $id . ') atualizada pelo usuário ' . Session::get('user_name') . '.'); // Log action
            // Handle file uploads
            if (isset($_FILES['attachments']) && is_array($_FILES['attachments'])) {
                $createdBy = Session::get('user_id'); // User who is updating/uploading
                foreach ($_FILES['attachments']['name'] as $key => $name) {
                    if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                        $uploadedFile = [
                            'name' => $_FILES['attachments']['name'][$key],
                            'type' => $_FILES['attachments']['type'][$key],
                            'tmp_name' => $_FILES['attachments']['tmp_name'][$key],
                            'error' => $_FILES['attachments']['error'][$key],
                            'size' => $_FILES['attachments']['size'][$key],
                        ];

                        $uploadResult = Upload::handleUpload($uploadedFile, UPLOAD_PATH, self::ALLOWED_MIME_TYPES, self::MAX_FILE_SIZE);

                        if ($uploadResult) {
                            $this->jobAttachmentModel->create([
                                'job_id' => $id, // Use current job ID
                                'user_id' => $createdBy,
                                'filename' => $uploadResult['filename'],
                                'filepath' => $uploadResult['filepath'],
                                'file_type' => $uploadResult['file_type'],
                                'file_size' => $uploadResult['file_size'],
                            ]);
                        }
                    }
                }
            }

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

        // Before deleting job, delete associated attachments from filesystem
        $attachments = $this->jobAttachmentModel->getByJobId($id);
        foreach ($attachments as $attachment) {
            $fullPath = BASE_PATH . '/' . $attachment['filepath'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            // Database records will be cascade deleted by FK on job_id
        }

        if ($this->jobModel->delete($id)) {
            ForgeLogger::logAction('Tarefa (ID: ' . $id . ') excluída pelo usuário ' . Session::get('user_name') . '.'); // Log action
            Session::flash('success', 'Tarefa excluída com sucesso!');
        } else {
            Session::flash('error', 'Erro ao excluir tarefa.');
        }
        header('Location: /jobs');
        exit();
    }

    /**
     * Adds a new note to a job.
     *
     * @param int $jobId The ID of the job to add the note to.
     */
    public function addNote(int $jobId): void
    {
        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            Session::flash('error', 'Token CSRF inválido.');
            header('Location: /jobs/' . $jobId);
            exit();
        }

        $noteContent = trim($_POST['note'] ?? '');
        $userId = Session::get('user_id');

        if (empty($noteContent)) {
            Session::flash('error', 'A nota não pode estar vazia.');
            header('Location: /jobs/' . $jobId);
            exit();
        }

        if ($userId === null) {
            Session::flash('error', 'Usuário não autenticado.');
            header('Location: /login');
            exit();
        }

        $data = [
            'job_id' => $jobId,
            'user_id' => $userId,
            'note' => $noteContent,
        ];

        if ($this->jobNoteModel->create($data)) {
            ForgeLogger::logAction('Nota adicionada à Tarefa (ID: ' . $jobId . ') pelo usuário ' . Session::get('user_name') . '.'); // Log action
            Session::flash('success', 'Nota adicionada com sucesso!');
        } else {
            Session::flash('error', 'Erro ao adicionar nota.');
        }

        header('Location: /jobs/' . $jobId);
        exit();
    }

    /**
     * Deletes a job note.
     *
     * @param int $noteId The ID of the note to delete.
     */
    public function deleteNote(int $noteId): void
    {
        // Get job_id from POST or wherever it's passed for redirection
        $jobId = (int)($_POST['job_id'] ?? 0); // Assuming job_id will be passed via POST for redirection

        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            Session::flash('error', 'Token CSRF inválido.');
            if ($jobId) {
                header('Location: /jobs/' . $jobId);
            } else {
                header('Location: /jobs'); // Fallback if jobId is not available
            }
            exit();
        }

        $note = $this->jobNoteModel->find($noteId);

        if (!$note) {
            Session::flash('error', 'Nota não encontrada.');
            if ($jobId) {
                header('Location: /jobs/' . $jobId);
            } else {
                header('Location: /jobs');
            }
            exit();
        }

        // Optional: Check if the current user is authorized to delete this note
        // e.g., if (Session::get('user_id') !== $note['user_id'] && Session::get('user_role') !== 'admin') { ... }

        if ($this->jobNoteModel->delete($noteId)) {
            ForgeLogger::logAction('Nota (ID: ' . $noteId . ') da Tarefa (ID: ' . ($note['job_id'] ?? 'N/A') . ') excluída pelo usuário ' . Session::get('user_name') . '.'); // Log action
            Session::flash('success', 'Nota excluída com sucesso!');
        } else {
            Session::flash('error', 'Erro ao excluir nota.');
        }

        if ($jobId) {
            header('Location: /jobs/' . $jobId);
        } else {
            header('Location: /jobs');
        }
        exit();
    }

    /**
     * Deletes a job attachment.
     *
     * @param int $attachmentId The ID of the attachment to delete.
     */
    public function deleteAttachment(int $attachmentId): void
    {
        // Get job_id from POST or wherever it's passed for redirection
        $jobId = (int)($_POST['job_id'] ?? 0); // Assuming job_id will be passed via POST for redirection

        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            Session::flash('error', 'Token CSRF inválido.');
            if ($jobId) {
                header('Location: /jobs/' . $jobId . '/edit'); // Redirect back to edit page
            } else {
                header('Location: /jobs'); // Fallback if jobId is not available
            }
            exit();
        }

        $attachment = $this->jobAttachmentModel->find($attachmentId);

        if (!$attachment) {
            Session::flash('error', 'Anexo não encontrado.');
            if ($jobId) {
                header('Location: /jobs/' . $jobId . '/edit');
            } else {
                header('Location: /jobs');
            }
            exit();
        }

        // Delete file from filesystem
        $fullPath = BASE_PATH . '/' . $attachment['filepath'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        // Delete record from database
        if ($this->jobAttachmentModel->delete($attachmentId)) {
            ForgeLogger::logAction('Anexo (ID: ' . $attachmentId . ') da Tarefa (ID: ' . ($attachment['job_id'] ?? 'N/A') . ') excluído pelo usuário ' . Session::get('user_name') . '.'); // Log action
            Session::flash('success', 'Anexo excluído com sucesso!');
        } else {
            Session::flash('error', 'Erro ao excluir anexo.');
        }

        if ($jobId) {
            header('Location: /jobs/' . $jobId . '/edit'); // Redirect back to edit page
        } else {
            header('Location: /jobs');
        }
        exit();
    }
}
