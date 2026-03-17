<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;

use App\Helpers\Session;
use App\Models\Client;
use App\Models\Service;
use App\Models\Job;
use App\Models\Setting;
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends BaseController
{
    private Client $clientModel;
    private Service $serviceModel;
    private Job $jobModel;
    private Setting $settingModel;

    public function __construct()
    {
        $this->clientModel = new Client();
        $this->serviceModel = new Service();
        $this->jobModel = new Job();
        $this->settingModel = new Setting();
    }

    public function dashboardPdf(): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }

        $stats = $this->getDashboardStats();
        $settings = $this->settingModel->get();

        if (!class_exists(Dompdf::class)) {
            Session::flash('error', 'Exportação em PDF indisponível.');
            $this->redirect('dashboard.php');
        }

        $logoDataUri = null;
        if (!empty($settings['logo_path'])) {
            $logoFullPath = BASE_PATH . '/' . $settings['logo_path'];
            if (file_exists($logoFullPath)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = $finfo ? finfo_file($finfo, $logoFullPath) : 'image/png';
                if ($finfo) {
                    finfo_close($finfo);
                }
                $logoData = base64_encode(file_get_contents($logoFullPath));
                $logoDataUri = 'data:' . $mime . ';base64,' . $logoData;
            }
        }

        ob_start();
        require BASE_PATH . '/app/views/reports/dashboard_pdf.php';
        $html = ob_get_clean();

        $dompdf = new Dompdf(['isRemoteEnabled' => true]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('dashboard-report.pdf', ['Attachment' => true]);
        exit();
    }

    public function dashboardXls(): void
    {
        if (!Session::exists('user_id')) {
            $this->redirect('login');
        }

        $stats = $this->getDashboardStats();
        $settings = $this->settingModel->get();

        if (!class_exists(Spreadsheet::class)) {
            $this->exportCsvFallback($stats, $settings);
            return;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Dashboard');

        $sheet->setCellValue('A1', 'Relatório do Dashboard');
        $sheet->setCellValue('A2', 'Cliente');
        $sheet->setCellValue('B2', $settings['client_name'] ?? '');
        $sheet->setCellValue('A3', 'Empresa');
        $sheet->setCellValue('B3', $settings['company_name'] ?? '');

        $sheet->setCellValue('A5', 'Total de Clientes');
        $sheet->setCellValue('B5', $stats['totalClients']);
        $sheet->setCellValue('A6', 'Total de Serviços');
        $sheet->setCellValue('B6', $stats['totalServices']);
        $sheet->setCellValue('A7', 'Total de Tarefas');
        $sheet->setCellValue('B7', $stats['totalJobs']);
        $sheet->setCellValue('A8', 'Tarefas Pendentes');
        $sheet->setCellValue('B8', $stats['pendingJobs']);
        $sheet->setCellValue('A9', 'Tarefas Em Andamento');
        $sheet->setCellValue('B9', $stats['inProgressJobs']);
        $sheet->setCellValue('A10', 'Tarefas Concluídas');
        $sheet->setCellValue('B10', $stats['completedJobs']);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="dashboard-report.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
    }

    private function exportCsvFallback(array $stats, array $settings): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment;filename="dashboard-report.csv"');
        header('Cache-Control: max-age=0');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Relatório do Dashboard']);
        fputcsv($output, ['Cliente', $settings['client_name'] ?? '']);
        fputcsv($output, ['Empresa', $settings['company_name'] ?? '']);
        fputcsv($output, []);
        fputcsv($output, ['Métrica', 'Valor']);
        fputcsv($output, ['Total de Clientes', $stats['totalClients']]);
        fputcsv($output, ['Total de Serviços', $stats['totalServices']]);
        fputcsv($output, ['Total de Tarefas', $stats['totalJobs']]);
        fputcsv($output, ['Tarefas Pendentes', $stats['pendingJobs']]);
        fputcsv($output, ['Tarefas Em Andamento', $stats['inProgressJobs']]);
        fputcsv($output, ['Tarefas Concluídas', $stats['completedJobs']]);
        fclose($output);
        exit();
    }

    private function getDashboardStats(): array
    {
        return [
            'totalClients' => $this->clientModel->count(),
            'totalServices' => $this->serviceModel->count(),
            'totalJobs' => $this->jobModel->count(),
            'pendingJobs' => $this->jobModel->countByStatus('pending'),
            'inProgressJobs' => $this->jobModel->countByStatus('in_progress'),
            'completedJobs' => $this->jobModel->countByStatus('completed'),
        ];
    }
}
