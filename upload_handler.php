<?php

declare(strict_types=1);

require_once __DIR__ . '/api/_auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'message' => 'Método não permitido'], 405);
}

try {
    if (!isset($_FILES['chat_image'])) {
        jsonResponse(['ok' => false, 'message' => 'Nenhum arquivo enviado'], 422);
    }

    $file = $_FILES['chat_image'];
    if (!is_array($file)) {
        jsonResponse(['ok' => false, 'message' => 'Upload inválido'], 422);
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        jsonResponse(['ok' => false, 'message' => 'Falha no upload do arquivo'], 422);
    }

    $size = (int)($file['size'] ?? 0);
    $maxSize = 8 * 1024 * 1024;
    if ($size <= 0 || $size > $maxSize) {
        jsonResponse(['ok' => false, 'message' => 'Arquivo excede o limite de 8MB'], 422);
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        jsonResponse(['ok' => false, 'message' => 'Arquivo temporário inválido'], 422);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($tmpName);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'audio/webm' => 'webm',
        'audio/ogg' => 'ogg',
        'audio/mpeg' => 'mp3',
        'audio/wav' => 'wav',
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/quicktime' => 'mov',
    ];

    if (!isset($allowed[$mime])) {
        jsonResponse(['ok' => false, 'message' => 'Tipo de arquivo não permitido'], 422);
    }

    $uploadDir = __DIR__ . '/chat_uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Não foi possível criar a pasta de upload');
    }

    $random = bin2hex(random_bytes(8));
    $baseName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', pathinfo((string)($file['name'] ?? 'arquivo'), PATHINFO_FILENAME));
    $baseName = trim((string)$baseName, '-_');
    if ($baseName === '') {
        $baseName = 'arquivo';
    }

    $fileName = sprintf('%s_%s.%s', date('Ymd_His'), $random, $allowed[$mime]);
    $destination = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($tmpName, $destination)) {
        throw new RuntimeException('Não foi possível salvar o arquivo enviado');
    }

    @chmod($destination, 0644);

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'crm.quimbanda.jp');
    $relativePath = '/chat_uploads/' . $fileName;
    $publicUrl = $scheme . '://' . $host . $relativePath;

    jsonResponse([
        'ok' => true,
        'message' => 'Upload concluído com sucesso',
        'path' => $relativePath,
        'url' => $publicUrl,
        'mime' => $mime,
        'originalName' => $baseName,
        'size' => $size,
    ]);
} catch (Throwable $e) {
    safeJsonError($e, 500);
}
