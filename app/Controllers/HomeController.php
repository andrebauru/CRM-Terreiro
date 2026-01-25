<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Session;

class HomeController
{
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
        // Output buffering to capture the view content
        ob_start();
        require_once BASE_PATH . '/app/views/dashboard/index.php'; // This view will be created later
        $content = ob_get_clean();

        // Include the main layout for authenticated users
        require_once BASE_PATH . '/app/views/layout.php';
    }
}
