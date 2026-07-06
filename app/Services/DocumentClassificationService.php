<?php

namespace App\Services;

/**
 * POC AI-style document classification for permohonan supporting documents (Spec 1.7.5).
 * Uses filename heuristics and MyKad visual fingerprinting for IC detection.
 */
class DocumentClassificationService
{
    public const CLASS_IC_PEMOHON_DEPAN = 'ic_pemohon_depan';

    public const CLASS_IC_PEMOHON_BELAKANG = 'ic_pemohon_belakang';

    public const CLASS_IC_PEMOHON_COMBINED = 'ic_pemohon_combined';

    public const CLASS_IC_PASANGAN_DEPAN = 'ic_pasangan_depan';

    public const CLASS_IC_PASANGAN_BELAKANG = 'ic_pasangan_belakang';

    public const CLASS_IC_PASANGAN_COMBINED = 'ic_pasangan_combined';

    public const CLASS_SSM_FORM_9 = 'ssm_form_9';

    public const CLASS_LESEN_PBT = 'lesen_pbt';

    public const CLASS_PENYATA_BANK = 'penyata_bank';

    public const CLASS_PLAN_PERNIAGAAN = 'plan_perniagaan';

    public const CLASS_LAIN_LAIN = 'lain_lain';

    /** @var array<string, string> */
    private const LEGACY_CLASS_MAP = [
        'ic_pemohon' => self::CLASS_IC_PEMOHON_COMBINED,
        'ic_pasangan' => self::CLASS_IC_PASANGAN_COMBINED,
    ];

    public function __construct(
        protected MyKadVerificationService $myKadVerification,
    ) {}

    /**
     * @return list<string>
     */
    public static function allowedClasses(): array
    {
        return array_keys(config('sppt.document_classes', []));
    }

    public static function labelFor(string $class): string
    {
        $class = self::LEGACY_CLASS_MAP[$class] ?? $class;
        $labels = config('sppt.document_classes', []);

        return is_string($labels[$class] ?? null) ? $labels[$class] : $class;
    }

    /**
     * @return array{
     *     suggested_class: string,
     *     confidence: int,
     *     message: string
     * }
     */
    public function classify(
        string $filePath,
        ?string $originalName = null,
        ?string $applicantIc = null,
        ?string $applicantName = null,
        ?string $spouseIc = null,
        ?string $spouseName = null,
    ): array {
        $filename = $originalName ?? basename($filePath);
        $filenameResult = $this->classifyFromFilename($filename);

        $mime = mime_content_type($filePath) ?: '';
        $isImage = in_array($mime, ['image/jpeg', 'image/png', 'image/jpg'], true);

        if ($isImage) {
            $verification = $this->myKadVerification->verify(
                $filePath,
                $applicantIc,
                $originalName,
                $applicantName,
                $spouseIc,
                $spouseName,
            );

            $icResult = $this->mapVerificationToClass($verification);
            if ($icResult !== null && $icResult['confidence'] >= $filenameResult['confidence']) {
                return $icResult;
            }
        }

        if ($filenameResult['confidence'] >= 55) {
            return $filenameResult;
        }

        return [
            'suggested_class' => self::CLASS_LAIN_LAIN,
            'confidence' => max(35, $filenameResult['confidence']),
            'message' => 'Jenis dokumen tidak dapat dikenalpasti dengan pasti. Sila pilih kategori yang sesuai.',
        ];
    }

    /**
     * @param  array<string, mixed>  $verification
     * @return array{suggested_class: string, confidence: int, message: string}|null
     */
    private function mapVerificationToClass(array $verification): ?array
    {
        $documentType = (string) ($verification['document_type'] ?? 'other');
        if (! in_array($documentType, ['ic_front', 'ic_back', 'ic_combined'], true)) {
            return null;
        }

        $confidence = (int) ($verification['confidence'] ?? 0);
        if ($confidence < 55) {
            $status = (string) ($verification['status'] ?? '');
            if ($status !== 'verified' && $confidence < 45) {
                return null;
            }
        }

        $subject = (string) ($verification['subject'] ?? '');
        $isSpouse = $subject === 'spouse';
        $suggestedClass = $this->resolveIcClass($isSpouse, $documentType);

        $sideLabel = match ($documentType) {
            'ic_front' => 'depan',
            'ic_back' => 'belakang',
            default => 'depan & belakang',
        };

        $subjectLabel = $isSpouse ? 'pasangan' : 'pemohon';

        return [
            'suggested_class' => $suggestedClass,
            'confidence' => max($confidence, 72),
            'message' => "AI mengesan MyKad {$sideLabel} {$subjectLabel} (keyakinan {$confidence}%).",
        ];
    }

    /**
     * @return array{suggested_class: string, confidence: int, message: string}
     */
    private function classifyFromFilename(string $filename): array
    {
        $normalized = strtolower($filename);

        if ($this->isSpouseIcFilename($normalized)) {
            return $this->classifyIcSideFromFilename($normalized, true);
        }

        if ($this->isApplicantIcFilename($normalized)) {
            return $this->classifyIcSideFromFilename($normalized, false);
        }

        if ($this->matchesPattern($normalized, [
            '/\b(ssm|suruhanjaya[\s_-]?syarikat|form[\s_-]?9|form9|borang[\s_-]?9)\b/',
        ])) {
            return $this->result(self::CLASS_SSM_FORM_9, 90, 'Nama fail menunjukkan dokumen SSM Form 9.');
        }

        if ($this->matchesPattern($normalized, [
            '/\b(penyata[\s_-]?bank|bank[\s_-]?statement|statement[\s_-]?bank|e[\s_-]?statement)\b/',
            '/\b(maybank|cimb|rhb|public[\s_-]?bank|hong[\s_-]?leong|ambank|bank[\s_-]?rakyat)\b.*\b(penyata|statement)\b/',
        ])) {
            return $this->result(self::CLASS_PENYATA_BANK, 88, 'Nama fail menunjukkan penyata bank.');
        }

        if ($this->matchesPattern($normalized, [
            '/\b(lesen|permit|pbt|mpkp|mpk|mbsp|dbkl|majlis[\s_-]?perbandaran)\b/',
        ])) {
            return $this->result(self::CLASS_LESEN_PBT, 86, 'Nama fail menunjukkan lesen atau permit PBT.');
        }

        if ($this->matchesPattern($normalized, [
            '/\b(plan[\s_-]?perniagaan|business[\s_-]?plan|rancangan[\s_-]?perniagaan)\b/',
        ])) {
            return $this->result(self::CLASS_PLAN_PERNIAGAAN, 84, 'Nama fail menunjukkan plan perniagaan.');
        }

        return $this->result(self::CLASS_LAIN_LAIN, 40, 'Jenis dokumen tidak dapat dikenalpasti daripada nama fail.');
    }

    private function isSpouseIcFilename(string $normalized): bool
    {
        return $this->matchesPattern($normalized, [
            '/\b(pasangan|spouse|isteri|suami|wife|husband)\b.*\b(ic|mykad|kad[\s_-]?pengenalan)\b/',
            '/\b(ic|mykad|kad[\s_-]?pengenalan)\b.*\b(pasangan|spouse|isteri|suami)\b/',
        ]);
    }

    private function isApplicantIcFilename(string $normalized): bool
    {
        return $this->matchesPattern($normalized, [
            '/\b(ic|mykad|kad[\s_-]?pengenalan|identity[\s_-]?card)\b/',
            '/\b(ic|mykad)[\s_-]?(depan|belakang|front|back)\b/',
            '/\b(depan|front|hadapan|belakang|back)\b.*\b(ic|mykad|kad[\s_-]?pengenalan)\b/',
        ]);
    }

    /**
     * @return array{suggested_class: string, confidence: int, message: string}
     */
    private function classifyIcSideFromFilename(string $normalized, bool $isSpouse): array
    {
        $side = $this->detectIcSideFromFilename($normalized);
        $suggestedClass = $this->resolveIcClass($isSpouse, $side);
        $subjectLabel = $isSpouse ? 'pasangan' : 'pemohon';
        $sideLabel = match ($side) {
            'ic_front' => 'depan',
            'ic_back' => 'belakang',
            default => 'depan & belakang',
        };

        return $this->result(
            $suggestedClass,
            $side === 'ic_combined' ? 86 : 88,
            "Nama fail menunjukkan salinan IC {$sideLabel} {$subjectLabel}.",
        );
    }

    private function detectIcSideFromFilename(string $normalized): string
    {
        if ($this->matchesPattern($normalized, [
            '/\b(depan[\s_&-]*belakang|belakang[\s_&-]*depan|front[\s_&-]*back|back[\s_&-]*front)\b/',
            '/\b(combined|depan-belakang|front-back|depan_belakang)\b/',
        ])) {
            return 'ic_combined';
        }

        if ($this->matchesPattern($normalized, [
            '/\b(belakang|back)\b/',
        ]) && ! $this->matchesPattern($normalized, [
            '/\b(depan|front|hadapan)\b/',
        ])) {
            return 'ic_back';
        }

        if ($this->matchesPattern($normalized, [
            '/\b(depan|front|hadapan)\b/',
        ]) && ! $this->matchesPattern($normalized, [
            '/\b(belakang|back)\b/',
        ])) {
            return 'ic_front';
        }

        return 'ic_combined';
    }

    private function resolveIcClass(bool $isSpouse, string $documentType): string
    {
        return match ([$isSpouse, $documentType]) {
            [true, 'ic_front'] => self::CLASS_IC_PASANGAN_DEPAN,
            [true, 'ic_back'] => self::CLASS_IC_PASANGAN_BELAKANG,
            [true, 'ic_combined'] => self::CLASS_IC_PASANGAN_COMBINED,
            [false, 'ic_front'] => self::CLASS_IC_PEMOHON_DEPAN,
            [false, 'ic_back'] => self::CLASS_IC_PEMOHON_BELAKANG,
            default => self::CLASS_IC_PEMOHON_COMBINED,
        };
    }

    /**
     * @param  list<string>  $patterns
     */
    private function matchesPattern(string $value, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{suggested_class: string, confidence: int, message: string}
     */
    private function result(string $class, int $confidence, string $message): array
    {
        return [
            'suggested_class' => $class,
            'confidence' => $confidence,
            'message' => $message,
        ];
    }
}
