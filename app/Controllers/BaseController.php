<?php
/** Direitos Autorais: Andre Silva */

namespace App\Controllers;

use App\Helpers\Session;

class BaseController
{
    protected function render(string $viewPath, array $data = []): void
    {
        // Extract data for the view
        extract($data);

        // Start output buffering
        ob_start();

        // Include the specific view file
        $fullViewPath = VIEW_PATH . '/' . $viewPath . '.php';
        if (file_exists($fullViewPath)) {
            require $fullViewPath;
        } else {
            // Handle view not found error
            http_response_code(500);
            echo "Error: View file not found: " . htmlspecialchars($fullViewPath);
            return;
        }

        // Get content from the buffer
        $content = ob_get_clean();

        // Include the main layout
        require VIEW_PATH . '/layout.php';
    }

    /**
     * Render a view directly without wrapping in layout.php.
     * Usado para páginas que possuem HTML completo próprio (ex: login).
     */
    protected function renderRaw(string $viewPath, array $data = []): void
    {
        extract($data);

        $fullViewPath = VIEW_PATH . '/' . $viewPath . '.php';
        if (file_exists($fullViewPath)) {
            require $fullViewPath;
        } else {
            http_response_code(500);
            echo "Error: View file not found: " . htmlspecialchars($fullViewPath);
        }
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . ROUTE_BASE . '/' . ltrim($path, '/'));
        exit();
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit();
    }

    // Basic permission check
    protected function authorize(string $requiredRole): void
    {
        if (!Session::exists('user_id') || Session::get('user_role') !== $requiredRole) {
            Session::set('error', 'Acesso negado.');
            $this->redirect('dashboard.php'); // Redireciona para o dashboard legacy
        }
    }

    /**
     * Check if the request is an AJAX request.
     */
    protected function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Get JSON input from the request body.
     */
    protected function getJsonInput(): array
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->json(['message' => 'Invalid JSON input'], 400);
        }
        return $data ?? [];
    }
}
