<?php

namespace App\Http\Controllers\Api\Sppt;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnalyzePenilaianPantasDokumenRequest;
use App\Http\Requests\StorePenilaianPantasKeputusanRequest;
use App\Http\Requests\UpdateDataAlternatifRequest;
use App\Http\Requests\VerifyPenilaianPantasWajahRequest;
use App\Http\Traits\ApiResponse;
use App\Models\PenilaianPantas;
use App\Models\Permohonan;
use App\Http\Requests\UpdateBiroKreditRequest;
use App\Services\AlternativeCreditScoringService;
use App\Services\AuditService;
use App\Services\CreditBureauSimulatorService;
use App\Services\FraudNetworkService;
use App\Services\PenilaianPantasService;
use App\Services\PenilaianService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POC 1 — Enjin Penilaian Pantas (C1–C4).
 */
class PenilaianPantasController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PenilaianPantasService $pipeline,
        protected AlternativeCreditScoringService $creditScoring,
        protected FraudNetworkService $fraud,
        protected PenilaianService $penilaianService,
        protected CreditBureauSimulatorService $creditBureau,
        protected AuditService $auditService,
    ) {}

    /**
     * K4 — credit bureau simulator connection state.
     */
    public function biroKredit(): JsonResponse
    {
        return $this->sendOk(['tersambung' => $this->creditBureau->connected()]);
    }

    public function updateBiroKredit(UpdateBiroKreditRequest $request): JsonResponse
    {
        $before = $this->creditBureau->connected();
        $after = (bool) $request->validated()['tersambung'];
        $this->creditBureau->setConnected($after);
        $this->auditService->log('biro_kredit.'.($after ? 'disambung' : 'diputuskan'), $request->user(), 'BiroKredit', null, ['tersambung' => $before], ['tersambung' => $after]);

        return $this->sendOk(['tersambung' => $after]);
    }

    /**
     * Submitted applications with their latest pipeline run.
     */
    public function index(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->input('page', 1));
        $limit = min(100, max(1, (int) $request->input('limit', 20)));
        $q = $request->input('q');

        $query = Permohonan::query()->where('status', '!=', 'Draf');
        if ($q) {
            $query->where(function ($builder) use ($q) {
                $builder->where('nama', 'like', "%{$q}%")->orWhere('no_rujukan', 'like', "%{$q}%");
            });
        }

        $total = $query->count();
        $rows = $query->orderByDesc('updated_at')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get(['id', 'no_rujukan', 'nama', 'status', 'jumlah_permohonan', 'cawangan', 'created_at', 'updated_at']);

        $latest = PenilaianPantas::query()
            ->whereIn('permohonan_id', $rows->pluck('id'))
            ->orderByDesc('id')
            ->get(['id', 'permohonan_id', 'status', 'laluan', 'tahap_risiko', 'skor_kredit', 'hujung_ke_hujung_ms', 'keputusan_pegawai', 'created_at'])
            ->unique('permohonan_id')
            ->keyBy('permohonan_id');

        $data = $rows->map(fn (Permohonan $p) => $p->toArray() + ['penilaian_terkini' => $latest->get($p->id)]);

        return $this->sendOk($data, [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => (int) ceil($total / $limit),
        ]);
    }

    public function show(Permohonan $permohonan): JsonResponse
    {
        $details = is_array($permohonan->details) ? $permohonan->details : [];
        $run = PenilaianPantas::where('permohonan_id', $permohonan->id)->orderByDesc('id')->first();

        return $this->sendOk([
            'permohonan' => [
                'id' => $permohonan->id,
                'no_rujukan' => $permohonan->no_rujukan,
                'nama' => $permohonan->nama,
                'status' => $permohonan->status,
                'jumlah_permohonan' => (float) $permohonan->jumlah_permohonan,
                'cawangan' => $permohonan->cawangan,
                'no_ic' => $details['no_ic_baru'] ?? null,
                'nama_perniagaan' => $details['nama_perniagaan'] ?? null,
                'tempoh_pembiayaan' => $details['tempoh_pembiayaan'] ?? null,
                'dihantar_pada' => $details['dihantar_pada'] ?? null,
                'ada_mykad' => $this->pipeline->myKadFront($permohonan) !== null,
            ],
            'data_alternatif' => $details['data_alternatif'] ?? null,
            'ekyc_wajah' => $details['ekyc_wajah'] ?? null,
            'penilaian' => $run,
            'langkah' => PenilaianPantasService::LANGKAH,
            'sasaran_ms' => PenilaianPantasService::SASARAN_MS,
        ]);
    }

    public function start(Request $request, Permohonan $permohonan): JsonResponse
    {
        return $this->sendCreated($this->pipeline->start($permohonan, $request->user()?->id));
    }

    public function run(PenilaianPantas $run): JsonResponse
    {
        return $this->sendOk($run);
    }

    public function step(PenilaianPantas $run, string $kod): JsonResponse
    {
        if (! array_key_exists($kod, PenilaianPantasService::LANGKAH)) {
            return $this->sendError(404, 'NOT_FOUND', 'Langkah tidak dikenali');
        }

        try {
            return $this->sendOk($this->pipeline->runStep($run, $kod));
        } catch (\RuntimeException $e) {
            return $this->sendError(409, 'CONFLICT', $e->getMessage());
        }
    }

    /**
     * Officer decision (human-in-the-loop). Approval re-checks fraud signals before the account is opened.
     */
    public function decide(StorePenilaianPantasKeputusanRequest $request, PenilaianPantas $run): JsonResponse
    {
        if ($run->status !== 'selesai') {
            return $this->sendError(409, 'CONFLICT', 'Penilaian belum selesai.');
        }
        if ($run->keputusan_pegawai !== null) {
            return $this->sendError(409, 'CONFLICT', 'Keputusan telah direkodkan untuk penilaian ini.');
        }

        $data = $request->validated();
        $permohonan = $run->permohonan()->firstOrFail();
        $cadangan = $run->cadangan ?? [];
        $semakan = null;

        if ($data['keputusan'] === 'lulus') {
            $semakan = $this->pipeline->disbursementCheck($permohonan);
            if (($semakan['amaran'] ?? false) && ! ($data['akui_amaran'] ?? false)) {
                return $this->sendError(409, 'DISBURSEMENT_ALERT', 'Amaran penipuan dikesan sebelum pengeluaran dana.', $semakan);
            }
            if (($semakan['amaran'] ?? false) && trim((string) ($data['catatan'] ?? '')) === '') {
                return $this->sendError(422, 'VALIDATION_ERROR', 'Catatan justifikasi diperlukan untuk meluluskan kes beramaran.', [
                    'catatan' => ['Catatan justifikasi diperlukan untuk meluluskan kes beramaran.'],
                ]);
            }
            if (($cadangan['laluan'] ?? null) !== 'pantas' && trim((string) ($data['catatan'] ?? '')) === '') {
                return $this->sendError(422, 'VALIDATION_ERROR', 'Catatan diperlukan untuk kelulusan di luar laluan pantas.', [
                    'catatan' => ['Catatan diperlukan untuk kelulusan di luar laluan pantas.'],
                ]);
            }
        }

        $akaun = null;
        if (in_array($data['keputusan'], ['lulus', 'tolak'], true)) {
            $jumlah = (float) ($data['jumlah_diluluskan'] ?? $cadangan['jumlah_dicadang'] ?? $permohonan->jumlah_permohonan);
            $result = $this->penilaianService->save($permohonan, [
                'keputusan' => $data['keputusan'] === 'lulus' ? 'Lulus' : 'Tolak',
                'skor_kredit' => (string) ($run->skor_kredit ?? ''),
                'cadangan_pembiayaan' => (string) $jumlah,
                'tempoh_pembiayaan' => $cadangan['tempoh_bulan'] ?? null,
                'catatan' => trim('[Penilaian Pantas #'.$run->id.'] '.($data['catatan'] ?? '')),
            ]);
            $akaun = $result['akaun'];
        }

        $run->update([
            'keputusan_pegawai' => $data['keputusan'],
            'keputusan_oleh' => $request->user()?->id,
            'keputusan_pada' => now(),
            'catatan_pegawai' => $data['catatan'] ?? null,
        ]);

        return $this->sendOk([
            'penilaian' => $run->fresh(),
            'akaun' => $akaun,
            'semakan_pengeluaran' => $semakan,
        ]);
    }

    public function verifyFace(VerifyPenilaianPantasWajahRequest $request, Permohonan $permohonan): JsonResponse
    {
        return $this->sendOk($this->pipeline->verifyFace($permohonan, $request->validated()['frames']));
    }

    public function updateDataAlternatif(UpdateDataAlternatifRequest $request, Permohonan $permohonan): JsonResponse
    {
        $details = is_array($permohonan->details) ? $permohonan->details : [];
        $details['data_alternatif'] = $request->validated();
        $permohonan->update(['details' => $details]);

        return $this->sendOk($details['data_alternatif']);
    }

    public function extractDataAlternatif(AnalyzePenilaianPantasDokumenRequest $request, Permohonan $permohonan): JsonResponse
    {
        $jenis = (string) ($request->validated()['jenis'] ?? '');
        if ($jenis === '') {
            return $this->sendError(422, 'VALIDATION_ERROR', 'Jenis penyata diperlukan.', ['jenis' => ['Jenis penyata diperlukan.']]);
        }

        $file = $request->file('file');

        return $this->sendOk($this->creditScoring->extractFromStatement(
            (string) $file->getRealPath(),
            (string) $file->getMimeType(),
            $jenis,
        ));
    }

    public function analyzeSalesDocument(AnalyzePenilaianPantasDokumenRequest $request, Permohonan $permohonan): JsonResponse
    {
        $file = $request->file('file');

        return $this->sendOk($this->fraud->analyzeSalesDocument((string) $file->getRealPath(), (string) $file->getMimeType()));
    }

    public function disbursementCheck(Permohonan $permohonan): JsonResponse
    {
        return $this->sendOk($this->pipeline->disbursementCheck($permohonan));
    }

    public function fairness(): JsonResponse
    {
        return $this->sendOk($this->creditScoring->fairnessReport());
    }
}
