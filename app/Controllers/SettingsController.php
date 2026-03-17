<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;

use App\Helpers\Session;
use App\Models\Setting;
use App\Helpers\Upload;

class SettingsController extends BaseController
{
    private Setting $settingsModel;

    public function __construct()
    {
        $this->settingsModel = new Setting();
    }

    public function index(): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }
        if (Session::get('user_role') !== 'admin') {
            Session::flash('error', 'Você não tem permissão para acessar esta área.');
            $this->redirect('dashboard.php');
        }

        $settings = $this->settingsModel->get();
        $this->render('settings/index', [
            'title' => 'Configurações',
            'settings' => $settings,
            'csrfToken' => Session::generateCsrfToken(),
            'breadcrumb' => [
                ['label' => 'Configurações']
            ]
        ]);
    }

    public function update(): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }
        if (Session::get('user_role') !== 'admin') {
            Session::flash('error', 'Você não tem permissão para acessar esta área.');
            $this->redirect('dashboard.php');
        }

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
                    $this->json(['success' => false, 'errors' => [Session::get('error')]]);
                }
                $this->redirect('configuracoes.php');
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
                $response = ['success' => true, 'message' => 'Configurações atualizadas com sucesso!'];
                if ($newLogoUploaded) {
                    $response['new_logo_url'] = BASE_URL . '/' . $logoPath;
                }
                $this->json($response);
            }
            Session::flash('success', 'Configurações atualizadas com sucesso!');
        } else {
            $this->handleError('Erro ao atualizar configurações.');
        }

        $this->redirect('configuracoes.php');
    }

    private function handleError(string $message): void
    {
        // Use the isAjax from BaseController
        if ($this->isAjax()) {
            $this->json(['success' => false, 'errors' => [$message]], 422);
        }
        Session::flash('error', $message);
        $this->redirect('configuracoes.php');
    }
}
