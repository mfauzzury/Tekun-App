<?php

namespace App\Http\Controllers\Api\Sppt;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExtractPermohonanFormOcrRequest;
use App\Http\Requests\ClassifyPermohonanDocumentRequest;
use App\Http\Requests\StorePermohonanDocumentRequest;
use App\Http\Requests\StorePermohonanRequest;
use App\Http\Requests\UpdatePermohonanDocumentClassRequest;
use App\Http\Requests\UpdatePermohonanRequest;
use App\Http\Requests\VerifyPermohonanDocumentRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Permohonan;
use App\Models\Usahawan;
use App\Services\DocumentClassificationService;
use App\Services\MyKadVerificationService;
use App\Services\PermohonanFormOcrService;
use App\Services\SpptViewTransform;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PermohonanController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected MyKadVerificationService $myKadVerification,
        protected DocumentClassificationService $documentClassification,
        protected PermohonanFormOcrService $formOcr,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $q = $request->input('q');
        $status = $request->input('status');

        $query = Permohonan::query()->with('usahawan');

        if ($q) {
            $query->where(function ($builder) use ($q) {
                $builder->where('no_rujukan', 'like', "%{$q}%")
                    ->orWhere('nama', 'like', "%{$q}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $total = $query->count();
        $rows = $query->orderByDesc('created_at')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        if ($request->boolean('penilaian')) {
            $rows = $rows->map(fn (Permohonan $row) => SpptViewTransform::permohonanPenilaian([
                'id' => $row->id,
                'noRujukan' => $row->no_rujukan,
                'nama' => $row->nama,
                'jumlahPermohonan' => $row->jumlah_permohonan,
                'tarikhPermohonan' => $row->tarikh_permohonan?->format('Y-m-d'),
                'status' => $row->status,
            ]));
        }

        return $this->sendOk($rows, [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => (int) ceil(max($total, 1) / $limit),
        ]);
    }

    public function summary(): JsonResponse
    {
        return $this->sendOk([
            'jumlah' => Permohonan::count(),
            'draf' => Permohonan::where('status', 'Draf')->count(),
            'dalamProses' => Permohonan::where('status', 'Dalam Proses')->count(),
            'menungguDokumen' => Permohonan::where('status', 'Menunggu Dokumen')->count(),
            'selesaiBulanIni' => Permohonan::where('status', 'Lengkap')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count(),
        ]);
    }

    public function store(StorePermohonanRequest $request): JsonResponse
    {
        $data = $this->preparePermohonanData($request->validated());
        if (empty($data['no_rujukan'])) {
            $data['no_rujukan'] = 'PM-'.now()->format('Y').'-'.str_pad((string) (Permohonan::count() + 1), 4, '0', STR_PAD_LEFT);
        }

        $permohonan = Permohonan::create($data);

        return $this->sendCreated($permohonan);
    }

    public function show(int $id): JsonResponse
    {
        $permohonan = Permohonan::with('usahawan')->find($id);
        if (! $permohonan) {
            return $this->sendError(404, 'NOT_FOUND', 'Permohonan not found');
        }

        return $this->sendOk($permohonan);
    }

    public function update(UpdatePermohonanRequest $request, int $id): JsonResponse
    {
        $permohonan = Permohonan::find($id);
        if (! $permohonan) {
            return $this->sendError(404, 'NOT_FOUND', 'Permohonan not found');
        }

        $validated = $request->validated();
        $existingDetails = is_array($permohonan->details) ? $permohonan->details : [];
        if (isset($validated['details']) && is_array($validated['details'])) {
            if (! array_key_exists('attachments', $validated['details']) && ! empty($existingDetails['attachments'])) {
                $validated['details']['attachments'] = $existingDetails['attachments'];
            }
        }

        $targetStatus = $validated['status'] ?? $permohonan->status;
        if ($targetStatus !== 'Draf') {
            $details = is_array($validated['details'] ?? null)
                ? array_merge($existingDetails, $validated['details'])
                : $existingDetails;
            $attachments = is_array($details['attachments'] ?? null) ? $details['attachments'] : [];

            if (count($attachments) === 0) {
                return $this->sendError(422, 'VALIDATION_ERROR', 'Validation failed', [
                    'attachments' => ['Sila lampirkan dokumen sokongan.'],
                ]);
            }

            $applicantIc = (string) ($details['no_ic_baru'] ?? '');
            $applicantName = (string) ($details['nama'] ?? $validated['nama'] ?? $permohonan->nama ?? '');
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

            $validated['details'] = array_merge($details, $validated['details'] ?? []);
            $validated['details']['attachments'] = $attachments;
        }

        $permohonan->update($this->preparePermohonanData($validated));

        return $this->sendOk($permohonan);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preparePermohonanData(array $data): array
    {
        $details = is_array($data['details'] ?? null) ? $data['details'] : [];
        $status = $data['status'] ?? 'Dalam Proses';

        if (! empty($details['no_usahawan'])) {
            $usahawan = Usahawan::where('no_usahawan', $details['no_usahawan'])->first();
            if ($usahawan) {
                $data['usahawan_id'] = $usahawan->id;
            }
        }

        if (empty($data['nama'])) {
            $data['nama'] = $details['nama'] ?? 'Draf';
        }

        if (empty($data['kategori_pembiayaan']) && ! empty($details['kategori_pembiayaan'])) {
            $data['kategori_pembiayaan'] = $details['kategori_pembiayaan'];
        }

        if (! isset($data['jumlah_permohonan']) && ! empty($details['jumlah_permohonan'])) {
            $data['jumlah_permohonan'] = (float) $details['jumlah_permohonan'];
        }

        if ($status === 'Draf') {
            $data['status'] = 'Draf';
        } elseif (empty($data['status'])) {
            $data['status'] = 'Dalam Proses';
        }

        if ($data['status'] !== 'Draf' && empty($data['tarikh_permohonan'])) {
            $data['tarikh_permohonan'] = now()->toDateString();
        }

        return $data;
    }

    public function uploadDocument(StorePermohonanDocumentRequest $request, int $id): JsonResponse
    {
        $permohonan = Permohonan::find($id);
        if (! $permohonan) {
            return $this->sendError(404, 'NOT_FOUND', 'Permohonan not found');
        }

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $safeBase = preg_replace('/-+/', '-', preg_replace('/[^a-z0-9.\-_]/', '-', strtolower($originalName)));
        $ext = pathinfo($safeBase, PATHINFO_EXTENSION);
        $name = pathinfo($safeBase, PATHINFO_FILENAME);
        $filename = $name.'-'.time().'.'.$ext;

        $file->storeAs('permohonan/'.$id, $filename, 'public');

        $attachment = [
            'id' => (string) Str::uuid(),
            'name' => $originalName,
            'size' => $file->getSize(),
            'url' => '/storage/permohonan/'.$id.'/'.$filename,
            'mime_type' => $file->getMimeType(),
        ];

        $documentClass = $request->input('document_class');
        if (is_string($documentClass) && $documentClass !== '') {
            $attachment['document_class'] = $documentClass;
            $attachment['document_class_label'] = DocumentClassificationService::labelFor($documentClass);
            if ($documentClass === DocumentClassificationService::CLASS_LAIN_LAIN) {
                $attachment['document_class_other'] = (string) $request->input('document_class_other', '');
            }
        }

        $details = is_array($permohonan->details) ? $permohonan->details : [];
        $attachments = is_array($details['attachments'] ?? null) ? $details['attachments'] : [];
        $attachments[] = $attachment;
        $details['attachments'] = $attachments;
        unset($details['attachment_names']);

        $permohonan->update(['details' => $details]);

        return $this->sendCreated($attachment);
    }

    public function extractFormOcr(ExtractPermohonanFormOcrRequest $request): JsonResponse
    {
        try {
            $result = $this->formOcr->extractFromUpload($request->file('file'));

            return $this->sendOk($result);
        } catch (\RuntimeException $exception) {
            return $this->sendError(422, 'BAD_REQUEST', $exception->getMessage());
        }
    }

    public function classifyDocument(ClassifyPermohonanDocumentRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $tempPath = $file->getRealPath() ?: $file->store('temp/classify', 'local');

        if ($tempPath !== $file->getRealPath()) {
            $absolutePath = Storage::disk('local')->path($tempPath);
        } else {
            $absolutePath = $tempPath;
        }

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

    public function updateDocumentClass(UpdatePermohonanDocumentClassRequest $request, int $id, string $attachmentId): JsonResponse
    {
        $permohonan = Permohonan::find($id);
        if (! $permohonan) {
            return $this->sendError(404, 'NOT_FOUND', 'Permohonan not found');
        }

        $documentClass = (string) $request->input('document_class');
        $details = is_array($permohonan->details) ? $permohonan->details : [];
        $attachments = is_array($details['attachments'] ?? null) ? $details['attachments'] : [];
        $updated = null;

        foreach ($attachments as $index => $attachment) {
            if (! is_array($attachment) || ($attachment['id'] ?? null) !== $attachmentId) {
                continue;
            }

            $attachment['document_class'] = $documentClass;
            $attachment['document_class_label'] = DocumentClassificationService::labelFor($documentClass);
            if ($documentClass === DocumentClassificationService::CLASS_LAIN_LAIN) {
                $attachment['document_class_other'] = (string) $request->input('document_class_other', '');
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
        $permohonan->update(['details' => $details]);

        return $this->sendOk($updated);
    }

    public function verifyDocument(VerifyPermohonanDocumentRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $tempPath = $file->getRealPath() ?: $file->store('temp/verify', 'local');

        if ($tempPath !== $file->getRealPath()) {
            $absolutePath = Storage::disk('local')->path($tempPath);
        } else {
            $absolutePath = $tempPath;
        }

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

    public function showDocument(int $id, string $attachmentId)
    {
        $permohonan = Permohonan::find($id);
        if (! $permohonan) {
            return $this->sendError(404, 'NOT_FOUND', 'Permohonan not found');
        }

        $details = is_array($permohonan->details) ? $permohonan->details : [];
        $attachments = is_array($details['attachments'] ?? null) ? $details['attachments'] : [];

        foreach ($attachments as $attachment) {
            if (! is_array($attachment) || ($attachment['id'] ?? null) !== $attachmentId) {
                continue;
            }

            $storagePath = $this->attachmentStoragePath((string) ($attachment['url'] ?? ''));
            if ($storagePath === null || ! Storage::disk('public')->exists($storagePath)) {
                return $this->sendError(404, 'NOT_FOUND', 'Attachment file not found');
            }

            $absolutePath = Storage::disk('public')->path($storagePath);
            $downloadName = (string) ($attachment['name'] ?? basename($storagePath));
            $mimeType = (string) ($attachment['mime_type'] ?? Storage::disk('public')->mimeType($storagePath));

            return response()->file($absolutePath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="'.addslashes($downloadName).'"',
            ]);
        }

        return $this->sendError(404, 'NOT_FOUND', 'Attachment not found');
    }

    public function deleteDocument(int $id, string $attachmentId): JsonResponse
    {
        $permohonan = Permohonan::find($id);
        if (! $permohonan) {
            return $this->sendError(404, 'NOT_FOUND', 'Permohonan not found');
        }

        $details = is_array($permohonan->details) ? $permohonan->details : [];
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
        $permohonan->update(['details' => $details]);

        return $this->sendOk(['success' => true]);
    }

    private function attachmentStoragePath(string $url): ?string
    {
        if (! str_starts_with($url, '/storage/')) {
            return null;
        }

        return substr($url, strlen('/storage/'));
    }

    public function destroy(int $id): JsonResponse
    {
        Permohonan::where('id', $id)->delete();

        return $this->sendOk(['success' => true]);
    }
}
