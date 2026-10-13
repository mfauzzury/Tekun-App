<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PemohonDataAlternatifRequest;
use App\Http\Requests\PemohonEkstrakPenyataRequest;
use App\Http\Requests\PemohonEkycWajahRequest;
use App\Http\Requests\StartPemohonPenilaianPantasRequest;
use App\Http\Requests\ClassifyPemohonPermohonanDocumentRequest;
use App\Http\Requests\StorePemohonPermohonanDocumentRequest;
use App\Http\Requests\StorePemohonPermohonanRequest;
use App\Http\Requests\UpdatePemohonPermohonanDocumentClassRequest;
use App\Http\Requests\UpdatePemohonPermohonanRequest;
use App\Http\Requests\VerifyPemohonPermohonanDocumentRequest;
use App\Http\Traits\ApiResponse;
use App\Models\PenilaianPantas;
use App\Models\Permohonan;
use App\Services\AlternativeCreditScoringService;
use App\Services\DocumentClassificationService;
use App\Services\EkycService;
use App\Services\MyKadVerificationService;
use App\Services\PenilaianPantasService;
use App\Services\PermohonanReferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Self-service counterpart to Api\Sppt\PermohonanController for the public Pemohon portal.
 *
 * Every action that reads/writes an existing Permohonan requires a matching `pemohon_access_token`
 * (returned once at creation). Staff-created records always have a null token, so they can never
 * be reached through these endpoints.
 */
class PemohonPermohonanController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected MyKadVerificationService $myKadVerification,
        protected DocumentClassificationService $documentClassification,
        protected PermohonanReferenceService $referenceService,
        protected PenilaianPantasService $penilaianPantas,
        protected EkycService $ekyc,
        protected AlternativeCreditScoringService $creditScoring,
    ) {}

    public function store(StorePemohonPermohonanRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $status = $validated['status'] ?? 'Draf';

        $permohonan = Permohonan::create([
            'no_rujukan' => $this->referenceService->generateNextNoRujukan(),
            'usahawan_id' => null,
            'nama' => $validated['nama'] ?? 'Draf',
            'kategori_pembiayaan' => $validated['kategori_pembiayaan'] ?? null,
            'status' => $status,
            'jumlah_permohonan' => $validated['jumlah_permohonan'] ?? 0,
            'tarikh_permohonan' => $status !== 'Draf' ? now()->toDateString() : null,
            'details' => $validated['details'] ?? [],
            'pemohon_email' => $validated['pemohon_email'] ?? null,
            'pemohon_telefon' => $validated['pemohon_telefon'] ?? null,
            'pemohon_access_token' => Str::random(40),
        ]);

        return $this->sendCreated($permohonan);
    }

    public function show(Request $request, int $permohonan): JsonResponse
    {
        $record = $this->resolveOwned($permohonan, (string) $request->query('access_token', ''));
        if (! $record) {
            return $this->sendError(404, 'NOT_FOUND', 'Permohonan not found');
        }

        return $this->sendOk($record);
    }

    public function update(UpdatePemohonPermohonanRequest $request, int $permohonan): JsonResponse
    {
        $validated = $request->validated();
        $record = $this->resolveOwned($permohonan, $validated['access_token']);
        if (! $record) {
            return $this->sendError(404, 'NOT_FOUND', 'Permohonan not found');
        }

        $existingDetails = is_array($record->details) ? $record->details : [];
        $incomingDetails = is_array($validated['details'] ?? null) ? $validated['details'] : [];
        // Server-owned keys: written only by the e-KYC / data-alternatif endpoints and on Hantar.
        foreach (['data_alternatif', 'ekyc_mykad', 'ekyc_wajah', 'dihantar_pada'] as $serverKey) {
            unset($incomingDetails[$serverKey]);
        }
        $details = array_merge($existingDetails, $incomingDetails);
        if (! array_key_exists('attachments', $incomingDetails) && ! empty($existingDetails['attachments'])) {
            $details['attachments'] = $existingDetails['attachments'];
        }

        $targetStatus = $validated['status'] ?? $record->status;

        if ($targetStatus !== 'Draf') {
            $attachments = is_array($details['attachments'] ?? null) ? $details['attachments'] : [];

            if (count($attachments) === 0) {
                return $this->sendError(422, 'VALIDATION_ERROR', 'Validation failed', [
                    'attachments' => ['Sila lampirkan dokumen sokongan.'],
                ]);
            }

            $applicantIc = (string) ($details['no_ic_baru'] ?? '');
            $applicantName = (string) ($details['nama'] ?? $validated['nama'] ?? $record->nama ?? '');
            $spouseIc = (string) ($details['no_ic_pasangan'] ?? '');
            $spouseName = (string) ($details['nama_pasangan'] ?? '');

            $attachments = $this->myKadVerification->verifyAllAttachments(
                $attachments,
                $applicantIc,
                $applicantName,
                $spouseIc,
                $spouseName,
            );

            // When the AI e-KYC has read the MyKad, it is authoritative for identity and coverage;
            // the image-fingerprint check remains the fallback for applications without it.
            $aiOcr = is_array($existingDetails['ekyc_mykad']['ocr'] ?? null) ? $existingDetails['ekyc_mykad']['ocr'] : [];
            $aiVerified = ($aiOcr['ok'] ?? false) && ($aiOcr['adalah_mykad'] ?? false);

            if ($aiVerified) {
                $padanan = $this->ekyc->compareWithApplication(is_array($aiOcr['data'] ?? null) ? $aiOcr['data'] : [], $details);
                $icRow = collect($padanan['padanan'])->firstWhere('medan', 'no_ic');
                if ($icRow && $icRow['nilai_borang'] && $icRow['nilai_mykad'] && ! $icRow['padan']) {
                    return $this->sendError(422, 'VALIDATION_ERROR', 'Validation failed', [
                        'attachments' => ['No. MyKad yang dibaca daripada kad ('.$icRow['nilai_mykad'].') tidak sepadan dengan No. MyKad dalam borang.'],
                    ]);
                }

                $classes = array_column(array_filter($attachments, 'is_array'), 'document_class');
                $coverage = [
                    'has_front' => (bool) array_intersect($classes, [DocumentClassificationService::CLASS_IC_PEMOHON_DEPAN, DocumentClassificationService::CLASS_IC_PEMOHON_COMBINED]),
                    'has_back' => (bool) array_intersect($classes, [DocumentClassificationService::CLASS_IC_PEMOHON_BELAKANG, DocumentClassificationService::CLASS_IC_PEMOHON_COMBINED]),
                ];
            } else {
                $identityError = $this->myKadVerification->firstIdentityValidationError($attachments);
                if ($identityError !== null) {
                    return $this->sendError(422, 'VALIDATION_ERROR', 'Validation failed', [
                        'attachments' => [$identityError],
                    ]);
                }

                $coverage = $this->myKadVerification->summarizeApplicantIcCoverage($attachments);
            }

            if (! $coverage['has_front'] || ! $coverage['has_back']) {
                return $this->sendError(422, 'VALIDATION_ERROR', 'Validation failed', [
                    'attachments' => ['Sila lampirkan salinan MyKad depan dan belakang pemohon dalam dokumen sokongan.'],
                ]);
            }

            if ($this->myKadVerification->requiresSpouseIc($details)) {
                $spouseCoverage = $this->myKadVerification->summarizeSpouseIcCoverage($attachments);
                if (! $spouseCoverage['has_front'] || ! $spouseCoverage['has_back']) {
                    return $this->sendError(422, 'VALIDATION_ERROR', 'Validation failed', [
                        'attachments' => ['Sila lampirkan salinan MyKad depan dan belakang pasangan dalam dokumen sokongan.'],
                    ]);
                }
            }

            $details['attachments'] = $attachments;
            // Start of the POC 1 end-to-end timer (Hantar → AI recommendation); never client-supplied.
            $details['dihantar_pada'] = $record->status === 'Draf'
                ? now()->toIso8601String()
                : ($existingDetails['dihantar_pada'] ?? now()->toIso8601String());
            $validated['tarikh_permohonan'] = $record->tarikh_permohonan?->toDateString() ?? now()->toDateString();
        }

        $record->update([
            'nama' => $validated['nama'] ?? $record->nama,
            'kategori_pembiayaan' => $validated['kategori_pembiayaan'] ?? $record->kategori_pembiayaan,
            'status' => $targetStatus,
            'jumlah_permohonan' => $validated['jumlah_permohonan'] ?? $record->jumlah_permohonan,
            'tarikh_permohonan' => $validated['tarikh_permohonan'] ?? $record->tarikh_permohonan,
            'pemohon_email' => $validated['pemohon_email'] ?? $record->pemohon_email,
            'pemohon_telefon' => $validated['pemohon_telefon'] ?? $record->pemohon_telefon,
            'details' => $details,
        ]);

        return $this->sendOk($record);
    }

    public function uploadDocument(StorePemohonPermohonanDocumentRequest $request, int $permohonan): JsonResponse
    {
        $validated = $request->validated();
        $record = $this->resolveOwned($permohonan, $validated['access_token']);
        if (! $record) {
            return $this->sendError(404, 'NOT_FOUND', 'Permohonan not found');
        }

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $safeBase = preg_replace('/-+/', '-', preg_replace('/[^a-z0-9.\-_]/', '-', strtolower($originalName)));
        $ext = pathinfo($safeBase, PATHINFO_EXTENSION);
        $name = pathinfo($safeBase, PATHINFO_FILENAME);
        $filename = $name.'-'.time().'.'.$ext;

        $file->storeAs('permohonan/'.$permohonan, $filename, 'public');

        $attachment = [
            'id' => (string) Str::uuid(),
            'name' => $originalName,
            'size' => $file->getSize(),
            'url' => '/storage/permohonan/'.$permohonan.'/'.$filename,
            'mime_type' => $file->getMimeType(),
        ];

        $documentClass = $validated['document_class'] ?? null;
        if (is_string($documentClass) && $documentClass !== '') {
            $attachment['document_class'] = $documentClass;
            $attachment['document_class_label'] = DocumentClassificationService::labelFor($documentClass);
            if ($documentClass === DocumentClassificationService::CLASS_LAIN_LAIN) {
                $attachment['document_class_other'] = (string) ($validated['document_class_other'] ?? '');
            }
        }

        $details = is_array($record->details) ? $record->details : [];
        $attachments = is_array($details['attachments'] ?? null) ? $details['attachments'] : [];
        $attachments[] = $attachment;
        $details['attachments'] = $attachments;

        $record->update(['details' => $details]);

        return $this->sendCreated($attachment);
    }

    public function classifyDocument(ClassifyPemohonPermohonanDocumentRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $tempPath = $file->getRealPath() ?: $file->store('temp/classify', 'local');
        $absolutePath = $tempPath !== $file->getRealPath() ? Storage::disk('local')->path($tempPath) : $tempPath;

        $classification = $this->documentClassification->classify(
            $absolutePath,
            $originalName,
            $request->input('applicant_ic'),
            $request->input('applicant_name'),
            $request->input('spouse_ic'),
            $request->input('spouse_name'),
        );

        if ($tempPath !== $file->getRealPath()) {
            Storage::disk('local')->delete($tempPath);
        }

        return $this->sendOk([
            'classification' => $classification,
            'name' => $originalName,
        ]);
    }

    public function verifyDocument(VerifyPemohonPermohonanDocumentRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $tempPath = $file->getRealPath() ?: $file->store('temp/verify', 'local');
        $absolutePath = $tempPath !== $file->getRealPath() ? Storage::disk('local')->path($tempPath) : $tempPath;

        $verification = $this->myKadVerification->verify(
            $absolutePath,
            $request->input('applicant_ic'),
            $originalName,
            $request->input('applicant_name'),
            $request->input('spouse_ic'),
            $request->input('spouse_name'),
        );

        if ($tempPath !== $file->getRealPath()) {
            Storage::disk('local')->delete($tempPath);
        }

        return $this->sendOk([
            'verification' => $verification,
            'name' => $originalName,
        ]);
    }

    public function updateDocumentClass(UpdatePemohonPermohonanDocumentClassRequest $request, int $permohonan, string $attachmentId): JsonResponse
    {
        $validated = $request->validated();
        $record = $this->resolveOwned($permohonan, $validated['access_token']);
        if (! $record) {
            return $this->sendError(404, 'NOT_FOUND', 'Permohonan not found');
        }

        $documentClass = $validated['document_class'];
        $details = is_array($record->details) ? $record->details : [];
        $attachments = is_array($details['attachments'] ?? null) ? $details['attachments'] : [];
        $updated = null;

        foreach ($attachments as $index => $attachment) {
            if (! is_array($attachment) || ($attachment['id'] ?? null) !== $attachmentId) {
                continue;
            }

            $attachment['document_class'] = $documentClass;
            $attachment['document_class_label'] = DocumentClassificationService::labelFor($documentClass);
            if ($documentClass === DocumentClassificationService::CLASS_LAIN_LAIN) {
                $attachment['document_class_other'] = (string) ($validated['document_class_other'] ?? '');
            } else {
                unset($attachment['document_class_other']);
            }

            $attachments[$index] = $attachment;
            $updated = $attachment;
            break;
        }

        if ($updated === null) {
            return $this->sendError(404, 'NOT_FOUND', 'Attachment not found');
        }

        $details['attachments'] = $attachments;
        $record->update(['details' => $details]);

        return $this->sendOk($updated);
    }

    public function deleteDocument(Request $request, int $permohonan, string $attachmentId): JsonResponse
    {
        $record = $this->resolveOwned($permohonan, (string) $request->input('access_token', ''));
        if (! $record) {
            return $this->sendError(404, 'NOT_FOUND', 'Permohonan not found');
        }

        $details = is_array($record->details) ? $record->details : [];
        $attachments = is_array($details['attachments'] ?? null) ? $details['attachments'] : [];

        $remaining = [];
        $deleted = false;

        foreach ($attachments as $attachment) {
            if (! is_array($attachment) || ($attachment['id'] ?? null) !== $attachmentId) {
                $remaining[] = $attachment;

                continue;
            }

            $deleted = true;
            $storagePath = $this->attachmentStoragePath((string) ($attachment['url'] ?? ''));
            if ($storagePath !== null && Storage::disk('public')->exists($storagePath)) {
                Storage::disk('public')->delete($storagePath);
            }
        }

        if (! $deleted) {
            return $this->sendError(404, 'NOT_FOUND', 'Attachment not found');
        }

        $details['attachments'] = $remaining;
        $record->update(['details' => $details]);

        return $this->sendOk(['success' => true]);
    }

    /**
     * POC 1 — runs the fast assessment pipeline right after Hantar. The applicant only sees progress;
     * scores, fraud signals and the recommendation are visible to officers only.
     */
    public function penilaianPantas(StartPemohonPenilaianPantasRequest $request, int $permohonan): JsonResponse
    {
        $record = $this->resolveOwned($permohonan, $request->validated()['access_token']);
        if (! $record) {
            return $this->sendError(404, 'NOT_FOUND', 'Permohonan not found');
        }
        if ($record->status === 'Draf') {
            return $this->sendError(409, 'CONFLICT', 'Permohonan belum dihantar.');
        }

        ignore_user_abort(true);
        set_time_limit(400);

        $run = PenilaianPantas::where('permohonan_id', $record->id)->orderByDesc('id')->first();
        if (! $run || $run->status === 'selesai') {
            $run = $this->penilaianPantas->start($record, null, 'pemohon');
        }
        $run = $this->penilaianPantas->runAll($run);

        return $this->sendOk($this->progressPayload($record->fresh(), $run));
    }

    /**
     * Run one pipeline step for the applicant's latest run (creates it if needed), so the portal can
     * show each step completing live. Steps still run strictly in order; finished runs are not re-run.
     */
    public function penilaianPantasLangkah(StartPemohonPenilaianPantasRequest $request, int $permohonan, string $kod): JsonResponse
    {
        $record = $this->resolveOwned($permohonan, $request->validated()['access_token']);
        if (! $record) {
            return $this->sendError(404, 'NOT_FOUND', 'Permohonan not found');
        }
        if ($record->status === 'Draf') {
            return $this->sendError(409, 'CONFLICT', 'Permohonan belum dihantar.');
        }
        if (! array_key_exists($kod, PenilaianPantasService::LANGKAH)) {
            return $this->sendError(404, 'NOT_FOUND', 'Langkah tidak dikenali');
        }

        ignore_user_abort(true);
        set_time_limit(200);

        $run = PenilaianPantas::where('permohonan_id', $record->id)->orderByDesc('id')->first();
        if (! $run) {
            $run = $this->penilaianPantas->start($record, null, 'pemohon');
        }
        if ($run->status !== 'selesai' && ($run->langkah[$kod]['status'] ?? null) !== 'selesai') {
            try {
                $run = $this->penilaianPantas->runStep($run, $kod);
            } catch (\RuntimeException $e) {
                return $this->sendError(409, 'CONFLICT', $e->getMessage());
            }
        }

        return $this->sendOk($this->progressPayload($record->fresh(), $run));
    }

    /**
     * Applicant view of the latest AI assessment run (progress and officer decision only).
     */
    public function penilaianPantasStatus(Request $request, int $permohonan): JsonResponse
    {
        $token = (string) ($request->input('access_token') ?? $request->query('accessToken', ''));
        $record = $this->resolveOwned($permohonan, $token);
        if (! $record) {
            return $this->sendError(404, 'NOT_FOUND', 'Permohonan not found');
        }

        $run = PenilaianPantas::where('permohonan_id', $record->id)->orderByDesc('id')->first();

        return $this->sendOk($this->progressPayload($record, $run));
    }

    /**
     * e-KYC step 1 — AI OCR of the uploaded MyKad front; the result can auto-fill the form.
     */
    public function scanMyKad(StartPemohonPenilaianPantasRequest $request, int $permohonan): JsonResponse
    {
        $record = $this->resolveOwned($permohonan, $request->validated()['access_token']);
        if (! $record) {
            return $this->sendError(404, 'NOT_FOUND', 'Permohonan not found');
        }

        $mykad = $this->penilaianPantas->myKadFront($record);
        if ($mykad === null) {
            return $this->sendError(422, 'VALIDATION_ERROR', 'Sila muat naik MyKad bahagian depan dahulu.', [
                'mykad' => ['Sila muat naik MyKad bahagian depan dahulu.'],
            ]);
        }

        $details = is_array($record->details) ? $record->details : [];
        $ocr = $this->ekyc->ocrMyKad($mykad['path'], $mykad['mime']);
        $ocrData = is_array($ocr['data'] ?? null) ? $ocr['data'] : [];
        $result = [
            'ocr' => $ocr,
            'ic' => ($ocr['ok'] ?? false) && ! empty($ocrData['no_ic']) ? $this->ekyc->validateIc((string) $ocrData['no_ic']) : null,
            'padanan' => ($ocr['ok'] ?? false) && ! empty($details['nama']) ? $this->ekyc->compareWithApplication($ocrData, $details) : null,
            'disemak_pada' => now()->toIso8601String(),
        ];

        $details['ekyc_mykad'] = $result;
        $record->update(['details' => $details]);

        return $this->sendOk($result);
    }

    /**
     * e-KYC step 2 — live liveness challenge + face match against the MyKad photo.
     */
    public function verifyFace(PemohonEkycWajahRequest $request, int $permohonan): JsonResponse
    {
        $validated = $request->validated();
        $record = $this->resolveOwned($permohonan, $validated['access_token']);
        if (! $record) {
            return $this->sendError(404, 'NOT_FOUND', 'Permohonan not found');
        }

        return $this->sendOk($this->penilaianPantas->verifyFace($record, $validated['frames']));
    }

    public function saveDataAlternatif(PemohonDataAlternatifRequest $request, int $permohonan): JsonResponse
    {
        $validated = $request->validated();
        $record = $this->resolveOwned($permohonan, $validated['access_token']);
        if (! $record) {
            return $this->sendError(404, 'NOT_FOUND', 'Permohonan not found');
        }

        unset($validated['access_token']);
        $details = is_array($record->details) ? $record->details : [];
        $details['data_alternatif'] = $validated;
        $record->update(['details' => $details]);

        return $this->sendOk($validated);
    }

    public function extractPenyata(PemohonEkstrakPenyataRequest $request, int $permohonan): JsonResponse
    {
        $validated = $request->validated();
        $record = $this->resolveOwned($permohonan, $validated['access_token']);
        if (! $record) {
            return $this->sendError(404, 'NOT_FOUND', 'Permohonan not found');
        }

        $file = $request->file('file');

        return $this->sendOk($this->creditScoring->extractFromStatement(
            (string) $file->getRealPath(),
            (string) $file->getMimeType(),
            $validated['jenis'],
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function progressPayload(Permohonan $record, ?PenilaianPantas $run): array
    {
        $keputusan = match ($record->status) {
            'Diluluskan' => 'lulus',
            'Ditolak' => 'tolak',
            default => null,
        };

        if ($run === null) {
            return [
                'status' => 'tiada',
                'langkah' => [],
                'jumlah_ms' => null,
                'hujung_ke_hujung_ms' => null,
                'status_permohonan' => $record->status,
                'keputusan' => $keputusan,
                'mesej' => 'Penilaian AI belum bermula.',
            ];
        }

        return [
            'status' => $run->status,
            'langkah' => collect($run->langkah ?? [])
                ->map(fn (array $l) => ['kod' => $l['kod'], 'label' => $l['label'], 'status' => $l['status'], 'ms' => $l['ms'] ?? null])
                ->values(),
            'jumlah_ms' => $run->jumlah_ms,
            'hujung_ke_hujung_ms' => $run->hujung_ke_hujung_ms,
            'siap_pada' => $run->siap_pada?->toIso8601String(),
            'status_permohonan' => $record->status,
            'keputusan' => $keputusan,
            'mesej' => match (true) {
                $keputusan === 'lulus' => 'Tahniah! Permohonan anda telah diluluskan.',
                $keputusan === 'tolak' => 'Permohonan anda tidak berjaya kali ini. Pegawai akan menghubungi anda.',
                $run->status === 'selesai' => 'Permohonan anda telah dinilai dan dihantar kepada pegawai untuk keputusan.',
                default => 'Permohonan anda sedang dinilai.',
            },
        ];
    }

    /**
     * Only ever returns a record when the token matches a non-null, pemohon-issued token —
     * staff-created records (pemohon_access_token is always null) are never reachable here.
     */
    private function resolveOwned(int $id, string $token): ?Permohonan
    {
        if ($token === '') {
            return null;
        }

        return Permohonan::where('id', $id)
            ->whereNotNull('pemohon_access_token')
            ->where('pemohon_access_token', $token)
            ->first();
    }

    private function attachmentStoragePath(string $url): ?string
    {
        if (! str_starts_with($url, '/storage/')) {
            return null;
        }

        return substr($url, strlen('/storage/'));
    }
}
