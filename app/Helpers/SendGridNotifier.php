<?php

declare(strict_types=1);

function sendGridNotifyBoard(PDO $pdo, string $section, string $action, string $title, string $details = ''): array
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
        $result['mode'] = ($port === 2525) ? 'smtp' : 'api';
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

        if ($port === 2525) {
            return sendGridSmtpDispatch(
                apiKey: $apiKey,
                host: 'smtp.sendgrid.net',
                port: $port,
                fromEmail: $fromEmail,
                fromName: $fromName,
                toEmail: $toEmail,
                toName: $companyName,
                subject: $subject,
                textBody: $text,
                htmlBody: $html,
                baseResult: $result
            );
        }

        return sendGridApiDispatch(
            apiKey: $apiKey,
            fromEmail: $fromEmail,
            fromName: $fromName,
            toEmail: $toEmail,
            toName: $companyName,
            subject: $subject,
            textBody: $text,
            htmlBody: $html,
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
    $result['ok'] = ($status >= 200 && $status < 300);
    $result['message'] = $result['ok'] ? 'E-mail enviado com sucesso via API v3.' : 'Falha ao enviar e-mail via API v3.';

    if ($curlError !== '') {
        $result['message'] .= ' cURL: ' . $curlError;
    }

    return $result;
}

function sendGridSmtpDispatch(
    string $apiKey,
    string $host,
    int $port,
    string $fromEmail,
    string $fromName,
    string $toEmail,
    string $toName,
    string $subject,
    string $textBody,
    string $htmlBody,
    array $baseResult
): array {
    $result = $baseResult;
    $result['mode'] = 'smtp';

    $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 20);
    if (!$socket) {
        $result['message'] = 'Falha na conexão SMTP: ' . $errstr;
        $result['provider_response'] = (string)$errno;
        return $result;
    }

    stream_set_timeout($socket, 20);

    $read = static function () use ($socket): string {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (preg_match('/^\d{3}\s/', $line)) {
                break;
            }
        }
        return trim($response);
    };

    $write = static function (string $command) use ($socket): void {
        fwrite($socket, $command . "\r\n");
    };

    $expect = static function (string $response, array $acceptedCodes): bool {
        foreach ($acceptedCodes as $code) {
            if (str_starts_with($response, (string)$code)) {
                return true;
            }
        }
        return false;
    };

    try {
        $banner = $read();
        if (!$expect($banner, [220])) {
            $result['message'] = 'SMTP banner inválido.';
            $result['provider_response'] = $banner;
            fclose($socket);
            return $result;
        }

        $write('EHLO crm-terreiro.local');
        $ehlo = $read();
        if (!$expect($ehlo, [250])) {
            $result['message'] = 'EHLO rejeitado.';
            $result['provider_response'] = $ehlo;
            fclose($socket);
            return $result;
        }

        $write('STARTTLS');
        $startTls = $read();
        if ($expect($startTls, [220])) {
            @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $write('EHLO crm-terreiro.local');
            $ehlo = $read();
        }

        $write('AUTH LOGIN');
        $authStart = $read();
        if (!$expect($authStart, [334])) {
            $result['message'] = 'AUTH LOGIN rejeitado.';
            $result['provider_response'] = $authStart;
            fclose($socket);
            return $result;
        }

        $write(base64_encode('apikey'));
        $authUser = $read();
        if (!$expect($authUser, [334])) {
            $result['message'] = 'Usuário SMTP rejeitado.';
            $result['provider_response'] = $authUser;
            fclose($socket);
            return $result;
        }

        $write(base64_encode($apiKey));
        $authPass = $read();
        if (!$expect($authPass, [235])) {
            $result['message'] = 'Senha SMTP rejeitada.';
            $result['provider_response'] = $authPass;
            $result['status_code'] = 401;
            fclose($socket);
            return $result;
        }

        $write("MAIL FROM:<{$fromEmail}>");
        $mailFrom = $read();
        if (!$expect($mailFrom, [250])) {
            $result['message'] = 'MAIL FROM rejeitado.';
            $result['provider_response'] = $mailFrom;
            fclose($socket);
            return $result;
        }

        $write("RCPT TO:<{$toEmail}>");
        $rcptTo = $read();
        if (!$expect($rcptTo, [250, 251])) {
            $result['message'] = 'RCPT TO rejeitado.';
            $result['provider_response'] = $rcptTo;
            fclose($socket);
            return $result;
        }

        $write('DATA');
        $dataResp = $read();
        if (!$expect($dataResp, [354])) {
            $result['message'] = 'DATA rejeitado.';
            $result['provider_response'] = $dataResp;
            fclose($socket);
            return $result;
        }

        $boundary = 'crm_' . md5((string)microtime(true));
        $headers = [
            'From: ' . formatMailbox($fromEmail, $fromName),
            'To: ' . formatMailbox($toEmail, $toName),
            'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
            'Date: ' . date(DATE_RFC2822),
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];

        $body = "--{$boundary}\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n\r\n"
            . $textBody . "\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n\r\n"
            . $htmlBody . "\r\n"
            . "--{$boundary}--\r\n";

        $rawMail = implode("\r\n", $headers) . "\r\n\r\n" . $body;
        fwrite($socket, $rawMail . "\r\n.\r\n");

        $queued = $read();
        $result['provider_response'] = $queued;
        $result['ok'] = $expect($queued, [250]);
        $result['status_code'] = $result['ok'] ? 250 : 500;
        $result['message'] = $result['ok'] ? 'E-mail enviado com sucesso via SMTP.' : 'Falha ao enviar e-mail via SMTP.';

        $write('QUIT');
        $read();
        fclose($socket);

        return $result;
    } catch (Throwable $e) {
        $result['message'] = 'Exceção no envio SMTP: ' . $e->getMessage();
        fclose($socket);
        return $result;
    }
}

function formatMailbox(string $email, string $name): string
{
    $safeName = trim($name) !== '' ? str_replace(['"', "\r", "\n"], '', $name) : '';
    if ($safeName === '') {
        return '<' . $email . '>';
    }
    return '"' . $safeName . '" <' . $email . '>';
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
