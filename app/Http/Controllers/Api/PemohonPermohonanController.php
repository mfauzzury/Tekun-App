<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
use App\Services\DocumentClassificationService;
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

            $identityError = $this->myKadVerification->firstIdentityValidationError($attachments);
            if ($identityError !== null) {
                return $this->sendError(422, 'VALIDATION_ERROR', 'Validation failed', [
                    'attachments' => [$identityError],
                ]);
            }

            $coverage = $this->myKadVerification->summarizeApplicantIcCoverage($attachments);
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

        return $this->sendOk([
            'status' => $run->status,
            'langkah' => collect($run->langkah ?? [])
                ->map(fn (array $l) => ['kod' => $l['kod'], 'label' => $l['label'], 'status' => $l['status'], 'ms' => $l['ms'] ?? null])
                ->values(),
            'mesej' => $run->status === 'selesai'
                ? 'Permohonan anda telah dinilai dan dihantar kepada pegawai untuk keputusan.'
                : 'Penilaian awal tidak lengkap. Pegawai akan menyemak permohonan anda.',
        ]);
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
