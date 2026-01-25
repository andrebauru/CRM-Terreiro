<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Session;
use App\Models\Client;
use App\Models\Service;
use App\Models\Job;

class HomeController
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
        // If user is logged in, redirect to dashboard
        if (Session::exists('user_id')) {
            header('Location: /dashboard');
            exit();
        }

        // If not logged in, redirect to login page as per app/views/home.php's content
        // The view itself handles the redirect now.
        require_once BASE_PATH . '/app/views/home.php';
    }

    public function dashboard(): void
    {
        $title = "Dashboard";

        // Fetch statistics
        $totalClients = $this->clientModel->count();
        $totalServices = $this->serviceModel->count();
        $totalJobs = $this->jobModel->count();
        $pendingJobs = $this->jobModel->countByStatus('pending');
        $inProgressJobs = $this->jobModel->countByStatus('in_progress');
        $completedJobs = $this->jobModel->countByStatus('completed');

        // Output buffering to capture the view content
        ob_start();
        require_once BASE_PATH . '/app/views/dashboard/index.php';
        $content = ob_get_clean();

        // Include the main layout for authenticated users
        require_once BASE_PATH . '/app/views/layout.php';
    }
}
