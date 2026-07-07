<?php

namespace App\Services;

use App\Models\Permohonan;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class OfferLetterService
{
    /** @var list<string> */
    private const APPROVED_STATUSES = ['Diluluskan', 'Lulus', 'Berjaya'];

    public function isApproved(Permohonan $permohonan): bool
    {
        return in_array((string) $permohonan->status, self::APPROVED_STATUSES, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildLetterData(Permohonan $permohonan): array
    {
        $config = config('sppt.offer_letter', []);
        $details = is_array($permohonan->details) ? $permohonan->details : [];
        $penilaian = is_array($details['penilaian'] ?? null) ? $details['penilaian'] : [];
        $credit = is_array($details['ai_credit_scoring'] ?? null) ? $details['ai_credit_scoring'] : [];

        $approvedAmount = $this->resolveApprovedAmount($permohonan, $details, $penilaian, $credit);
        $months = $this->resolveTenureMonths($details, $penilaian);
        $profitRate = $this->resolveProfitRate($penilaian, $config);
        $monthlyInstallment = $this->calculateMonthlyInstallment($approvedAmount, $profitRate, $months);

        $letterDate = now();
        $noRujukan = (string) ($permohonan->no_rujukan ?? 'PM-'.$permohonan->id);
        $year = $letterDate->format('Y');

        return [
            'logoBase64' => $this->logoBase64((string) ($config['logo_path'] ?? '')),
            'branchAddress' => (string) ($config['branch_address'] ?? ''),
            'branchPhone' => (string) ($config['branch_phone'] ?? ''),
            'branchEmail' => (string) ($config['branch_email'] ?? ''),
            'referenceNo' => 'TEKUN/PP/'.$noRujukan.'/'.$year,
            'letterDate' => $this->formatTarikhMalay($letterDate),
            'applicantName' => (string) ($permohonan->nama ?: ($details['nama'] ?? '')),
            'applicantIc' => (string) ($details['no_ic_baru'] ?? $details['no_ic'] ?? ''),
            'applicantAddress' => $this->formatApplicantAddress($details),
            'financingScheme' => $this->resolveFinancingScheme($permohonan, $details, $penilaian),
            'approvedAmount' => $this->formatCurrency($approvedAmount),
            'tenureLabel' => $this->formatTenureLabel($months),
            'profitRateLabel' => number_format($profitRate, 1).'% Setahun',
            'monthlyInstallment' => $this->formatCurrency($monthlyInstallment),
            'syariahConcept' => (string) ($penilaian['konsep_syariah'] ?? $config['default_syariah_concept'] ?? 'Tawarruq'),
            'acceptanceDays' => (int) ($config['acceptance_days'] ?? 14),
            'signatoryName' => (string) ($config['signatory_name'] ?? ''),
            'signatoryTitle' => (string) ($config['signatory_title'] ?? 'Pegawai Pembiayaan'),
        ];
    }

    public function generatePdf(Permohonan $permohonan): string
    {
        $html = View::make('pdf.surat-tawaran', $this->buildLetterData($permohonan))->render();

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return (string) $dompdf->output();
    }

    public function pdfResponse(Permohonan $permohonan): Response
    {
        $filename = $this->downloadFilename($permohonan);
        $pdf = $this->generatePdf($permohonan);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($pdf),
        ]);
    }

    public function downloadFilename(Permohonan $permohonan): string
    {
        $ref = preg_replace('/[^A-Za-z0-9\-_]/', '-', (string) ($permohonan->no_rujukan ?? $permohonan->id));

        return 'Surat-Tawaran-'.trim((string) $ref, '-').'.pdf';
    }

    /**
     * @param  array<string, mixed>  $details
     * @param  array<string, mixed>  $penilaian
     * @param  array<string, mixed>  $credit
     */
    private function resolveApprovedAmount(
        Permohonan $permohonan,
        array $details,
        array $penilaian,
        array $credit,
    ): float {
        $candidates = [
            $penilaian['cadangan_pembiayaan'] ?? null,
            $penilaian['jumlah_diluluskan'] ?? null,
            $credit['recommended_limit'] ?? null,
            $details['jumlah_diluluskan'] ?? null,
            $details['jumlah_permohonan'] ?? null,
            $permohonan->jumlah_permohonan,
        ];

        foreach ($candidates as $candidate) {
            $amount = $this->parseAmount($candidate);
            if ($amount !== null && $amount > 0) {
                return $amount;
            }
        }

        return 0.0;
    }

    /**
     * @param  array<string, mixed>  $details
     * @param  array<string, mixed>  $penilaian
     */
    private function resolveTenureMonths(array $details, array $penilaian): int
    {
        $raw = $penilaian['tempoh_pembiayaan']
            ?? $details['tempoh_pembiayaan']
            ?? $details['tempoh_pembiayaan_bulan']
            ?? 36;

        $months = (int) preg_replace('/\D/', '', (string) $raw);

        return max(1, $months);
    }

    /**
     * @param  array<string, mixed>  $penilaian
     * @param  array<string, mixed>  $config
     */
    private function resolveProfitRate(array $penilaian, array $config): float
    {
        if (isset($penilaian['kadar_keuntungan'])) {
            $parsed = $this->parseAmount($penilaian['kadar_keuntungan']);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        return (float) ($config['default_profit_rate'] ?? 4);
    }

    /**
     * @param  array<string, mixed>  $details
     * @param  array<string, mixed>  $penilaian
     */
    private function resolveFinancingScheme(Permohonan $permohonan, array $details, array $penilaian): string
    {
        $scheme = (string) (
            $penilaian['cadangan_jenis_pembiayaan']
            ?? $penilaian['skim_pembiayaan']
            ?? $permohonan->kategori_pembiayaan
            ?? $details['kategori_pembiayaan']
            ?? 'TEKUN Niaga'
        );

        if (! str_contains(strtolower($scheme), 'skim')) {
            return 'Skim Pembiayaan '.$scheme;
        }

        return $scheme;
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function formatApplicantAddress(array $details): string
    {
        $parts = array_filter([
            $details['alamat'] ?? null,
            $details['poskod'] ?? null,
            $details['negeri'] ?? null,
        ], fn ($part) => is_string($part) && trim($part) !== '');

        return implode(', ', $parts) ?: '—';
    }

    private function calculateMonthlyInstallment(float $principal, float $annualRate, int $months): float
    {
        if ($principal <= 0 || $months <= 0) {
            return 0.0;
        }

        $years = $months / 12;
        $totalRepayment = $principal + ($principal * ($annualRate / 100) * $years);

        return round($totalRepayment / $months, 2);
    }

    private function formatTenureLabel(int $months): string
    {
        $years = intdiv($months, 12);
        $remainingMonths = $months % 12;

        if ($years > 0 && $remainingMonths === 0) {
            return $months.' Bulan / '.$years.' Tahun';
        }

        if ($years > 0) {
            return $months.' Bulan / '.$years.' Tahun '.$remainingMonths.' Bulan';
        }

        return $months.' Bulan';
    }

    private function formatCurrency(float $amount): string
    {
        return 'RM '.number_format($amount, 2);
    }

    private function formatTarikhMalay(Carbon $date): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Mac', 4 => 'April', 5 => 'Mei', 6 => 'Jun',
            7 => 'Julai', 8 => 'Ogos', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Disember',
        ];

        return $date->day.' '.($months[$date->month] ?? $date->format('F')).' '.$date->year;
    }

    private function parseAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = preg_replace('/[^0-9.]/', '', (string) $value);

        return $clean !== '' ? (float) $clean : null;
    }

    private function logoBase64(string $path): ?string
    {
        if ($path === '' || ! is_file($path)) {
            return null;
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
