<?php

declare(strict_types=1);

function sendGridNotifyBoard(
    PDO $pdo,
    string $section,
    string $action,
    string $title,
    string $details = '',
    ?string $ctaLink = null,
    ?string $imagePath = null
): array
{
    $result = [
        'ok' => false,
        'status_code' => 0,
        'message' => '',
        'provider_response' => '',
        'mode' => 'api',
        'port' => 443,
        'to_email' => '',
        'from_email' => '',
        'section' => $section,
        'action' => $action,
        'title' => $title,
    ];

    try {
        $settings = $pdo->query('SELECT company_name, sendgrid_api_key, notification_email, sendgrid_from_name, sendgrid_port FROM settings ORDER BY id ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC) ?: [];

        $apiKey = trim((string)($settings['sendgrid_api_key'] ?? ($_ENV['SENDGRID_API_KEY'] ?? '')));
        $toEmail = trim((string)($settings['notification_email'] ?? ($_ENV['NOTIFICATION_EMAIL'] ?? '')));
        $companyName = trim((string)($settings['company_name'] ?? 'CRM Terreiro')) ?: 'CRM Terreiro';
        $fromName = trim((string)($settings['sendgrid_from_name'] ?? $companyName));
        $fromEmail = trim((string)($_ENV['EMAIL_FROM'] ?? ''));

        $envPortRaw = trim((string)($_ENV['SENDGRID_PORT'] ?? $_ENV['SMTP_PORT'] ?? $_ENV['MAIL_PORT'] ?? ''));
        $port = $envPortRaw !== '' ? (int)$envPortRaw : (int)($settings['sendgrid_port'] ?? 443);
        if ($port <= 0) {
            $port = 443;
        }

        $result['port'] = $port;
        $result['mode'] = 'api';
        $result['to_email'] = $toEmail;
        $result['from_email'] = $fromEmail;

        if ($apiKey === '' || $toEmail === '' || $fromEmail === '') {
            $result['message'] = 'Configuração incompleta: API Key, e-mail destino e EMAIL_FROM no .env são obrigatórios.';
            return $result;
        }

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $result['message'] = 'E-mail de destino inválido.';
            return $result;
        }

        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $result['message'] = 'EMAIL_FROM inválido no .env.';
            return $result;
        }

        $actionLabel = [
            'create' => 'Criado',
            'update' => 'Atualizado',
            'delete' => 'Removido',
            'test' => 'Teste',
        ][$action] ?? 'Atualizado';

        $sectionBase = trim($section) ?: 'AVISOS';
        $sectionUpper = function_exists('mb_strtoupper')
            ? mb_strtoupper($sectionBase, 'UTF-8')
            : strtoupper($sectionBase);
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeDetails = nl2br(htmlspecialchars($details, ENT_QUOTES, 'UTF-8'));
        $safeCtaLink = trim((string)$ctaLink);
        if ($safeCtaLink !== '' && !preg_match('~^https?://~i', $safeCtaLink)) {
            $safeCtaLink = '';
        }

        $subject = 'Quadro de Avisos';
        $text = "{$sectionUpper} | {$actionLabel}\nTítulo: {$title}";
        if ($details !== '') {
            $text .= "\n\n{$details}";
        }
        if ($safeCtaLink !== '') {
            $text .= "\n\nLink: {$safeCtaLink}";
        }

        $inlineContentId = 'img_notificacao';
        $inlineHtmlImage = '';
        $attachments = [];
        if ($imagePath !== null && trim($imagePath) !== '') {
            $resolvedImage = resolveEmailImagePath((string)$imagePath);
            if ($resolvedImage !== null && is_file($resolvedImage) && is_readable($resolvedImage)) {
                $binary = @file_get_contents($resolvedImage);
                if ($binary !== false) {
                    $mime = detectMimeTypeByPath($resolvedImage) ?: 'image/png';
                    $filename = basename($resolvedImage);
                    $attachments[] = [
                        'content' => base64_encode($binary),
                        'type' => $mime,
                        'filename' => $filename !== '' ? $filename : 'notificacao.png',
                        'disposition' => 'inline',
                        'content_id' => $inlineContentId,
                    ];
                    $inlineHtmlImage = '<div style="margin:0 0 16px 0"><img src="cid:' . $inlineContentId . '" alt="Imagem" style="max-width:100%;height:auto;border-radius:12px;border:1px solid #e2e8f0"></div>';
                }
            }
        }

        $ctaHtml = '';
        if ($safeCtaLink !== '') {
            $safeUrl = htmlspecialchars($safeCtaLink, ENT_QUOTES, 'UTF-8');
            $ctaHtml = '<div style="margin:18px 0 0 0"><a href="' . $safeUrl . '" style="display:inline-block;background:#e12127;color:#ffffff;text-decoration:none;padding:10px 16px;border-radius:10px;font-weight:700">Abrir aviso</a></div>';
        }

        $html = "
            <div style=\"font-family:Arial,sans-serif;color:#0f172a;line-height:1.5\">
                <h1 style=\"margin:0 0 12px 0;font-size:20px\">Quadro de Avisos</h1>
                {$inlineHtmlImage}
                <h2 style=\"margin:0 0 8px 0;font-size:18px;color:#e12127;font-weight:800\">{$sectionUpper}</h2>
                <p style=\"margin:0 0 8px 0\"><strong>Ação:</strong> {$actionLabel}</p>
                <p style=\"margin:0 0 8px 0\"><strong>Título:</strong> <span style=\"color:#e12127;font-weight:800\">{$safeTitle}</span></p>
                " . ($safeDetails !== '' ? "<p style=\"margin:0 0 12px 0\"><strong>Detalhes:</strong><br>{$safeDetails}</p>" : '') . "
                {$ctaHtml}
                <hr style=\"border:none;border-top:1px solid #e2e8f0;margin:16px 0\">
                <p style=\"margin:0;color:#64748b;font-size:12px\">{$companyName}</p>
            </div>
        ";
        // Mesmo com porta 2525 configurada, este helper usa API v3 por cURL.
        return sendGridApiDispatch(
            apiKey: $apiKey,
            fromEmail: $fromEmail,
            fromName: $fromName,
            toEmail: $toEmail,
            toName: $companyName,
            subject: $subject,
            textBody: $text,
            htmlBody: $html,
            attachments: $attachments,
            baseResult: $result
        );
    } catch (Throwable $e) {
        $result['message'] = 'Exceção ao enviar e-mail: ' . $e->getMessage();
        error_log('[SendGrid] Exceção: ' . $e->getMessage());
        return $result;
    }
}

function sendGridApiDispatch(
    string $apiKey,
    string $fromEmail,
    string $fromName,
    string $toEmail,
    string $toName,
    string $subject,
    string $textBody,
    string $htmlBody,
    array $attachments,
    array $baseResult
): array {
    $result = $baseResult;

    $payload = [
        'personalizations' => [[
            'to' => [[
                'email' => $toEmail,
                'name' => $toName,
            ]],
            'subject' => $subject,
        ]],
        'from' => [
            'email' => $fromEmail,
            'name' => $fromName,
        ],
        'content' => [
            ['type' => 'text/plain', 'value' => $textBody],
            ['type' => 'text/html', 'value' => $htmlBody],
        ],
    ];

    if (!empty($attachments)) {
        $payload['attachments'] = $attachments;
    }

    $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($jsonPayload === false) {
        $result['message'] = 'Falha ao serializar payload JSON da API.';
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
    $result['ok'] = ($status === 202);
    $result['message'] = $result['ok'] ? 'E-mail enviado com sucesso via API v3.' : 'Falha ao enviar e-mail via API v3. HTTP ' . $status;

    if ($curlError !== '') {
        $result['message'] .= ' cURL: ' . $curlError;
    }

    return $result;
}

function resolveEmailImagePath(string $path): ?string
{
    $candidate = trim($path);
    if ($candidate === '') {
        return null;
    }

    if (preg_match('~^[A-Za-z]:[\\/]~', $candidate) || str_starts_with($candidate, '/')) {
        return $candidate;
    }

    $basePath = dirname(__DIR__, 2);
    $cleanRelative = ltrim(str_replace('../', '', str_replace('..\\', '', $candidate)), '/\\');
    return $basePath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cleanRelative);
}

function detectMimeTypeByPath(string $path): ?string
{
    if (function_exists('mime_content_type')) {
        $mime = @mime_content_type($path);
        if (is_string($mime) && $mime !== '') {
            return $mime;
        }
    }

    $ext = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
    return match ($ext) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        default => null,
    };
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
