<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Session;
use App\Models\Client;
use App\Models\Service;
use App\Models\Job;

class HomeController extends BaseController // Extende BaseController
{
    private Client $clientModel;
    private Service $serviceModel;
    private Job $jobModel;

    public function __construct()
    {
        $this->clientModel = new Client();
        $this->serviceModel = new Service();
        $this->jobModel = new Job();
    }

    public function index(): void
    {
        // Se o usuário estiver logado, redireciona para o dashboard legacy
        if (Session::exists('user_id')) {
            $this->redirect('dashboard.php');
        }

        // Se não estiver logado, renderiza a página de login
        // Usa renderRaw pois login.php tem HTML completo próprio (sem Tabler)
        $csrfToken = Session::generateCsrfToken();
        $this->renderRaw('auth/login', [
            'csrfToken' => $csrfToken,
            'title' => 'Login'
        ]);
    }

    public function dashboard(): void
    {
        // Verifica se o usuário está autenticado antes de exibir o dashboard
        if (!Session::exists('user_id')) {
            $this->redirect('login'); // Redireciona para login se não autenticado
        }

        // Fetch statistics
        $totalClients = $this->clientModel->count();
        $totalServices = $this->serviceModel->count();
        $totalJobs = $this->jobModel->count();
        $pendingJobs = $this->jobModel->countByStatus('pending');
        $inProgressJobs = $this->jobModel->countByStatus('in_progress');
        $completedJobs = $this->jobModel->countByStatus('completed');

        $data = [
            'title' => "Dashboard",
            'totalClients' => $totalClients,
            'totalServices' => $totalServices,
            'totalJobs' => $totalJobs,
            'pendingJobs' => $pendingJobs,
            'inProgressJobs' => $inProgressJobs,
            'completedJobs' => $completedJobs,
            'breadcrumb' => [
                ['label' => 'Dashboard']
            ]
        ];

        $this->render('dashboard/index', $data);
    }
}
