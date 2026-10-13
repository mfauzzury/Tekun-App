<?php

namespace Tests\Feature;

use App\Models\PenilaianPantas;
use App\Models\Permohonan;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PocSindiketDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PenilaianPantasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // No AI key: every service must degrade gracefully and never call out.
        config(['services.anthropic.key' => null]);
        Http::fake();
    }

    private function actingAsOfficer(array $permissions = ['sppt.view', 'sppt.edit', 'sppt.kelulusan']): User
    {
        $role = Role::create([
            'name' => 'pegawai-'.uniqid(),
            'description' => 'Pegawai',
            'permissions' => $permissions,
        ]);
        $user = User::factory()->create(['role' => 'admin', 'role_id' => $role->id]);
        Sanctum::actingAs($user);

        return $user;
    }

    private function permohonan(array $details = [], array $attrs = []): Permohonan
    {
        return Permohonan::create(array_merge([
            'no_rujukan' => 'PM-TEST-'.uniqid(),
            'nama' => 'Aminah Binti Yusof',
            'kategori_pembiayaan' => 'TEKUN Niaga',
            'status' => 'Dalam Semakan',
            'jumlah_permohonan' => 5000,
            'tarikh_permohonan' => now()->toDateString(),
            'details' => array_merge([
                'nama' => 'Aminah Binti Yusof',
                'no_ic_baru' => '880512-10-5372',
                'alamat' => 'No 3, Jalan Kenanga 2, Taman Kenanga',
                'poskod' => '43000',
                'no_telefon_bimbit' => '0137788123',
                'pendapatan' => 3000,
                'pendapatan_bulan' => 1,
                'tempoh_pembiayaan' => 24,
                'tempoh_berniaga' => '4',
                'dihantar_pada' => now()->subMinute()->toIso8601String(),
                'data_alternatif' => [
                    'ewallet' => ['penyedia' => 'Touch n Go eWallet', 'purata_masuk_bulanan' => 2800, 'bilangan_transaksi_bulanan' => 320, 'bulan_aktif' => 10],
                    'duitnow_qr' => ['purata_jualan_bulanan' => 3100, 'bilangan_transaksi_bulanan' => 410, 'bulan_aktif' => 10, 'trend_pertumbuhan_peratus' => 6],
                    'bil_utiliti' => ['bulan_dibayar_tepat' => 11, 'bulan_dinilai' => 12],
                    'ccris_tersedia' => false,
                    'penyata_bank_tersedia' => false,
                ],
            ], $details),
        ], $attrs));
    }

    private function runAllSteps(Permohonan $permohonan): array
    {
        $run = $this->postJson("/api/sppt/penilaian-pantas/permohonan/{$permohonan->id}/mula")
            ->assertCreated()
            ->json('data');

        foreach (['ekyc', 'peraturan', 'penipuan', 'skor_kredit', 'cadangan'] as $kod) {
            $run = $this->postJson("/api/sppt/penilaian-pantas/run/{$run['id']}/langkah/{$kod}")
                ->assertOk()
                ->json('data');
        }

        return $run;
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/sppt/penilaian-pantas')->assertStatus(401);
        $this->postJson('/api/sppt/penilaian-pantas/permohonan/1/mula')->assertStatus(401);
    }

    public function test_requires_permissions(): void
    {
        $permohonan = $this->permohonan();
        $this->actingAsOfficer(['posts.view']);
        $this->getJson('/api/sppt/penilaian-pantas')->assertStatus(403)->assertJsonPath('error.code', 'FORBIDDEN');

        $this->actingAsOfficer(['sppt.view']);
        $this->postJson("/api/sppt/penilaian-pantas/permohonan/{$permohonan->id}/mula")->assertStatus(403);
    }

    public function test_index_lists_submitted_applications_with_pagination(): void
    {
        $this->permohonan();
        $this->permohonan([], ['status' => 'Draf']);
        $this->actingAsOfficer();

        $this->getJson('/api/sppt/penilaian-pantas')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data' => [['id', 'noRujukan', 'nama', 'penilaianTerkini']], 'meta' => ['page', 'limit', 'total', 'totalPages']]);
    }

    public function test_full_pipeline_records_step_latency_and_routes_to_officer_without_ekyc(): void
    {
        $permohonan = $this->permohonan();
        $this->actingAsOfficer();

        $run = $this->runAllSteps($permohonan);

        $this->assertSame('selesai', $run['status']);
        foreach (['ekyc', 'peraturan', 'penipuan', 'skorKredit', 'cadangan'] as $key) {
            $this->assertSame('selesai', $run['langkah'][$key]['status'], $key);
            $this->assertIsInt($run['langkah'][$key]['ms']);
        }
        // No MyKad image / face check → identity needs officer review, so no fast lane.
        $this->assertSame('semak', $run['langkah']['ekyc']['keputusan']);
        $this->assertSame('semakan_pegawai', $run['laluan']);
        $this->assertTrue($run['langkah']['skorKredit']['hasil']['failNipis']);
        $this->assertNotNull($run['hujungKeHujungMs']);
        $this->assertGreaterThanOrEqual(60000, $run['hujungKeHujungMs']);
        $this->assertNotEmpty($run['cadangan']['notaHitl']);

        Http::assertNothingSent();
    }

    public function test_steps_must_run_in_order(): void
    {
        $permohonan = $this->permohonan();
        $this->actingAsOfficer();
        $run = $this->postJson("/api/sppt/penilaian-pantas/permohonan/{$permohonan->id}/mula")->json('data');

        $this->postJson("/api/sppt/penilaian-pantas/run/{$run['id']}/langkah/cadangan")
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'CONFLICT');
        $this->postJson("/api/sppt/penilaian-pantas/run/{$run['id']}/langkah/tak-wujud")->assertStatus(404);
    }

    public function test_syndicate_case_routes_to_rejection_and_blocks_approval_before_disbursement(): void
    {
        $this->seed(PocSindiketDemoSeeder::class);
        $permohonan = Permohonan::where('no_rujukan', 'PM-POC-K2-001')->firstOrFail();
        $this->actingAsOfficer();

        $run = $this->runAllSteps($permohonan);
        $this->assertSame('gagal', $run['langkah']['penipuan']['keputusan']);
        $this->assertSame('cadang_tolak', $run['laluan']);
        $this->assertTrue($run['cadangan']['amaranPenipuan']);

        $this->postJson("/api/sppt/penilaian-pantas/run/{$run['id']}/keputusan", ['keputusan' => 'lulus', 'catatan' => 'cuba'])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'DISBURSEMENT_ALERT')
            ->assertJsonPath('error.details.bolehSalur', false);

        $this->assertNotSame('Diluluskan', $permohonan->fresh()->status);
    }

    public function test_officer_override_requires_acknowledgement_and_justification(): void
    {
        $this->seed(PocSindiketDemoSeeder::class);
        $permohonan = Permohonan::where('no_rujukan', 'PM-POC-K2-001')->firstOrFail();
        $this->actingAsOfficer();
        $run = $this->runAllSteps($permohonan);

        $this->postJson("/api/sppt/penilaian-pantas/run/{$run['id']}/keputusan", ['keputusan' => 'lulus', 'akuiAmaran' => true])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');

        $this->postJson("/api/sppt/penilaian-pantas/run/{$run['id']}/keputusan", [
            'keputusan' => 'lulus',
            'akuiAmaran' => true,
            'catatan' => 'Lawatan bersemuka mengesahkan identiti.',
        ])->assertOk()->assertJsonPath('data.penilaian.keputusanPegawai', 'lulus');

        $this->assertSame('Diluluskan', $permohonan->fresh()->status);
    }

    public function test_fast_lane_is_one_click_and_reject_needs_reason(): void
    {
        $permohonan = $this->permohonan();
        $user = $this->actingAsOfficer();
        $run = PenilaianPantas::create([
            'permohonan_id' => $permohonan->id,
            'status' => 'selesai',
            'laluan' => 'pantas',
            'tahap_risiko' => 'rendah',
            'skor_kredit' => 86,
            'langkah' => [],
            'cadangan' => ['laluan' => 'pantas', 'jumlah_dicadang' => 5000, 'tempoh_bulan' => 24],
            'mula_pada' => now(),
            'siap_pada' => now(),
            'dimulakan_oleh' => $user->id,
        ]);

        $this->postJson("/api/sppt/penilaian-pantas/run/{$run->id}/keputusan", ['keputusan' => 'tolak'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');

        $this->actingAsOfficer(['sppt.view', 'sppt.edit']);
        $this->postJson("/api/sppt/penilaian-pantas/run/{$run->id}/keputusan", ['keputusan' => 'lulus'])->assertStatus(403);

        $this->actingAsOfficer();
        $this->postJson("/api/sppt/penilaian-pantas/run/{$run->id}/keputusan", ['keputusan' => 'lulus'])
            ->assertOk()
            ->assertJsonPath('data.penilaian.keputusanPegawai', 'lulus');
        $this->assertSame('Diluluskan', $permohonan->fresh()->status);

        $this->postJson("/api/sppt/penilaian-pantas/run/{$run->id}/keputusan", ['keputusan' => 'lulus'])->assertStatus(409);
    }

    public function test_data_alternatif_validation_and_save(): void
    {
        $permohonan = $this->permohonan();
        $this->actingAsOfficer();

        $this->putJson("/api/sppt/penilaian-pantas/permohonan/{$permohonan->id}/data-alternatif", [
            'ewallet' => ['purataMasukBulanan' => -5],
            'platform' => ['penarafan' => 9],
        ])->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');

        $this->putJson("/api/sppt/penilaian-pantas/permohonan/{$permohonan->id}/data-alternatif", [
            'duitnowQr' => ['purataJualanBulanan' => 4200, 'bulanAktif' => 8],
            'ccrisTersedia' => false,
        ])->assertOk()->assertJsonPath('data.duitnowQr.purataJualanBulanan', 4200);

        $this->assertSame(4200, $permohonan->fresh()->details['data_alternatif']['duitnow_qr']['purata_jualan_bulanan']);
    }

    public function test_face_check_validation(): void
    {
        $permohonan = $this->permohonan();
        $this->actingAsOfficer();

        $this->postJson("/api/sppt/penilaian-pantas/permohonan/{$permohonan->id}/wajah", ['frames' => []])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_portal_runs_pipeline_after_hantar_and_hides_scores(): void
    {
        $permohonan = $this->permohonan([], ['pemohon_access_token' => 'tok-'.str_repeat('a', 36)]);

        $this->postJson("/api/public/pemohon/permohonan/{$permohonan->id}/penilaian-pantas", ['accessToken' => 'wrong'])
            ->assertStatus(404);

        $response = $this->postJson("/api/public/pemohon/permohonan/{$permohonan->id}/penilaian-pantas", [
            'accessToken' => 'tok-'.str_repeat('a', 36),
        ])->assertOk()->assertJsonPath('data.status', 'selesai');

        $this->assertStringNotContainsString('skorKredit', $response->getContent());
        $this->assertStringNotContainsString('laluan', $response->getContent());
        $this->assertSame('pemohon', PenilaianPantas::where('permohonan_id', $permohonan->id)->value('sumber'));
    }
}
