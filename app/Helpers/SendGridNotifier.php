<?php

declare(strict_types=1);

function sendGridNotifyBoard(PDO $pdo, string $section, string $action, string $title, string $details = ''): void
{
    try {
        $settings = $pdo->query('SELECT company_name, sendgrid_api_key, notification_email FROM settings ORDER BY id ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC) ?: [];

        $apiKey = trim((string)($settings['sendgrid_api_key'] ?? ''));
        $email = trim((string)($settings['notification_email'] ?? ''));
        $companyName = trim((string)($settings['company_name'] ?? 'CRM Terreiro')) ?: 'CRM Terreiro';

        if ($apiKey === '' || $email === '') {
            return;
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
                    'email' => $email,
                    'name' => $companyName,
                ]],
                'subject' => $subject,
            ]],
            'from' => [
                'email' => $email,
                'name' => $companyName,
            ],
            'content' => [
                ['type' => 'text/plain', 'value' => $text],
                ['type' => 'text/html', 'value' => $html],
            ],
        ];

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($jsonPayload === false) {
            return;
        }

        $status = 0;
        $responseBody = '';

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

            $responseBody = (string)curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Authorization: Bearer {$apiKey}\r\nContent-Type: application/json\r\n",
                    'content' => $jsonPayload,
                    'timeout' => 20,
                ],
            ]);
            $responseBody = (string)@file_get_contents('https://api.sendgrid.com/v3/mail/send', false, $context);
            $status = 202;
        }

        if ($status < 200 || $status >= 300) {
            error_log('[SendGrid] Falha ao enviar email. HTTP ' . $status . ' | Resposta: ' . $responseBody);
        }
    } catch (Throwable $e) {
        error_log('[SendGrid] Exceção ao enviar email: ' . $e->getMessage());
    }
}
