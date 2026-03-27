<?php

declare(strict_types=1);

function sendGridNotifyBoard(PDO $pdo, string $section, string $action, string $title, string $details = ''): array
{
    $result = [
        'ok' => false,
        'status_code' => 0,
        'message' => '',
        'provider_response' => '',
        'section' => $section,
        'action' => $action,
        'title' => $title,
    ];

    try {
        ensureSendGridLogsTable($pdo);

        $settings = $pdo->query('SELECT company_name, sendgrid_api_key, notification_email, sendgrid_from_email, sendgrid_from_name FROM settings ORDER BY id ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC) ?: [];

        $apiKey = trim((string)($settings['sendgrid_api_key'] ?? ''));
        $toEmail = trim((string)($settings['notification_email'] ?? ''));
        $companyName = trim((string)($settings['company_name'] ?? 'CRM Terreiro')) ?: 'CRM Terreiro';
        $fromEmail = trim((string)($settings['sendgrid_from_email'] ?? ''));
        $fromName = trim((string)($settings['sendgrid_from_name'] ?? ''));
        if ($fromName === '') {
            $fromName = $companyName;
        }
        if ($fromEmail === '') {
            $fromEmail = $toEmail;
        }

        if ($apiKey === '' || $toEmail === '' || $fromEmail === '') {
            $result['message'] = 'Configuração incompleta: API Key, e-mail de destino e e-mail remetente são obrigatórios.';
            persistSendGridLog($pdo, $result, $toEmail, $fromEmail, 'Quadro de Avisos');
            return $result;
        }

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $result['message'] = 'E-mail de destino inválido.';
            persistSendGridLog($pdo, $result, $toEmail, $fromEmail, 'Quadro de Avisos');
            return $result;
        }

        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $result['message'] = 'E-mail remetente inválido.';
            persistSendGridLog($pdo, $result, $toEmail, $fromEmail, 'Quadro de Avisos');
            return $result;
        }

        $actionLabel = [
            'create' => 'Criado',
            'update' => 'Atualizado',
            'delete' => 'Removido',
        ][$action] ?? 'Atualizado';

        $sectionBase = trim($section) ?: 'AVISOS';
        $sectionUpper = function_exists('mb_strtoupper')
            ? mb_strtoupper($sectionBase, 'UTF-8')
            : strtoupper($sectionBase);
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeDetails = nl2br(htmlspecialchars($details, ENT_QUOTES, 'UTF-8'));

        $subject = 'Quadro de Avisos';
        $text = "{$sectionUpper} | {$actionLabel}\nTítulo: {$title}";
        if ($details !== '') {
            $text .= "\n\n{$details}";
        }

        $html = "
            <div style=\"font-family:Arial,sans-serif;color:#0f172a;line-height:1.5\">
                <h1 style=\"margin:0 0 12px 0;font-size:20px\">Quadro de Avisos</h1>
                <h2 style=\"margin:0 0 8px 0;font-size:18px;color:#dc2626;font-weight:800\">{$sectionUpper}</h2>
                <p style=\"margin:0 0 8px 0\"><strong>Ação:</strong> {$actionLabel}</p>
                <p style=\"margin:0 0 8px 0\"><strong>Título:</strong> {$safeTitle}</p>
                " . ($safeDetails !== '' ? "<p style=\"margin:0 0 12px 0\"><strong>Detalhes:</strong><br>{$safeDetails}</p>" : '') . "
                <hr style=\"border:none;border-top:1px solid #e2e8f0;margin:16px 0\">
                <p style=\"margin:0;color:#64748b;font-size:12px\">{$companyName}</p>
            </div>
        ";

        $payload = [
            'personalizations' => [[
                'to' => [[
                    'email' => $toEmail,
                    'name' => $companyName,
                ]],
                'subject' => $subject,
            ]],
            'from' => [
                'email' => $fromEmail,
                'name' => $fromName,
            ],
            'content' => [
                ['type' => 'text/plain', 'value' => $text],
                ['type' => 'text/html', 'value' => $html],
            ],
        ];

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($jsonPayload === false) {
            $result['message'] = 'Falha ao serializar payload JSON para SendGrid.';
            persistSendGridLog($pdo, $result, $toEmail, $fromEmail, $subject);
            return $result;
        }

        $status = 0;
        $responseBody = '';
        $curlError = '';

        if (function_exists('curl_init')) {
            $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json',
                ],
                CURLOPT_POSTFIELDS => $jsonPayload,
                CURLOPT_TIMEOUT => 20,
            ]);

            $exec = curl_exec($ch);
            if ($exec === false) {
                $curlError = (string)curl_error($ch);
                $responseBody = '';
            } else {
                $responseBody = (string)$exec;
            }
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Authorization: Bearer {$apiKey}\r\nContent-Type: application/json\r\n",
                    'content' => $jsonPayload,
                    'timeout' => 20,
                    'ignore_errors' => true,
                ],
            ]);
            $responseBody = (string)@file_get_contents('https://api.sendgrid.com/v3/mail/send', false, $context);
            if (function_exists('http_get_last_response_headers')) {
                $headers = http_get_last_response_headers();
                if (is_array($headers) && isset($headers[0]) && preg_match('/\s(\d{3})\s/', (string)$headers[0], $m)) {
                    $status = (int)$m[1];
                }
            }
        }

        $result['status_code'] = $status;
        $result['provider_response'] = $responseBody;
        $result['ok'] = ($status >= 200 && $status < 300);

        if ($result['ok']) {
            $result['message'] = 'E-mail enviado com sucesso.';
        } else {
            $result['message'] = 'Falha ao enviar e-mail via SendGrid.';
            if ($curlError !== '') {
                $result['message'] .= ' cURL: ' . $curlError;
            }
            error_log('[SendGrid] Falha ao enviar email. HTTP ' . $status . ' | Resposta: ' . $responseBody . ($curlError !== '' ? ' | cURL: ' . $curlError : ''));
        }

        persistSendGridLog($pdo, $result, $toEmail, $fromEmail, $subject);
        return $result;
    } catch (Throwable $e) {
        $result['message'] = 'Exceção ao enviar email: ' . $e->getMessage();
        error_log('[SendGrid] Exceção ao enviar email: ' . $e->getMessage());
        try {
            ensureSendGridLogsTable($pdo);
            persistSendGridLog($pdo, $result, '', '', 'Quadro de Avisos');
        } catch (Throwable $_) {
            // ignore
        }
        return $result;
    }
}

function ensureSendGridLogsTable(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS sendgrid_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            section VARCHAR(80) NULL,
            action_name VARCHAR(80) NULL,
            title VARCHAR(255) NULL,
            to_email VARCHAR(255) NULL,
            from_email VARCHAR(255) NULL,
            subject VARCHAR(255) NULL,
            status_code INT NOT NULL DEFAULT 0,
            success TINYINT(1) NOT NULL DEFAULT 0,
            message TEXT NULL,
            provider_response MEDIUMTEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_sendgrid_logs_created (created_at),
            KEY idx_sendgrid_logs_success (success, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function persistSendGridLog(PDO $pdo, array $result, string $toEmail, string $fromEmail, string $subject): void
{
    $userId = (int)($_SESSION['user_id'] ?? 0);

    $pdo->prepare(
        'INSERT INTO sendgrid_logs (user_id, section, action_name, title, to_email, from_email, subject, status_code, success, message, provider_response)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $userId > 0 ? $userId : null,
        (string)($result['section'] ?? ''),
        (string)($result['action'] ?? ''),
        (string)($result['title'] ?? ''),
        $toEmail !== '' ? $toEmail : null,
        $fromEmail !== '' ? $fromEmail : null,
        $subject !== '' ? $subject : null,
        (int)($result['status_code'] ?? 0),
        !empty($result['ok']) ? 1 : 0,
        (string)($result['message'] ?? ''),
        (string)($result['provider_response'] ?? ''),
    ]);
}
