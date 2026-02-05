<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Session;
use App\Models\Setting;
use App\Helpers\Upload;

class SettingsController
{
    private Setting $settingsModel;



    public function __construct()
    {
        if (!Session::exists('user_id')) {
            header('Location: ' . ROUTE_BASE . '/login');
            exit();
        }
        if (Session::get('user_role') !== 'admin') {
            Session::flash('error', 'Você não tem permissão para acessar esta área.');
            header('Location: ' . ROUTE_BASE . '/dashboard');
            exit();
        }
        $this->settingsModel = new Setting();
    }

    public function index(): void
    {
        $settings = $this->settingsModel->get();
        $title = 'Configurações';
        $csrfToken = Session::generateCsrfToken();

        ob_start();
        require_once BASE_PATH . '/app/views/settings/index.php';
        $content = ob_get_clean();
        require_once BASE_PATH . '/app/views/layout.php';
    }

    public function update(): void
    {
        if (!Session::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            $this->handleError('Token CSRF inválido.');
        }

        $clientName = trim($_POST['client_name'] ?? '');
        $companyName = trim($_POST['company_name'] ?? '');
        $currencyCode = strtoupper(trim($_POST['currency_code'] ?? 'JPY'));
        $currencySymbol = trim($_POST['currency_symbol'] ?? '¥');
        $timezone = trim($_POST['timezone'] ?? APP_TIMEZONE);

        if (strlen($currencyCode) !== 3) {
            $currencyCode = 'JPY';
        }
        if ($currencySymbol === '') {
            $currencySymbol = '¥';
        }
        if (!in_array($timezone, timezone_identifiers_list(), true)) {
            $timezone = APP_TIMEZONE;
        }

        if (empty($clientName) || empty($companyName)) {
            $this->handleError('Preencha o nome do cliente e da empresa.');
        }

        $settings = $this->settingsModel->get();
        $logoPath = $settings['logo_path'] ?? null;
        $newLogoUploaded = false;

        if (isset($_FILES['logo']) && is_array($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['logo'];

            $uploadResult = Upload::handleCompressedImageUpload(
                $file,
                UPLOAD_PATH . '/branding',
                false // $useDateFolders
            );

            if (!$uploadResult) {
                // Upload::handle... already flashes an error message
                if ($this->isAjax()) {
                    // The helper doesn't know about ajax, so we handle it here
                    $this->handleError(Session::get('error'));
                }
                header('Location: ' . ROUTE_BASE . '/settings');
                exit();
            }

            if (!empty($logoPath)) {
                $oldFullPath = BASE_PATH . '/' . $logoPath;
                if (file_exists($oldFullPath)) {
                    unlink($oldFullPath);
                }
            }

            $logoPath = $uploadResult['filepath'];
            $newLogoUploaded = true;
        }

        if ($this->settingsModel->updateSettings([
            'client_name' => $clientName,
            'company_name' => $companyName,
            'logo_path' => $logoPath,
            'currency_code' => $currencyCode,
            'currency_symbol' => $currencySymbol,
            'timezone' => $timezone,
        ])) {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                $response = ['success' => true, 'message' => 'Configurações atualizadas com sucesso!'];
                if ($newLogoUploaded) {
                    $response['new_logo_url'] = BASE_URL . '/' . $logoPath;
                }
                echo json_encode($response);
                exit();
            }
            Session::flash('success', 'Configurações atualizadas com sucesso!');
        } else {
            $this->handleError('Erro ao atualizar configurações.');
        }

        header('Location: ' . ROUTE_BASE . '/settings');
        exit();
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function handleError(string $message): void
    {
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            http_response_code(422);
            echo json_encode(['success' => false, 'errors' => [$message]]);
            exit();
        }
        Session::flash('error', $message);
        header('Location: ' . ROUTE_BASE . '/settings');
        exit();
    }
}
