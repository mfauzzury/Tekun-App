<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Http\UploadedFile;

trait ValidatesPermohonanDocumentUpload
{
    /**
     * @return list<callable>
     */
    protected function permohonanDocumentFileRule(?int $maxKb = null, bool $imagesOnly = false): array
    {
        $maxKb ??= (int) config('sppt.permohonan_document_max_kb', 10240);
        $maxMb = (int) round($maxKb / 1024);

        return [
            function (string $attribute, mixed $value, \Closure $fail) use ($maxKb, $maxMb, $imagesOnly): void {
                $uploaded = $this->file('file');

                if (! $uploaded instanceof UploadedFile) {
                    $errorCode = (int) ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE);
                    if ($errorCode !== UPLOAD_ERR_OK) {
                        $fail($this->permohonanUploadErrorMessage($errorCode));

                        return;
                    }

                    $fail('Fail diperlukan.');

                    return;
                }

                if (! $uploaded->isValid()) {
                    $fail($this->permohonanUploadErrorMessage($uploaded->getError()));

                    return;
                }

                if ($uploaded->getSize() > ($maxKb * 1024)) {
                    $fail("Saiz fail melebihi had {$maxMb}MB setiap fail.");

                    return;
                }

                if (! $this->isAllowedPermohonanDocument($uploaded, $imagesOnly)) {
                    $fail($this->permohonanDocumentTypeError($uploaded, $imagesOnly));
                }
            },
        ];
    }

    protected function isAllowedPermohonanDocument(UploadedFile $file, bool $imagesOnly = false): bool
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $mime = strtolower($file->getMimeType() ?? '');

        $imageExtensions = ['jpg', 'jpeg', 'png'];
        $documentExtensions = ['pdf', 'doc', 'docx'];
        $allowedExtensions = $imagesOnly
            ? $imageExtensions
            : [...$documentExtensions, ...$imageExtensions];

        if (in_array($ext, $allowedExtensions, true)) {
            if ($mime === '' || $mime === 'application/octet-stream') {
                return true;
            }

            if ($this->isAllowedPermohonanDocumentMime($mime, $imagesOnly)) {
                return true;
            }
        }

        return $this->isAllowedPermohonanDocumentMime($mime, $imagesOnly);
    }

    protected function isAllowedPermohonanDocumentMime(string $mime, bool $imagesOnly = false): bool
    {
        $imageMimes = [
            'image/jpeg',
            'image/jpg',
            'image/pjpeg',
            'image/png',
            'image/x-png',
        ];

        if ($imagesOnly) {
            return in_array($mime, $imageMimes, true);
        }

        return in_array($mime, [
            ...$imageMimes,
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ], true);
    }

    protected function permohonanDocumentTypeError(UploadedFile $file, bool $imagesOnly = false): string
    {
        $label = $file->getClientOriginalExtension() ?: ($file->getMimeType() ?: 'tidak diketahui');

        if ($imagesOnly) {
            return "Format imej tidak disokong ({$label}). Sila gunakan JPG, JPEG, atau PNG.";
        }

        return "Format fail tidak disokong ({$label}). Sila gunakan PDF, DOC, DOCX, JPG, JPEG, atau PNG.";
    }

    protected function permohonanUploadErrorMessage(int $errorCode): string
    {
        $serverLimit = ini_get('upload_max_filesize');

        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "Saiz fail melebihi had pelayan (maks. {$serverLimit}). Imej MyKad biasanya 2–5MB — mulakan semula pelayan dev dengan `composer dev:win` atau mampatkan imej.",
            UPLOAD_ERR_PARTIAL => 'Muat naik fail tidak lengkap. Sila cuba lagi.',
            UPLOAD_ERR_NO_FILE => 'Tiada fail diterima oleh pelayan. Sila pilih semula fail dan cuba lagi.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'Ralat pelayan semasa menyimpan fail sementara. Hubungi pentadbir sistem.',
            UPLOAD_ERR_EXTENSION => 'Jenis fail disekat oleh pelayan.',
            default => 'Muat naik fail gagal. Sila cuba lagi atau pilih format JPG/PNG/PDF.',
        };
    }
}
