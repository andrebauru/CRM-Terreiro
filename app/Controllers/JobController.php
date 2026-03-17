<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;

use App\Helpers\Session;
use App\Helpers\Upload; // Adicionado para lidar com uploads
use App\Helpers\ForgeLogger; // Adicionado para logging
use App\Models\Job;
use App\Models\Client;
use App\Models\Service;
use App\Models\User; // To fetch users for assignment
use App\Models\JobAttachment; // Adicionado para gerenciar anexos
use App\Models\JobNote; // Adicionado para gerenciar notas
use App\Models\JobInstallment; // Gerenciar parcelas

class JobController extends BaseController
{
    private Job $jobModel;
    private Client $clientModel;
    private Service $serviceModel;
    private User $userModel;
    private JobAttachment $jobAttachmentModel; // Instância do modelo JobAttachment
    private JobNote $jobNoteModel; // Instância do modelo JobNote
    private JobInstallment $jobInstallmentModel;

    public function __construct()
    {
        $this->jobModel = new Job();
        $this->clientModel = new Client();
        $this->serviceModel = new Service();
        $this->userModel = new User();
        $this->jobAttachmentModel = new JobAttachment(); // Instancia o modelo JobAttachment
        $this->jobNoteModel = new JobNote(); // Instancia o modelo JobNote
        $this->jobInstallmentModel = new JobInstallment();
    }

    /**
     * Display a listing of the jobs.
     */
    public function index(): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }
        $jobs = $this->jobModel->all();
        $this->render('jobs/index', [
            'title' => "Trabalhos",
            'jobs' => $jobs,
            'breadcrumb' => [
                ['label' => 'Trabalhos']
            ]
        ]);
    }

    /**
     * Show the form for creating a new job.
     */
    public function create(): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }
        $clients = $this->clientModel->all();
        $services = $this->serviceModel->all();
        $users = $this->userModel->all();

        $data = [
            'title' => "Novo Trabalho",
            'csrfToken' => Session::generateCsrfToken(),
            'clients' => $clients,
            'services' => $services,
            'users' => $users
        ];

        if ($this->isAjax()) {
            $this->render('jobs/create', $data);
            return;
        }

        $this->render('jobs/create', $data);
    }

    /**
     * Store a newly created job in storage.
     */
    public function store(): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }

        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            $this->handleError('Token CSRF inválido.');
        }

        $errors = $this->validateJobData($_POST);
        if (!empty($errors)) {
            $this->handleError(implode('<br>', $errors));
        }

        $data = $this->prepareJobData($_POST);
        $jobId = $this->jobModel->create($data);

        if ($jobId) {
            $this->jobInstallmentModel->ensureForJob(
                $jobId,
                (int)$data['installments'],
                $data['installment_value'],
                $data['start_date']
            );
            ForgeLogger::logAction('Trabalho "' . $data['title'] . '" (ID: ' . $jobId . ') criado pelo usuário ' . Session::get('user_name') . '.');
            $this->handleFileUploads($jobId);

            if ($this->isAjax()) {
                $this->json(['success' => true, 'message' => 'Trabalho criado com sucesso!']);
            }
            Session::flash('success', 'Trabalho criado com sucesso!');
            $this->redirect('trabalhos.php');
        } else {
            $this->handleError('Erro ao criar trabalho.');
        }
    }

    /**
     * Display the specified job.
     *
     * @param int $id
     */
    public function show(int $id): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }

        $job = $this->jobModel->find($id);

        if (!$job) {
            Session::flash('error', 'Trabalho não encontrado.');
            $this->redirect('trabalhos.php');
        }

        $currentUserId = Session::get('user_id');
        $currentUserRole = Session::get('user_role');
        if ($currentUserRole !== 'admin' && (int)$job['created_by'] !== (int)$currentUserId) {
            Session::flash('error', 'Você não tem permissão para visualizar esta tarefa.');
            $this->redirect('trabalhos.php');
        }

        $attachments = $this->jobAttachmentModel->getByJobId($id); // Fetch attachments
        $notes = $this->jobNoteModel->getByJobId($id); // Fetch notes
        $this->jobInstallmentModel->ensureForJob(
            $id,
            (int)($job['installments'] ?? 1),
            isset($job['installment_value']) ? (float)$job['installment_value'] : null,
            $job['start_date'] ?: null
        );
        $installments = $this->jobInstallmentModel->getByJobId($id);

        $this->render('jobs/show', [
            'title' => "Detalhes da Tarefa: " . $job['title'],
            'job' => $job,
            'attachments' => $attachments,
            'notes' => $notes,
            'installments' => $installments,
            'breadcrumb' => [
                ['label' => 'Trabalhos', 'url' => ROUTE_BASE . '/jobs'],
                ['label' => $job['title']]
            ]
        ]);
    }

    /**
     * Show the form for editing the specified job.
     *
     * @param int $id
     */
    public function edit(int $id): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }

        $job = $this->jobModel->find($id);

        if (!$job) {
            Session::flash('error', 'Trabalho não encontrado.');
            $this->redirect('trabalhos.php');
        }

        $clients = $this->clientModel->all();
        $services = $this->serviceModel->all();
        $users = $this->userModel->all();
        $attachments = $this->jobAttachmentModel->getByJobId($id);

        $data = [
            'title' => "Editar Trabalho: " . htmlspecialchars($job['title']),
            'csrfToken' => Session::generateCsrfToken(),
            'job' => $job,
            'clients' => $clients,
            'services' => $services,
            'users' => $users,
            'attachments' => $attachments
        ];

        if ($this->isAjax()) {
            $this->render('jobs/edit', $data);
            return;
        }

        $this->render('jobs/edit', $data);
    }

    /**
     * Update the specified job in storage.
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

        $job = $this->jobModel->find($id);
        if (!$job) {
            $this->handleError('Trabalho não encontrado.', $id);
        }

        $errors = $this->validateJobData($_POST, $id);
        if (!empty($errors)) {
            $this->handleError(implode('<br>', $errors), $id);
        }

        $data = $this->prepareJobData($_POST, $job);

        if ($this->jobModel->update($id, $data)) {
            $this->jobInstallmentModel->ensureForJob(
                $id,
                (int)$data['installments'],
                $data['installment_value'],
                $data['start_date']
            );
            ForgeLogger::logAction('Trabalho "' . $data['title'] . '" (ID: ' . $id . ') atualizado pelo usuário ' . Session::get('user_name') . '.');
            $this->handleFileUploads($id);

            if ($this->isAjax()) {
                $this->json(['success' => true, 'message' => 'Trabalho atualizado com sucesso!']);
            }
            Session::flash('success', 'Trabalho atualizado com sucesso!');
            $this->redirect('trabalhos.php');
        } else {
            $this->handleError('Erro ao atualizar trabalho.', $id);
        }
    }

    /**
     * Remove the specified job from storage.
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
            $this->redirect('trabalhos.php');
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
            ForgeLogger::logAction('Trabalho (ID: ' . $id . ') excluído pelo usuário ' . Session::get('user_name') . '.'); // Log action
            Session::flash('success', 'Trabalho excluído com sucesso!');
        } else {
            Session::flash('error', 'Erro ao excluir trabalho.');
        }
        $this->redirect('trabalhos.php');
    }

    /**
     * Adds a new note to a job.
     *
     * @param int $jobId The ID of the job to add the note to.
     */
    public function addNote(int $jobId): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }

        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            Session::flash('error', 'Token CSRF inválido.');
            $this->redirect('jobs/' . $jobId);
        }

        $noteContent = trim($_POST['note'] ?? '');
        $userId = Session::get('user_id');

        if (empty($noteContent)) {
            Session::flash('error', 'A nota não pode estar vazia.');
            $this->redirect('jobs/' . $jobId);
        }

        if ($userId === null) {
            Session::flash('error', 'Usuário não autenticado.');
            $this->redirect('login');
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

        $this->redirect('jobs/' . $jobId);
    }

    /**
     * Deletes a job note.
     *
     * @param int $noteId The ID of the note to delete.
     */
    public function deleteNote(int $noteId): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }

        // Get job_id from POST or wherever it's passed for redirection
        $jobId = (int)($_POST['job_id'] ?? 0); // Assuming job_id will be passed via POST for redirection

        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            Session::flash('error', 'Token CSRF inválido.');
            $this->redirect($jobId ? 'jobs/' . $jobId : 'jobs');
        }

        $note = $this->jobNoteModel->find($noteId);

        if (!$note) {
            Session::flash('error', 'Nota não encontrada.');
            $this->redirect($jobId ? 'jobs/' . $jobId : 'jobs');
        }

        $currentUserId = Session::get('user_id');
        $currentUserRole = Session::get('user_role');
        if ($currentUserRole !== 'admin' && $currentUserId !== ($note['user_id'] ?? null)) {
            Session::flash('error', 'Você não tem permissão para excluir esta nota.');
            $this->redirect($jobId ? 'jobs/' . $jobId : 'jobs');
        }

        if ($this->jobNoteModel->delete($noteId)) {
            ForgeLogger::logAction('Nota (ID: ' . $noteId . ') da Tarefa (ID: ' . ($note['job_id'] ?? 'N/A') . ') excluída pelo usuário ' . Session::get('user_name') . '.'); // Log action
            Session::flash('success', 'Nota excluída com sucesso!');
        } else {
            Session::flash('error', 'Erro ao excluir nota.');
        }

        $this->redirect($jobId ? 'jobs/' . $jobId : 'jobs');
    }

    /**
     * Deletes a job attachment.
     *
     * @param int $attachmentId The ID of the attachment to delete.
     */
    public function deleteAttachment(int $attachmentId): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }

        // Get job_id from POST or wherever it's passed for redirection
        $jobId = (int)($_POST['job_id'] ?? 0); // Assuming job_id will be passed via POST for redirection

        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            Session::flash('error', 'Token CSRF inválido.');
            $this->redirect($jobId ? 'jobs/' . $jobId . '/edit' : 'jobs');
        }

        $attachment = $this->jobAttachmentModel->find($attachmentId);

        if (!$attachment) {
            Session::flash('error', 'Anexo não encontrado.');
            $this->redirect($jobId ? 'jobs/' . $jobId . '/edit' : 'jobs');
        }

        $currentUserId = Session::get('user_id');
        $currentUserRole = Session::get('user_role');
        if ($currentUserRole !== 'admin' && $currentUserId !== ($attachment['user_id'] ?? null)) {
            Session::flash('error', 'Você não tem permissão para excluir este anexo.');
            $this->redirect($jobId ? 'jobs/' . $jobId . '/edit' : 'jobs');
        }

        // Delete file from server
        if (file_exists(BASE_PATH . '/' . $attachment['filepath'])) {
            unlink(BASE_PATH . '/' . $attachment['filepath']);
        }

        // Delete from database
        if ($this->jobAttachmentModel->delete($attachmentId)) {
            ForgeLogger::logAction('Anexo ' . $attachment['filename'] . ' (ID: ' . $attachmentId . ') do trabalho ID ' . $jobId . ' excluído pelo usuário ' . Session::get('user_name') . '.');
            Session::flash('success', 'Anexo removido com sucesso!');
        } else {
            Session::flash('error', 'Erro ao remover anexo.');
        }

        $this->redirect('jobs/' . $jobId);
    }

    /**
     * Mark an installment as paid (manual baixa).
     *
     * @param int $installmentId
     */
    public function payInstallment(int $installmentId): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }

        $jobId = (int)($_POST['job_id'] ?? 0);

        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            Session::flash('error', 'Token CSRF inválido.');
            $this->redirect($jobId ? 'jobs/' . $jobId : 'jobs');
        }

        $installment = $this->jobInstallmentModel->find($installmentId);
        if (!$installment) {
            Session::flash('error', 'Parcela não encontrada.');
            $this->redirect($jobId ? 'jobs/' . $jobId : 'jobs');
        }

        $amount = trim((string)($_POST['amount'] ?? ''));
        $amount = $amount === '' ? null : (float)$amount;
        $userId = (int)Session::get('user_id');

        if ($this->jobInstallmentModel->markPaid($installmentId, $userId, $amount)) {
            ForgeLogger::logAction('Parcela (ID: ' . $installmentId . ') da Tarefa (ID: ' . ($installment['job_id'] ?? 'N/A') . ') baixada pelo usuário ' . Session::get('user_name') . '.');
            Session::flash('success', 'Parcela baixada com sucesso!');
        } else {
            Session::flash('error', 'Erro ao baixar parcela.');
        }

        $this->redirect($jobId ? 'jobs/' . $jobId : 'jobs');
    }



    /**
     * Validate job data from POST request.
     */
    private function validateJobData(array $post, ?int $jobId = null): array
    {
        $errors = [];
        if (empty(trim($post['title'] ?? ''))) {
            $errors[] = 'O título é obrigatório.';
        }
        if (empty($post['client_id'])) {
            $errors[] = 'O cliente é obrigatório.';
        }
        if (empty($post['service_id'])) {
            $errors[] = 'O serviço é obrigatório.';
        }
        if (isset($post['installment_value']) && $post['installment_value'] !== '' && (float)$post['installment_value'] < 0) {
            $errors[] = 'O valor da parcela não pode ser negativo.';
        }
        return $errors;
    }

    /**
     * Prepare job data for create/update.
     */
    private function prepareJobData(array $post, ?array $existingJob = null): array
    {
        $installmentValue = trim((string)($post['installment_value'] ?? ''));
        $status = $post['status'] ?? 'pending';

        // Set completed_at when status changes to completed
        $completedAt = $existingJob['completed_at'] ?? null;
        if ($status === 'completed' && ($existingJob === null || $existingJob['status'] !== 'completed')) {
            $completedAt = date('Y-m-d H:i:s');
        } elseif ($status !== 'completed') {
            $completedAt = null;
        }

        return [
            'client_id' => (int)($post['client_id'] ?? 0),
            'service_id' => (int)($post['service_id'] ?? 0),
            'title' => trim($post['title'] ?? ''),
            'description' => trim($post['description'] ?? ''),
            'status' => $status,
            'priority' => $post['priority'] ?? 'medium',
            'channel' => trim($post['channel'] ?? ($existingJob['channel'] ?? '')),
            'start_date' => empty($post['start_date']) ? null : $post['start_date'],
            'due_date' => empty($post['due_date']) ? null : $post['due_date'],
            'created_by' => $existingJob['created_by'] ?? Session::get('user_id'),
            'assigned_to' => empty($post['assigned_to']) ? null : (int)$post['assigned_to'],
            'installments' => max(1, (int)($post['installments'] ?? 1)),
            'installment_value' => $installmentValue === '' ? null : (float)$installmentValue,
            'completed_at' => $completedAt,
        ];
    }

    /**
     * Handle file uploads for a job.
     */
    private function handleFileUploads(int $jobId): void
    {
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

                    $uploadResult = Upload::handleCompressedImageUpload($uploadedFile, UPLOAD_PATH);

                    if ($uploadResult) {
                        $this->jobAttachmentModel->create([
                            'job_id' => $jobId,
                            'user_id' => Session::get('user_id'),
                            'filename' => $uploadResult['filename'],
                            'filepath' => $uploadResult['filepath'],
                            'file_type' => $uploadResult['file_type'],
                            'file_size' => $uploadResult['file_size'],
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Handle errors for AJAX and regular requests.
     */
    private function handleError(string $message, ?int $jobId = null): void
    {
        // Use the isAjax from BaseController
        if ($this->isAjax()) { 
            $this->json(['success' => false, 'errors' => [$message]], 400);
        }
        Session::flash('error', $message);
        $location = ($jobId ? 'jobs/' . $jobId . '/edit' : 'jobs/create');
        $this->redirect($location);
    }
}
