<?php

namespace Tests\Feature;

use App\Models\AkaunPembiayaan;
use App\Models\Permohonan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PermohonanPenilaianTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsEditor(): User
    {
        $role = Role::create([
            'name' => 'admin',
            'description' => 'Admin',
            'permissions' => ['sppt.view', 'sppt.edit'],
        ]);

        $user = User::factory()->create([
            'role' => 'admin',
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($user);

        return $user;
    }

    public function test_penilaian_requires_auth(): void
    {
        $permohonan = Permohonan::create([
            'no_rujukan' => 'PM-2026-0100',
            'nama' => 'Ali Test',
            'status' => 'Dalam Proses',
            'jumlah_permohonan' => 50000,
            'details' => ['no_ic_baru' => '850101145678'],
        ]);

        $this->postJson("/api/sppt/permohonan/{$permohonan->id}/penilaian", [
            'keputusan' => 'Lulus',
        ])->assertStatus(401);
    }

    public function test_penilaian_list_excludes_draft_applications(): void
    {
        $this->actingAsEditor();

        Permohonan::create([
            'no_rujukan' => 'PM-2026-DRAF',
            'nama' => 'Draf Applicant',
            'status' => 'Draf',
            'jumlah_permohonan' => 10000,
        ]);

        Permohonan::create([
            'no_rujukan' => 'PM-2026-READY',
            'nama' => 'Ready Applicant',
            'status' => 'Dalam Proses',
            'jumlah_permohonan' => 20000,
        ]);

        $response = $this->getJson('/api/sppt/permohonan?penilaian=1&limit=100');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains('PM-2026-READY', $ids);
        $this->assertNotContains('PM-2026-DRAF', $ids);
    }

    public function test_penilaian_baru_list_only_includes_dalam_proses_and_disemak(): void
    {
        $this->actingAsEditor();

        Permohonan::create([
            'no_rujukan' => 'PM-2026-PROSES',
            'nama' => 'Proses Applicant',
            'status' => 'Dalam Proses',
            'jumlah_permohonan' => 10000,
        ]);

        Permohonan::create([
            'no_rujukan' => 'PM-2026-DISEMAK',
            'nama' => 'Disemak Applicant',
            'status' => 'Disemak',
            'jumlah_permohonan' => 15000,
        ]);

        Permohonan::create([
            'no_rujukan' => 'PM-2026-LULUS',
            'nama' => 'Lulus Applicant',
            'status' => 'Diluluskan',
            'jumlah_permohonan' => 20000,
        ]);

        Permohonan::create([
            'no_rujukan' => 'PM-2026-DRAF2',
            'nama' => 'Draf Applicant',
            'status' => 'Draf',
            'jumlah_permohonan' => 5000,
        ]);

        $response = $this->getJson('/api/sppt/permohonan?penilaian=1&limit=100&statuses='.urlencode('Dalam Proses,Disemak'));

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains('PM-2026-PROSES', $ids);
        $this->assertContains('PM-2026-DISEMAK', $ids);
        $this->assertNotContains('PM-2026-LULUS', $ids);
        $this->assertNotContains('PM-2026-DRAF2', $ids);
    }

    public function test_penilaian_save_persists_formula_and_opens_account(): void
    {
        $this->actingAsEditor();

        $permohonan = Permohonan::create([
            'no_rujukan' => 'PM-2026-0101',
            'nama' => 'Ali Test',
            'kategori_pembiayaan' => 'TEKUN Niaga',
            'status' => 'Dalam Proses',
            'jumlah_permohonan' => 50000,
            'details' => [
                'no_ic_baru' => '850101145678',
                'negeri' => 'Selangor',
            ],
        ]);

        $response = $this->postJson("/api/sppt/permohonan/{$permohonan->id}/penilaian", [
            'keputusan' => 'Lulus',
            'skor_kredit' => '78',
            'cadangan_pembiayaan' => 'RM 50000',
            'cadangan_jenis_pembiayaan' => 'Tekun Niaga',
            'kadar_keuntungan_tahunan' => 4,
            'tempoh_pembiayaan' => 36,
            'kaedah_pengiraan' => 'reducing_balance',
            'buka_akaun' => true,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'permohonan' => ['status', 'details'],
                    'akaun' => ['id', 'bayaranBulanan', 'kaedahPengiraan', 'kadarKeuntunganTahunan'],
                ],
            ]);

        $permohonan->refresh();
        $this->assertSame('Diluluskan', $permohonan->status);
        $this->assertIsArray($permohonan->details['penilaian'] ?? null);
        $this->assertIsArray($permohonan->details['formula_pembiayaan'] ?? null);
        $this->assertSame('reducing_balance', $permohonan->details['penilaian']['kaedah_pengiraan']);

        $akaun = AkaunPembiayaan::where('permohonan_id', $permohonan->id)->first();
        $this->assertNotNull($akaun);
        $this->assertSame('reducing_balance', $akaun->kaedah_pengiraan);
        $this->assertSame(36, $akaun->jadualAnsuran()->count());
    }

    public function test_offer_letter_uses_saved_formula_installment(): void
    {
        $this->actingAsEditor();

        $permohonan = Permohonan::create([
            'no_rujukan' => 'PM-2026-0102',
            'nama' => 'Siti Test',
            'kategori_pembiayaan' => 'TEKUN Niaga',
            'status' => 'Diluluskan',
            'jumlah_permohonan' => 100000,
            'details' => [
                'no_ic_baru' => '900101145678',
                'penilaian' => [
                    'kaedah_pengiraan' => 'flat_rate',
                    'kadar_keuntungan' => 4,
                    'tempoh_pembiayaan' => 36,
                ],
                'formula_pembiayaan' => [
                    'kaedah_pengiraan' => 'flat_rate',
                    'kaedah_label' => 'Kadar Rata (Flat Rate)',
                    'ansuran_bulanan' => 3111.11,
                    'jumlah_bayaran_balik' => 112000,
                    'jumlah_keuntungan' => 12000,
                ],
            ],
        ]);

        $data = app(\App\Services\OfferLetterService::class)->buildLetterData($permohonan);

        $this->assertSame('RM 3,111.11', $data['monthlyInstallment']);
        $this->assertSame('Kadar Rata (Flat Rate)', $data['profitMethodLabel']);
        $this->assertSame('RM 112,000.00', $data['totalRepaymentLabel']);
    }
}
