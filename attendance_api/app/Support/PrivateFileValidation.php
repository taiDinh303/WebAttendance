<?php

namespace App\Support;

use App\Exceptions\ApiException;
use Illuminate\Http\UploadedFile;
use ZipArchive;

final class PrivateFileValidation
{
    public function assess(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();
        $size = $file->getSize();
        $path = $file->getRealPath();
        if (! in_array($ext, ['pdf', 'docx', 'xlsx', 'pptx', 'jpg', 'jpeg', 'png', 'txt'], true) || ! $size || $size > 20 * 1024 * 1024) {
            throw new ApiException('INVALID_FILE', 'Use an allowed file type between 1 byte and 20 MiB.', 422);
        }
        if (in_array($ext, ['docx', 'xlsx', 'pptx'], true)) {
            $this->office($path, $ext);
            $mime = ['docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'][$ext];
        } elseif ($ext === 'pdf') {
            if ($mime !== 'application/pdf' || ! str_starts_with(file_get_contents($path, false, null, 0, 8), '%PDF-')) {
                throw new ApiException('INVALID_FILE', 'The file content does not match PDF.', 422);
            }
        } elseif (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            $info = @getimagesize($path);
            $expected = $ext === 'png' ? 'image/png' : 'image/jpeg';
            if ($mime !== $expected || ! $info || $info['mime'] !== $expected || $info[0] * $info[1] > 50000000) {
                throw new ApiException('INVALID_FILE', 'Invalid or oversized image content.', 422);
            }
        } else {
            $content = file_get_contents($path);
            if (! in_array($mime, ['text/plain', 'application/x-empty'], true) || str_contains($content, "\0") || ! mb_check_encoding($content, 'UTF-8')) {
                throw new ApiException('INVALID_FILE', 'TXT files must contain UTF-8 text.', 422);
            }
        }
        $mode = config('filesafety.scanner');
        if ($mode === 'clamav') {
            $this->antivirus($path);
            $scan = 'clean';
        } elseif ($mode === 'local_validation' && app()->environment(['local', 'demo', 'testing', 'http-smoke'])) {
            $scan = 'validated_local';
        } else {
            throw new ApiException('FILE_SCANNER_UNAVAILABLE', 'A configured antivirus scanner is required in this environment.', 503);
        }

        return ['extension' => $ext, 'mimeType' => $mime, 'sizeBytes' => $size, 'scanState' => $scan, 'sha256' => hash_file('sha256', $path)];
    }

    public function requireApproved(string $scanState): void
    {
        if ($scanState !== 'clean' && ! ($scanState === 'validated_local' && app()->environment(['local', 'demo', 'testing', 'http-smoke']))) {
            throw new ApiException('FILE_SCANNER_UNAVAILABLE', 'This file has not passed the required scanner.', 503);
        }
    }

    private function office(string $path, string $ext): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new ApiException('INVALID_FILE', 'Invalid Office document container.', 422);
        }
        try {
            $main = ['docx' => 'word/document.xml', 'xlsx' => 'xl/workbook.xml', 'pptx' => 'ppt/presentation.xml'][$ext];
            if ($zip->locateName('[Content_Types].xml') === false || $zip->locateName($main) === false || $zip->numFiles > 2000) {
                throw new ApiException('INVALID_FILE', 'The Office document structure is invalid.', 422);
            }
            $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                $name = str_replace('\\', '/', $entry['name']);
                $total += $entry['size'];
                if (str_starts_with($name, '/') || preg_match('~(^|/)\.\.(/|$)|^[A-Za-z]:~', $name)
                    || str_contains(strtolower($name), 'vbaproject') || $total > 100 * 1024 * 1024
                    || ($entry['size'] > 0 && $entry['size'] / max(1, $entry['comp_size']) > 1000)) {
                    throw new ApiException('INVALID_FILE', 'Unsafe or oversized Office document container.', 422);
                }
            }
        } finally {
            $zip->close();
        }
    }

    private function antivirus(string $path): void
    {
        $socket = @stream_socket_client('tcp://'.config('filesafety.clamav_host').':'.config('filesafety.clamav_port'), $errorNumber, $errorText, 5);
        if (! $socket) {
            throw new ApiException('FILE_SCANNER_UNAVAILABLE', 'The antivirus scanner is unavailable.', 503);
        }
        $input = fopen($path, 'rb');
        if ($input === false) {
            fclose($socket);
            throw new ApiException('FILE_SCANNER_UNAVAILABLE', 'Unable to read upload for scanning.', 503);
        }
        try {
            stream_set_timeout($socket, 15);
            $this->write($socket, "zINSTREAM\0");
            while (! feof($input)) {
                $chunk = fread($input, 65536);
                if ($chunk === false) {
                    throw new ApiException('FILE_SCANNER_UNAVAILABLE', 'Unable to read upload for scanning.', 503);
                }
                if ($chunk !== '') {
                    $this->write($socket, pack('N', strlen($chunk)).$chunk);
                }
            }
            $this->write($socket, pack('N', 0));
            $result = stream_get_line($socket, 4096, "\0");
            if (is_string($result) && str_contains($result, 'FOUND')) {
                throw new ApiException('UNSAFE_FILE', 'The file was rejected by the scanner.', 422);
            }
            if (! is_string($result) || trim($result) !== 'stream: OK') {
                throw new ApiException('FILE_SCANNER_UNAVAILABLE', 'The scanner did not confirm this file.', 503);
            }
        } finally {
            fclose($input);
            fclose($socket);
        }
    }

    private function write($stream, string $data): void
    {
        while ($data !== '') {
            $written = fwrite($stream, $data);
            if ($written === false || $written === 0) {
                throw new ApiException('FILE_SCANNER_UNAVAILABLE', 'The scanner connection failed.', 503);
            }
            $data = substr($data, $written);
        }
    }
}
