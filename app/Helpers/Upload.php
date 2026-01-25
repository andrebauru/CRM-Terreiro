<?php

declare(strict_types=1);

namespace App\Helpers;

use Ramsey\Uuid\Uuid; // Assuming Ramsey/Uuid is installed via composer. If not, this needs to be installed or replaced with a custom UUID generator.

class Upload
{
    /**
     * Handles a single file upload.
     *
     * @param array $file The $_FILES array for a single file input.
     * @param string $uploadDir The base directory to store uploads (e.g., UPLOAD_PATH).
     * @param array $allowedMimeTypes Allowed MIME types (e.g., ['image/png', 'image/jpeg']).
     * @param int $maxFileSize Max file size in bytes.
     * @return array|false An array containing 'filename', 'filepath', 'file_type', 'file_size' on success, or false on failure.
     */
    public static function handleUpload(array $file, string $uploadDir, array $allowedMimeTypes, int $maxFileSize): array|false
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            Session::flash('error', 'Parâmetros de upload inválidos.');
            return false;
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                // No file was uploaded, this might be acceptable depending on context
                return false;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                Session::flash('error', 'O arquivo excedeu o tamanho máximo permitido.');
                return false;
            default:
                Session::flash('error', 'Erro desconhecido no upload do arquivo.');
                return false;
        }

        // Check file size
        if ($file['size'] > $maxFileSize) {
            Session::flash('error', 'O arquivo é muito grande. Máximo ' . ($maxFileSize / 1024 / 1024) . 'MB.');
            return false;
        }

        // Check file type
        if (!in_array(mime_content_type($file['tmp_name']), $allowedMimeTypes)) {
            Session::flash('error', 'Tipo de arquivo não permitido.');
            return false;
        }

        // Create year/month directory
        $year = date('Y');
        $month = date('m');
        $targetDir = $uploadDir . '/' . $year . '/' . $month;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true); // Create directory recursively
        }

        // Generate a unique filename using UUID v4
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFilename = Uuid::uuid4()->toString() . '.' . $extension;
        $filepath = $targetDir . '/' . $newFilename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            Session::flash('error', 'Falha ao mover o arquivo enviado.');
            return false;
        }

        return [
            'filename' => $file['name'], // Original filename
            'filepath' => str_replace(BASE_PATH . '/', '', $filepath), // Relative path to BASE_PATH
            'file_type' => mime_content_type($file['tmp_name']),
            'file_size' => $file['size'],
        ];
    }
}