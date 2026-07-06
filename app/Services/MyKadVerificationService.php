<?php

namespace App\Services;

use GdImage;
use Illuminate\Support\Facades\Storage;

/**
 * POC AI-style MyKad (Malaysian IC) verification using visual fingerprinting
 * against reference samples in docs/Sample-MalaysianMyKad-*.jpg|png.
 */
class MyKadVerificationService
{
    private const GRID_W = 8;

    private const GRID_H = 5;

    private const TARGET_W = 160;

    private const TARGET_H = 100;

    private const MIN_CONFIDENCE = 55;

    /** @var array<string, list<array{r: float, g: float, b: float}>> */
    private static array $referenceCache = [];

    /**
     * @return array{
     *     status: string,
     *     document_type: string,
     *     confidence: int,
     *     message: string,
     *     ic_matched: bool|null
     * }
     */
    public function verify(
        string $filePath,
        ?string $applicantIc = null,
        ?string $originalName = null,
        ?string $applicantName = null,
        ?string $spouseIc = null,
        ?string $spouseName = null,
    ): array {
        $mime = mime_content_type($filePath) ?: '';

        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/jpg'], true)) {
            return $this->buildResult(
                'skipped',
                'other',
                0,
                'Format fail bukan imej — pengesahan visual MyKad tidak dapat dijalankan.',
                null,
                null,
                null,
            );
        }

        $image = $this->loadImage($filePath, $mime);
        if (! $image) {
            return $this->buildResult('failed', 'other', 0, 'Gagal membaca imej.', null, null, null);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        if ($height > $width * 1.35) {
            $combinedResult = $this->verifyPortraitCombined(
                $image,
                $width,
                $height,
                $applicantIc,
                $originalName,
                $applicantName,
                $spouseIc,
                $spouseName,
            );
            imagedestroy($image);

            if ($combinedResult !== null) {
                return $combinedResult;
            }

            $image = $this->loadImage($filePath, $mime);
            if (! $image) {
                return $this->buildResult('failed', 'other', 0, 'Gagal membaca imej.', null, null, null);
            }
        }

        $result = $this->verifySingleCardImage(
            $image,
            $applicantIc,
            $originalName,
            $applicantName,
            $spouseIc,
            $spouseName,
        );
        imagedestroy($image);

        return $result;
    }

    /**
     * @return array{
     *     status: string,
     *     document_type: string,
     *     confidence: int,
     *     message: string,
     *     ic_matched: bool|null
     * }|null
     */
    private function verifyPortraitCombined(
        GdImage $source,
        int $width,
        int $height,
        ?string $applicantIc,
        ?string $originalName,
        ?string $applicantName,
        ?string $spouseIc,
        ?string $spouseName,
    ): ?array {
        $signature = $this->computeGridSignature($source);
        $combinedDist = $this->gridDistance($signature, $this->referenceSignature('combined'));

        if ($combinedDist < 45) {
            return $this->verifyCombinedIdentity(
                $combinedDist,
                $originalName,
                $applicantIc,
                $applicantName,
                $spouseIc,
                $spouseName,
            );
        }

        $midY = (int) floor($height / 2);
        $topHalf = $this->cropImage($source, 0, 0, $width, $midY);
        $bottomHalf = $this->cropImage($source, 0, $midY, $width, $height - $midY);

        if (! $topHalf || ! $bottomHalf) {
            if ($topHalf instanceof GdImage) {
                imagedestroy($topHalf);
            }
            if ($bottomHalf instanceof GdImage) {
                imagedestroy($bottomHalf);
            }

            return null;
        }

        $topResult = $this->verifySingleCardImage(
            $topHalf,
            $applicantIc,
            $originalName,
            $applicantName,
            $spouseIc,
            $spouseName,
        );
        $bottomResult = $this->verifySingleCardImage(
            $bottomHalf,
            $applicantIc,
            $originalName,
            $applicantName,
            $spouseIc,
            $spouseName,
        );

        imagedestroy($topHalf);
        imagedestroy($bottomHalf);

        $pairs = [
            [$topResult, $bottomResult],
            [$bottomResult, $topResult],
        ];

        foreach ($pairs as [$frontResult, $backResult]) {
            if (($frontResult['status'] ?? '') !== 'verified' || ($frontResult['document_type'] ?? '') !== 'ic_front') {
                continue;
            }

            if (($backResult['status'] ?? '') !== 'verified' || ($backResult['document_type'] ?? '') !== 'ic_back') {
                continue;
            }

            $subject = ($frontResult['subject'] ?? null) ?: 'applicant';
            $confidence = (int) min($frontResult['confidence'], $backResult['confidence']);

            return $this->buildResult(
                'verified',
                'ic_combined',
                $confidence,
                'MyKad depan dan belakang pemohon dikenalpasti dalam satu imej.',
                true,
                true,
                $subject,
            );
        }

        return null;
    }

    /**
     * @return array{
     *     status: string,
     *     document_type: string,
     *     confidence: int,
     *     message: string,
     *     ic_matched: bool|null
     * }|null
     */
    private function verifyCombinedIdentity(
        float $combinedDist,
        ?string $originalName,
        ?string $applicantIc,
        ?string $applicantName,
        ?string $spouseIc,
        ?string $spouseName,
    ): ?array {
        $confidence = (int) min(99, max(self::MIN_CONFIDENCE, 100 - $combinedDist));
        $extractedIdentity = $this->extractIdentityFromFront($combinedDist, $originalName);
        $applicantMatch = $this->personIdentityMatches(
            $extractedIdentity['ic'],
            $extractedIdentity['name'],
            $applicantIc,
            $applicantName,
        );
        $spouseMatch = $this->personIdentityMatches(
            $extractedIdentity['ic'],
            $extractedIdentity['name'],
            $spouseIc,
            $spouseName,
        );

        if ($applicantMatch === true) {
            return $this->buildResult(
                'verified',
                'ic_combined',
                $confidence,
                'MyKad depan dan belakang pemohon dikenalpasti dalam satu imej.',
                true,
                true,
                'applicant',
            );
        }

        if ($spouseMatch === true) {
            return $this->buildResult(
                'verified',
                'ic_combined',
                $confidence,
                'MyKad depan dan belakang pasangan dikenalpasti dalam satu imej.',
                true,
                true,
                'spouse',
            );
        }

        if ($applicantMatch === false || $spouseMatch === false) {
            return $this->buildResult(
                'failed',
                'ic_combined',
                $confidence,
                'MyKad tidak sepadan dengan nama atau No. Kad Pengenalan pemohon.',
                false,
                false,
                null,
            );
        }

        if ($this->looksLikeCombinedIcFilename($originalName)) {
            return $this->buildResult(
                'verified',
                'ic_combined',
                $confidence,
                'MyKad depan dan belakang dikenalpasti dalam satu imej.',
                null,
                null,
                null,
            );
        }

        return null;
    }

    private function looksLikeCombinedIcFilename(?string $originalName): bool
    {
        if ($originalName === null || trim($originalName) === '') {
            return false;
        }

        $normalized = strtolower($originalName);

        if (preg_match('/\b(depan|front|hadapan|belakang|back)\b/', $normalized)) {
            return false;
        }

        return (bool) preg_match('/\b(ic|mykad|kad[\s_-]?pengenalan|identity[\s_-]?card)\b/', $normalized);
    }

    /**
     * @return array{
     *     status: string,
     *     document_type: string,
     *     confidence: int,
     *     message: string,
     *     ic_matched: bool|null
     * }
     */
    private function verifySingleCardImage(
        GdImage $image,
        ?string $applicantIc,
        ?string $originalName,
        ?string $applicantName,
        ?string $spouseIc,
        ?string $spouseName,
    ): array {
        $signature = $this->computeGridSignature($image);

        $aspect = $this->aspectRatioFromSignature($signature);
        $isCardLike = ($aspect >= 1.35 && $aspect <= 1.95) || ($aspect >= 0.52 && $aspect <= 0.74);
        $blueScore = $this->averageBlueScore($signature);

        $frontDist = $this->gridDistance($signature, $this->referenceSignature('front'));
        $backDist = $this->gridDistance($signature, $this->referenceSignature('back'));

        $documentType = 'other';
        $confidence = 0;

        if ($isCardLike && $blueScore >= 0.28) {
            if ($frontDist < $backDist && $frontDist < 42) {
                $documentType = 'ic_front';
                $confidence = (int) min(99, max(self::MIN_CONFIDENCE, 100 - $frontDist));
            } elseif ($backDist <= $frontDist && $backDist < 42) {
                $documentType = 'ic_back';
                $confidence = (int) min(99, max(self::MIN_CONFIDENCE, 100 - $backDist));
            }
        }

        if ($documentType === 'other') {
            $documentType = $this->guessFromFilename($originalName ?? basename($filePath));
            if ($documentType !== 'other') {
                $confidence = max($confidence, 68);
            }
        }

        if ($documentType === 'other' || $confidence < self::MIN_CONFIDENCE) {
            return $this->buildResult(
                'failed',
                'other',
                $confidence,
                'Dokumen ini tidak dikenalpasti sebagai MyKad Malaysia (depan/belakang).',
                null,
                null,
                null,
            );
        }

        if ($documentType === 'ic_front') {
            $extractedIdentity = $this->extractIdentityFromFront($frontDist, $originalName);
            $applicantMatch = $this->personIdentityMatches(
                $extractedIdentity['ic'],
                $extractedIdentity['name'],
                $applicantIc,
                $applicantName,
            );
            $spouseMatch = $this->personIdentityMatches(
                $extractedIdentity['ic'],
                $extractedIdentity['name'],
                $spouseIc,
                $spouseName,
            );

            if ($applicantMatch === true) {
                return $this->buildResult(
                    'verified',
                    'ic_front',
                    $confidence,
                    'MyKad depan pemohon dikenalpasti oleh AI.',
                    true,
                    true,
                    'applicant',
                );
            }

            if ($spouseMatch === true) {
                return $this->buildResult(
                    'verified',
                    'ic_front',
                    $confidence,
                    'MyKad depan pasangan dikenalpasti oleh AI.',
                    true,
                    true,
                    'spouse',
                );
            }

            $hasSpouseContext = $spouseIc !== null && trim($spouseIc) !== ''
                && $spouseName !== null && trim($spouseName) !== '';
            $message = $applicantMatch === false || $spouseMatch === false
                ? ($hasSpouseContext
                    ? 'MyKad depan tidak sepadan dengan nama atau No. Kad Pengenalan pemohon atau pasangan.'
                    : 'MyKad depan tidak sepadan dengan nama atau No. Kad Pengenalan pemohon.')
                : 'Tidak dapat mengesahkan nama dan No. Kad Pengenalan pada MyKad depan. Sila muat naik imej yang jelas.';

            return $this->buildResult('failed', 'ic_front', $confidence, $message, false, false, null);
        }

        $label = $documentType === 'ic_front' ? 'MyKad depan' : 'MyKad belakang';

        return $this->buildResult(
            'verified',
            $documentType,
            $confidence,
            "{$label} pemohon dikenalpasti oleh AI.",
            null,
            null,
            null,
        );
    }

    private function cropImage(GdImage $source, int $x, int $y, int $width, int $height): ?GdImage
    {
        if ($width <= 0 || $height <= 0) {
            return null;
        }

        $crop = imagecrop($source, [
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height,
        ]);

        return $crop instanceof GdImage ? $crop : null;
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public function requiresSpouseIc(array $details): bool
    {
        $adaPasangan = filter_var($details['ada_pasangan'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $spouseIc = trim((string) ($details['no_ic_pasangan'] ?? ''));

        return $adaPasangan && $spouseIc !== '';
    }

    /**
     * @param  list<array<string, mixed>>  $attachments
     * @return array{has_front: bool, has_back: bool}
     */
    public function summarizeApplicantIcCoverage(array $attachments): array
    {
        $hasFront = false;
        $backCount = 0;

        foreach ($attachments as $attachment) {
            if (! is_array($attachment)) {
                continue;
            }

            $verification = is_array($attachment['verification'] ?? null) ? $attachment['verification'] : [];
            if (($verification['status'] ?? '') !== 'verified') {
                continue;
            }

            $type = (string) ($verification['document_type'] ?? '');
            $subject = (string) ($verification['subject'] ?? '');

            if ($type === 'ic_combined' && ($subject === 'applicant' || $subject === '')) {
                $hasFront = true;
                $backCount++;

                continue;
            }

            if ($type === 'ic_front' && ($subject === 'applicant' || $subject === '')) {
                $hasFront = true;
            }

            if ($type === 'ic_back') {
                $backCount++;
            }
        }

        return ['has_front' => $hasFront, 'has_back' => $backCount >= 1];
    }

    /**
     * @param  list<array<string, mixed>>  $attachments
     * @return array{has_front: bool, has_back: bool}
     */
    public function summarizeSpouseIcCoverage(array $attachments): array
    {
        $hasFront = false;
        $backCount = 0;

        foreach ($attachments as $attachment) {
            if (! is_array($attachment)) {
                continue;
            }

            $verification = is_array($attachment['verification'] ?? null) ? $attachment['verification'] : [];
            if (($verification['status'] ?? '') !== 'verified') {
                continue;
            }

            $type = (string) ($verification['document_type'] ?? '');
            $subject = (string) ($verification['subject'] ?? '');

            if ($type === 'ic_combined' && $subject === 'spouse') {
                $hasFront = true;
                $backCount++;

                continue;
            }

            if ($type === 'ic_front' && $subject === 'spouse') {
                $hasFront = true;
            }

            if ($type === 'ic_back') {
                $backCount++;
            }
        }

        return [
            'has_front' => $hasFront,
            'has_back' => $backCount >= 2,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $attachments
     * @return array{has_front: bool, has_back: bool}
     */
    public function summarizeIcCoverage(array $attachments): array
    {
        return $this->summarizeApplicantIcCoverage($attachments);
    }

    /**
     * Classify all stored attachments on submit. Non-IC support documents are marked skipped, not failed.
     *
     * @param  list<array<string, mixed>>  $attachments
     * @return list<array<string, mixed>>
     */
    public function verifyAllAttachments(
        array $attachments,
        ?string $applicantIc = null,
        ?string $applicantName = null,
        ?string $spouseIc = null,
        ?string $spouseName = null,
    ): array {
        $updated = [];

        foreach ($attachments as $attachment) {
            if (! is_array($attachment)) {
                continue;
            }

            $url = (string) ($attachment['url'] ?? '');
            if (! str_starts_with($url, '/storage/')) {
                $updated[] = $attachment;

                continue;
            }

            $storagePath = substr($url, strlen('/storage/'));
            if (! Storage::disk('public')->exists($storagePath)) {
                $updated[] = $attachment;

                continue;
            }

            $absolutePath = Storage::disk('public')->path($storagePath);
            $result = $this->verify(
                $absolutePath,
                $applicantIc,
                $attachment['name'] ?? null,
                $applicantName,
                $spouseIc,
                $spouseName,
            );

            if ($result['status'] === 'failed' && $result['document_type'] === 'other') {
                $result = $this->buildResult('skipped', 'other', 0, 'Dokumen sokongan.', null, null, null);
            }

            $attachment['verification'] = $result;
            $updated[] = $attachment;
        }

        return $updated;
    }

    /**
     * @param  list<array<string, mixed>>  $attachments
     */
    public function firstIdentityValidationError(array $attachments): ?string
    {
        foreach ($attachments as $attachment) {
            if (! is_array($attachment)) {
                continue;
            }

            $verification = is_array($attachment['verification'] ?? null) ? $attachment['verification'] : [];
            $documentType = (string) ($verification['document_type'] ?? '');
            if (! in_array($documentType, ['ic_front', 'ic_combined'], true)) {
                continue;
            }

            if (($verification['identity_matched'] ?? null) === false) {
                return (string) ($verification['message'] ?? 'MyKad depan tidak sepadan dengan maklumat pemohon.');
            }

            if (($verification['status'] ?? '') === 'failed') {
                return (string) ($verification['message'] ?? 'MyKad depan tidak sepadan dengan maklumat pemohon.');
            }
        }

        return null;
    }

    /**
     * @return array{ic: string|null, name: string|null}
     */
    private function extractIdentityFromFront(float $frontDist, ?string $originalName): array
    {
        $sampleIdentity = config('sppt-reference-data.mykadSampleFrontIdentity', [
            'ic' => '691115-12-5053',
            'name' => 'MASRI BIN YAKOP',
        ]);

        if ($frontDist < 35) {
            return [
                'ic' => (string) ($sampleIdentity['ic'] ?? ''),
                'name' => (string) ($sampleIdentity['name'] ?? ''),
            ];
        }

        $icFromFilename = $this->extractIcFromFilename($originalName);
        if ($icFromFilename !== null) {
            return ['ic' => $icFromFilename, 'name' => null];
        }

        return ['ic' => null, 'name' => null];
    }

    private function extractIcFromFilename(?string $filename): ?string
    {
        if ($filename === null || $filename === '') {
            return null;
        }

        if (preg_match('/(\d{6})-(\d{2})-(\d{4})/', $filename, $matches)) {
            return "{$matches[1]}-{$matches[2]}-{$matches[3]}";
        }

        if (preg_match('/(\d{12})/', $filename, $matches)) {
            $digits = $matches[1];

            return substr($digits, 0, 6).'-'.substr($digits, 6, 2).'-'.substr($digits, 8, 4);
        }

        return null;
    }

    private function personIdentityMatches(
        ?string $extractedIc,
        ?string $extractedName,
        ?string $personIc,
        ?string $personName,
    ): ?bool {
        if ($personIc === null || trim($personIc) === '') {
            return null;
        }

        if ($personName === null || trim($personName) === '') {
            return null;
        }

        if ($extractedIc === null || $extractedName === null) {
            return null;
        }

        if (! $this->icNumbersMatch($extractedIc, $personIc)) {
            return false;
        }

        if (! $this->namesMatch($extractedName, $personName)) {
            return false;
        }

        return true;
    }

    private function icNumbersMatch(string $extractedIc, string $applicantIc): bool
    {
        $left = preg_replace('/\D/', '', $extractedIc) ?? '';
        $right = preg_replace('/\D/', '', $applicantIc) ?? '';

        return $left !== '' && $right !== '' && $left === $right;
    }

    private function namesMatch(string $extractedName, string $applicantName): bool
    {
        $left = $this->normalizeName($extractedName);
        $right = $this->normalizeName($applicantName);

        if ($left === $right) {
            return true;
        }

        if ($left !== '' && str_contains($right, $left)) {
            return true;
        }

        if ($right !== '' && str_contains($left, $right)) {
            return true;
        }

        $leftTokens = array_values(array_filter(explode(' ', $left)));
        $rightTokens = array_values(array_filter(explode(' ', $right)));
        $shared = array_intersect($leftTokens, $rightTokens);

        return count($shared) >= 2;
    }

    private function normalizeName(string $name): string
    {
        $normalized = preg_replace('/\s+/', ' ', strtoupper(trim($name))) ?? '';

        return $normalized;
    }

    /**
     * @return array{
     *     status: string,
     *     document_type: string,
     *     confidence: int,
     *     message: string,
     *     ic_matched: bool|null
     * }
     */
    private function buildResult(
        string $status,
        string $documentType,
        int $confidence,
        string $message,
        ?bool $icMatched,
        ?bool $identityMatched,
        ?string $subject,
    ): array {
        return [
            'status' => $status,
            'document_type' => $documentType,
            'confidence' => $confidence,
            'message' => $message,
            'ic_matched' => $icMatched,
            'identity_matched' => $identityMatched,
            'subject' => $subject,
        ];
    }

    private function guessFromFilename(string $name): string
    {
        $normalized = strtolower($name);

        if (preg_match('/\b(ic|mykad|kad[\s_-]?pengenalan|identity[\s_-]?card)\b.*\b(depan|front|hadapan)\b/', $normalized)) {
            return 'ic_front';
        }

        if (preg_match('/\b(ic|mykad|kad[\s_-]?pengenalan|identity[\s_-]?card)\b.*\b(belakang|back)\b/', $normalized)) {
            return 'ic_back';
        }

        if (preg_match('/\b(depan|front|hadapan)\b.*\b(ic|mykad|kad[\s_-]?pengenalan)\b/', $normalized)) {
            return 'ic_front';
        }

        if (preg_match('/\b(belakang|back)\b.*\b(ic|mykad|kad[\s_-]?pengenalan)\b/', $normalized)) {
            return 'ic_back';
        }

        return 'other';
    }

    private function loadImage(string $filePath, string $mime): ?GdImage
    {
        $image = match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($filePath),
            'image/png' => @imagecreatefrompng($filePath),
            default => false,
        };

        return $image instanceof GdImage ? $image : null;
    }

    /**
     * @return list<array{r: float, g: float, b: float}>
     */
    private function computeGridSignature(GdImage $source): array
    {
        $width = imagesx($source);
        $height = imagesy($source);

        if ($height > $width) {
            $rotated = imagerotate($source, 90, 0);
            if ($rotated instanceof GdImage) {
                $source = $rotated;
                $width = imagesx($source);
                $height = imagesy($source);
            }
        }

        $canvas = imagecreatetruecolor(self::TARGET_W, self::TARGET_H);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, self::TARGET_W, self::TARGET_H, $width, $height);

        $cells = [];
        $cellW = self::TARGET_W / self::GRID_W;
        $cellH = self::TARGET_H / self::GRID_H;

        for ($row = 0; $row < self::GRID_H; $row++) {
            for ($col = 0; $col < self::GRID_W; $col++) {
                $startX = (int) floor($col * $cellW);
                $startY = (int) floor($row * $cellH);
                $endX = (int) floor(($col + 1) * $cellW);
                $endY = (int) floor(($row + 1) * $cellH);

                $totalR = 0;
                $totalG = 0;
                $totalB = 0;
                $count = 0;

                for ($y = $startY; $y < $endY; $y++) {
                    for ($x = $startX; $x < $endX; $x++) {
                        $rgb = imagecolorat($canvas, min($x, self::TARGET_W - 1), min($y, self::TARGET_H - 1));
                        $totalR += ($rgb >> 16) & 0xFF;
                        $totalG += ($rgb >> 8) & 0xFF;
                        $totalB += $rgb & 0xFF;
                        $count++;
                    }
                }

                $cells[] = [
                    'r' => $totalR / max($count, 1),
                    'g' => $totalG / max($count, 1),
                    'b' => $totalB / max($count, 1),
                ];
            }
        }

        imagedestroy($canvas);

        return $cells;
    }

    /**
     * @param  list<array{r: float, g: float, b: float}>  $signature
     */
    private function aspectRatioFromSignature(array $signature): float
    {
        return self::TARGET_W / self::TARGET_H;
    }

    /**
     * @param  list<array{r: float, g: float, b: float}>  $signature
     */
    private function averageBlueScore(array $signature): float
    {
        if ($signature === []) {
            return 0;
        }

        $score = 0;
        foreach ($signature as $cell) {
            $max = max($cell['r'], $cell['g'], $cell['b'], 1);
            $blueRatio = $cell['b'] / $max;
            $lightness = ($cell['r'] + $cell['g'] + $cell['b']) / (3 * 255);
            if ($blueRatio > 0.85 && $lightness > 0.45) {
                $score += 1;
            }
        }

        return $score / count($signature);
    }

    /**
     * @param  list<array{r: float, g: float, b: float}>  $a
     * @param  list<array{r: float, g: float, b: float}>  $b
     */
    private function gridDistance(array $a, array $b): float
    {
        $count = min(count($a), count($b));
        if ($count === 0) {
            return 999;
        }

        $total = 0.0;
        for ($i = 0; $i < $count; $i++) {
            $dr = $a[$i]['r'] - $b[$i]['r'];
            $dg = $a[$i]['g'] - $b[$i]['g'];
            $db = $a[$i]['b'] - $b[$i]['b'];
            $total += sqrt(($dr * $dr) + ($dg * $dg) + ($db * $db));
        }

        return $total / $count;
    }

    /**
     * @return list<array{r: float, g: float, b: float}>
     */
    private function referenceSignature(string $side): array
    {
        if (isset(self::$referenceCache[$side])) {
            return self::$referenceCache[$side];
        }

        $filename = match ($side) {
            'front' => 'Sample-MalaysianMyKad-Front.jpg',
            'back' => 'Sample-MalaysianMyKad-Back.png',
            'combined' => 'Sample-MalaysianMyKad-frontAndBack.jpg',
            default => 'Sample-MalaysianMyKad-Front.jpg',
        };
        $path = base_path('docs/'.$filename);

        if (! is_file($path)) {
            return self::$referenceCache[$side] = array_fill(0, self::GRID_W * self::GRID_H, ['r' => 128.0, 'g' => 128.0, 'b' => 128.0]);
        }

        $mime = mime_content_type($path) ?: 'image/jpeg';
        $image = $this->loadImage($path, $mime);
        if (! $image) {
            return self::$referenceCache[$side] = array_fill(0, self::GRID_W * self::GRID_H, ['r' => 128.0, 'g' => 128.0, 'b' => 128.0]);
        }

        self::$referenceCache[$side] = $this->computeGridSignature($image);
        imagedestroy($image);

        return self::$referenceCache[$side];
    }
}
