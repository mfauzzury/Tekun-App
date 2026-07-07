<?php

namespace Tests\Unit;

use App\Models\Permohonan;
use App\Services\OfferLetterService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class OfferLetterServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_builds_letter_data_from_approved_permohonan(): void
    {
        $permohonan = Permohonan::create([
            'no_rujukan' => 'PM-2026-0001',
            'nama' => 'Ahmad bin Abdullah',
            'kategori_pembiayaan' => 'TEKUN Niaga',
            'jumlah_permohonan' => 50000,
            'status' => 'Diluluskan',
            'details' => [
                'no_ic_baru' => '850101-01-1234',
                'alamat' => 'No. 12, Jalan Merdeka',
                'poskod' => '40000',
                'negeri' => 'Selangor',
                'tempoh_pembiayaan' => '36',
            ],
        ]);

        $service = app(OfferLetterService::class);
        $data = $service->buildLetterData($permohonan);

        $this->assertSame('Ahmad bin Abdullah', $data['applicantName']);
        $this->assertSame('850101-01-1234', $data['applicantIc']);
        $this->assertSame('RM 50,000.00', $data['approvedAmount']);
        $this->assertSame('36 Bulan / 3 Tahun', $data['tenureLabel']);
        $this->assertStringContainsString('TEKUN/PP/PM-2026-0001/', $data['referenceNo']);
        $this->assertStringStartsWith('Skim Pembiayaan', $data['financingScheme']);
    }

    public function test_generates_pdf_binary(): void
    {
        $permohonan = Permohonan::create([
            'no_rujukan' => 'PM-2026-0002',
            'nama' => 'Siti Nurhaliza',
            'kategori_pembiayaan' => 'TEKUN Niaga',
            'jumlah_permohonan' => 30000,
            'status' => 'Diluluskan',
            'details' => [
                'no_ic_baru' => '920315-05-6789',
                'alamat' => 'No. 5, Jalan Bunga',
                'negeri' => 'Johor',
                'tempoh_pembiayaan' => '24',
            ],
        ]);

        $pdf = app(OfferLetterService::class)->generatePdf($permohonan);

        $this->assertNotSame('', $pdf);
        $this->assertSame('%PDF', substr($pdf, 0, 4));
    }

    public function test_offer_letter_fits_single_page(): void
    {
        $permohonan = Permohonan::create([
            'no_rujukan' => 'P-2024-003',
            'nama' => 'Mohd Rizal bin Hassan',
            'kategori_pembiayaan' => 'TEKUN Niaga',
            'jumlah_permohonan' => 75000,
            'status' => 'Diluluskan',
            'details' => [
                'no_ic_baru' => '780512-03-3456',
                'alamat' => 'Kampung Baru, 15100 Kota Bharu',
                'poskod' => '15100',
                'negeri' => 'Kelantan',
                'tempoh_pembiayaan' => '72',
            ],
        ]);

        $service = app(OfferLetterService::class);
        $html = View::make('pdf.surat-tawaran', $service->buildLetterData($permohonan))->render();

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $this->assertSame(1, $dompdf->getCanvas()->get_page_count());
    }
}
