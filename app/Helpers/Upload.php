<?php

declare(strict_types=1);

namespace App\Helpers;

use Ramsey\Uuid\Uuid; // Assuming Ramsey/Uuid is installed via composer. If not, this needs to be installed or replaced with a custom UUID generator.

class Upload
{
    // Constante para o tamanho máximo permitido de upload de imagens (6MB)
    private const MAX_UPLOAD_IMAGE_SIZE = 6 * 1024 * 1024; // 6MB

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

        // Detect MIME type securely
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
        if ($finfo) {
            finfo_close($finfo);
        }

        // Check file type
        if (!$detectedMime || !in_array($detectedMime, $allowedMimeTypes, true)) {
            Session::flash('error', 'Tipo de arquivo não permitido.');
            return false;
        }

        // Check file extension against allowed MIME types
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mimeToExt = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/webp' => 'webp',
        ];
        $allowedExtensions = array_values(array_unique(array_intersect_key($mimeToExt, array_flip($allowedMimeTypes))));
        if (empty($extension) || !in_array($extension, $allowedExtensions, true)) {
            Session::flash('error', 'Extensão de arquivo não permitida.');
            return false;
        }

        // Create year/month directory
        $year = date('Y');
        $month = date('m');
        $targetDir = $uploadDir . '/' . $year . '/' . $month;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Generate a unique filename using UUID v4
        $newFilename = Uuid::uuid4()->toString() . '.' . $extension;
        $filepath = $targetDir . '/' . $newFilename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            Session::flash('error', 'Falha ao mover o arquivo enviado.');
            return false;
        }

        return [
            'filename' => $file['name'], // Original filename
            'filepath' => str_replace(BASE_PATH . '/', '', $filepath), // Relative path to BASE_PATH
            'file_type' => $detectedMime,
            'file_size' => $file['size'],
        ];
    }

    /**
     * Handles an image upload and compresses to a target max size in bytes.
     * The resulting file is saved as JPEG with a unique filename.
     *
     * @param array $file The $_FILES array for a single file input.
     * @param string $uploadDir Base upload directory.
     * @param bool $useDateFolders Whether to create year/month folders.
     * @return array|false
     */
    public static function handleCompressedImageUpload(
        array $file,
        string $uploadDir,
        bool $useDateFolders = true
    ): array|false {
        if (!isset($file['error']) || is_array($file['error'])) {
            Session::flash('error', 'Parâmetros de upload inválidos.');
            return false;
        }

        if (!extension_loaded('gd')) {
            Session::flash('error', 'Extensão GD não disponível para converter imagens.');
            return false;
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return false;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                Session::flash('error', 'O arquivo excedeu o tamanho máximo permitido de ' . (self::MAX_UPLOAD_IMAGE_SIZE / 1024 / 1024) . 'MB.');
                return false;
            default:
                Session::flash('error', 'Erro desconhecido no upload do arquivo.');
                return false;
        }

        // Validação de MIME type seguro
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowedImageMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!$detectedMime || !in_array($detectedMime, $allowedImageMimeTypes, true)) {
            Session::flash('error', 'Tipo de arquivo de imagem não permitido: ' . ($detectedMime ?? 'desconhecido') . '. Tipos permitidos: JPEG, PNG, WebP.');
            return false;
        }

        // Verifica o tamanho do arquivo antes de tentar ler
        if ($file['size'] > self::MAX_UPLOAD_IMAGE_SIZE) {
            Session::flash('error', 'O arquivo de imagem é muito grande. Máximo ' . (self::MAX_UPLOAD_IMAGE_SIZE / 1024 / 1024) . 'MB.');
            return false;
        }


        $imageData = @file_get_contents($file['tmp_name']);
        if ($imageData === false) {
            Session::flash('error', 'Falha ao ler a imagem enviada.');
            return false;
        }

        $srcImage = @imagecreatefromstring($imageData);
        if (!$srcImage) {
            Session::flash('error', 'Arquivo enviado não é uma imagem válida ou formato não suportado pela GD.');
            return false;
        }

        $width = imagesx($srcImage);
        $height = imagesy($srcImage);

        $year = date('Y');
        $month = date('m');
        $targetDir = $uploadDir;
        if ($useDateFolders) {
            $targetDir = $uploadDir . '/' . $year . '/' . $month;
        }

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Gerar um nome de arquivo único usando UUID v4
        $newFilename = Uuid::uuid4()->toString() . '.jpg';
        $filepath = $targetDir . '/' . $newFilename;
        
        $scale = 1.0;
        $quality = 85;
        $attempts = 0;
        $binary = null;

        // Limite máximo em bytes para a imagem final
        $maxOutputBytes = self::MAX_UPLOAD_IMAGE_SIZE;

        while ($attempts < 15) {
            $attempts++;

            $newWidth = max(1, (int) round($width * $scale));
            $newHeight = max(1, (int) round($height * $scale));

            $dstImage = imagecreatetruecolor($newWidth, $newHeight);
            $white = imagecolorallocate($dstImage, 255, 255, 255);
            imagefill($dstImage, 0, 0, $white);
            imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            ob_start();
            imagejpeg($dstImage, null, $quality);
            $binary = ob_get_clean();
            imagedestroy($dstImage);

            if (is_string($binary) && strlen($binary) <= $maxOutputBytes) {
                break;
            }

            if ($quality > 30) {
                $quality -= 10;
            } else {
                $scale *= 0.8;
                if ($scale < 0.2) {
                    break;
                }
            }
        }

        imagedestroy($srcImage);

        if (!is_string($binary) || strlen($binary) > $maxOutputBytes) {
            Session::flash('error', 'Não foi possível reduzir a imagem para menos de ' . (self::MAX_UPLOAD_IMAGE_SIZE / 1024 / 1024) . 'MB.');
            return false;
        }

        if (file_put_contents($filepath, $binary) === false) {
            Session::flash('error', 'Falha ao salvar a imagem processada.');
            return false;
        }

        return [
            'filename' => $newFilename,
            'filepath' => str_replace(BASE_PATH . '/', '', $filepath),
            'file_type' => 'image/jpeg',
            'file_size' => strlen($binary),
        ];
    }
}