<?php

namespace Tests\Feature;

use App\Models\AkaunPembiayaan;
use App\Models\PermohonanPenjadualan;
use App\Models\Role;
use App\Models\TransaksiBayaran;
use App\Models\User;
use Database\Seeders\SpptSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SpptRepaymentTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(array $permissions = null): User
    {
        $role = Role::create([
            'name' => 'admin',
            'description' => 'Admin',
            'permissions' => $permissions ?? [
                'sppt.view', 'sppt.create', 'sppt.edit', 'sppt.delete',
            ],
        ]);

        $user = User::factory()->create([
            'role' => 'admin',
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($user);

        return $user;
    }

    public function test_bayaran_requires_auth(): void
    {
        $this->postJson('/api/sppt/bayaran', [])->assertStatus(401);
    }

    public function test_bayaran_validation_error(): void
    {
        $this->actingAsAdmin();
        $this->seed(SpptSeeder::class);

        $this->postJson('/api/sppt/bayaran', [])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_bayaran_forbidden_without_create_permission(): void
    {
        $this->actingAsAdmin(['sppt.view']);
        $this->seed(SpptSeeder::class);

        $akaun = AkaunPembiayaan::first();

        $this->postJson('/api/sppt/bayaran', [
            'akaun_id' => $akaun->id,
            'jumlah' => 100,
            'saluran' => 'fpx',
        ])->assertStatus(403);
    }

    public function test_real_time_payment_updates_account(): void
    {
        $this->actingAsAdmin();
        $this->seed(SpptSeeder::class);

        $akaun = AkaunPembiayaan::where('tunggakan', '>', 0)->first();
        $this->assertNotNull($akaun);

        $beforeTunggakan = (float) $akaun->tunggakan;

        $response = $this->postJson('/api/sppt/bayaran', [
            'akaun_id' => $akaun->id,
            'jumlah' => min($beforeTunggakan, (float) $akaun->bayaran_bulanan),
            'saluran' => 'jompay',
            'no_rujukan_luar' => 'JOMPAY123456',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'jumlah', 'saluran', 'mod', 'kepadaTawidh', 'kepadaTunggakan'],
            ])
            ->assertJsonPath('data.saluran', 'jompay')
            ->assertJsonPath('data.mod', 'real_time');

        $akaun->refresh();
        $this->assertLessThanOrEqual($beforeTunggakan, (float) $akaun->tunggakan);
    }

    public function test_batch_payment_processing(): void
    {
        $this->actingAsAdmin();
        $this->seed(SpptSeeder::class);

        $akaunList = AkaunPembiayaan::where('status', 'Aktif')->limit(2)->get();
        $this->assertGreaterThanOrEqual(1, $akaunList->count());

        $rows = $akaunList->map(fn (AkaunPembiayaan $a) => [
            'no_akaun' => $a->no_akaun,
            'jumlah' => 100,
            'no_rujukan_luar' => 'BATCH-'.$a->no_akaun,
        ])->all();

        $response = $this->postJson('/api/sppt/bayaran/batch', [
            'saluran' => 'auto_debit',
            'rows' => $rows,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.saluran', 'auto_debit')
            ->assertJsonPath('data.jumlahBerjaya', count($rows));

        $this->assertGreaterThanOrEqual(count($rows), TransaksiBayaran::where('mod', 'batch')->count());
    }

    public function test_penjadualan_moratorium_flow(): void
    {
        $user = $this->actingAsAdmin();
        $this->seed(SpptSeeder::class);

        $akaun = AkaunPembiayaan::where('status', 'Aktif')->first();
        $this->assertNotNull($akaun);

        $create = $this->postJson('/api/sppt/penjadualan', [
            'akaun_id' => $akaun->id,
            'jenis' => 'moratorium',
            'tempoh_bulan' => 3,
            'sebab' => 'Bencana banjir',
        ]);

        $create->assertStatus(201)
            ->assertJsonPath('data.jenis', 'moratorium')
            ->assertJsonPath('data.status', 'menunggu');

        $permohonanId = PermohonanPenjadualan::first()->id;

        $approve = $this->postJson("/api/sppt/penjadualan/{$permohonanId}/approve", [
            'tindakan' => 'lulus',
            'catatan_pegawai' => 'Diluluskan PS',
        ]);

        $approve->assertStatus(200)
            ->assertJsonPath('data.status', 'diluluskan');

        $akaun->refresh();
        $this->assertSame('Moratorium', $akaun->status);
        $this->assertNotNull($akaun->moratorium_tamat);
    }

    public function test_tawidh_recalculate_endpoint(): void
    {
        $this->actingAsAdmin();
        $this->seed(SpptSeeder::class);

        $response = $this->postJson('/api/sppt/tawidh/recalculate');
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['akaunDikira']]);
    }

    public function test_tunggakan_summary_lists_accounts(): void
    {
        $this->actingAsAdmin();
        $this->seed(SpptSeeder::class);

        $response = $this->getJson('/api/sppt/tawidh/tunggakan');
        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_jadual_ansuran_for_account(): void
    {
        $this->actingAsAdmin();
        $this->seed(SpptSeeder::class);

        $akaun = AkaunPembiayaan::first();
        $response = $this->getJson("/api/sppt/akaun/{$akaun->id}/jadual");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['no', 'tarikh', 'ansuran', 'status']]]);
    }
}
