<?php

namespace Tests\Feature;

use App\Models\AkaunPembiayaan;
use App\Models\NotisKutipan;
use App\Models\Role;
use App\Models\User;
use App\Services\NplClassificationService;
use Database\Seeders\SpptSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SpptCollectionTest extends TestCase
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

    public function test_koleksi_config_requires_auth(): void
    {
        $this->getJson('/api/sppt/koleksi/config')->assertStatus(401);
    }

    public function test_klasifikasi_summary_success(): void
    {
        $this->actingAsAdmin();
        $this->seed(SpptSeeder::class);

        $this->getJson('/api/sppt/koleksi/klasifikasi/summary')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['kategori', 'bil', 'amaun', 'peratus'],
                ],
            ]);
    }

    public function test_klasifikasi_index_returns_npl_fields(): void
    {
        $this->actingAsAdmin();
        $this->seed(SpptSeeder::class);

        $response = $this->getJson('/api/sppt/koleksi/klasifikasi?limit=5')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'nama', 'klasifikasiNpl', 'klasifikasiNplLabel', 'hariLewat'],
                ],
                'meta' => ['page', 'limit', 'total', 'totalPages'],
            ]);

        $this->assertNotEmpty($response->json('data'));
    }

    public function test_klasifikasi_summary_includes_lancar_and_perhatian_khusus_samples(): void
    {
        $this->actingAsAdmin();
        $this->seed(SpptSeeder::class);

        $response = $this->getJson('/api/sppt/koleksi/klasifikasi/summary')->assertOk();
        $buckets = collect($response->json('data'));

        $lancar = $buckets->first(fn (array $row) => str_starts_with($row['kategori'], 'Lancar'));
        $perhatian = $buckets->first(fn (array $row) => str_starts_with($row['kategori'], 'Perhatian'));

        $this->assertSame(6, $lancar['bil'] ?? 0);
        $this->assertSame(1, $perhatian['bil'] ?? 0);

        $list = $this->getJson('/api/sppt/koleksi/klasifikasi?limit=200&klasifikasi=lancar')->assertOk();
        $this->assertCount(6, $list->json('data'));
    }

    public function test_recalculate_klasifikasi_forbidden_without_edit_permission(): void
    {
        $this->actingAsAdmin(['sppt.view']);
        $this->seed(SpptSeeder::class);

        $this->postJson('/api/sppt/koleksi/klasifikasi/recalculate')
            ->assertStatus(403);
    }

    public function test_process_dunning_creates_notices(): void
    {
        $this->actingAsAdmin();
        $this->seed(SpptSeeder::class);

        $akaun = AkaunPembiayaan::where('tunggakan', '>', 0)->first();
        $this->assertNotNull($akaun);

        $akaun->update(['hari_lewat' => 35, 'klasifikasi_npl' => 'perhatian_khusus']);

        NotisKutipan::query()->delete();
        $before = NotisKutipan::count();

        $this->postJson('/api/sppt/koleksi/notis/process')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['notisDijana', 'notisDihantar', 'gagal'],
            ]);

        $this->assertGreaterThan($before, NotisKutipan::count());
    }

    public function test_notis_index_success(): void
    {
        $this->actingAsAdmin();
        $this->seed(SpptSeeder::class);

        $this->postJson('/api/sppt/koleksi/notis/process')->assertOk();

        $this->getJson('/api/sppt/koleksi/notis?limit=10')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['rujukan', 'tahap', 'jenis', 'status', 'hariLewat'],
                ],
                'meta' => ['page', 'limit', 'total', 'totalPages'],
            ]);
    }

    public function test_npl_classification_promotes_to_tidak_lancar(): void
    {
        $this->seed(SpptSeeder::class);

        $akaun = AkaunPembiayaan::where('no_akaun', 'TEK-IPH-2022-0089')->firstOrFail();
        $akaun->update(['hari_lewat' => 95, 'status' => 'Tunggakan']);

        app(NplClassificationService::class)->classify($akaun->fresh());

        $akaun->refresh();
        $this->assertSame('tidak_lancar', $akaun->klasifikasi_npl);
        $this->assertSame('NPF', $akaun->status);
    }

    public function test_recalculate_klasifikasi_preserves_perhatian_khusus_demo(): void
    {
        $this->actingAsAdmin();
        $this->seed(SpptSeeder::class);

        $this->postJson('/api/sppt/koleksi/klasifikasi/recalculate')
            ->assertOk();

        $akaun = AkaunPembiayaan::where('no_akaun', 'TEK-JB-2024-0012')->firstOrFail();
        $this->assertSame('perhatian_khusus', $akaun->klasifikasi_npl);
        $this->assertSame(45, $akaun->hari_lewat);

        $summary = $this->getJson('/api/sppt/koleksi/klasifikasi/summary')->assertOk();
        $perhatian = collect($summary->json('data'))
            ->first(fn (array $row) => str_starts_with($row['kategori'], 'Perhatian'));
        $this->assertSame(1, $perhatian['bil'] ?? 0);

        $list = $this->getJson('/api/sppt/koleksi/klasifikasi?limit=200&klasifikasi=perhatian_khusus')
            ->assertOk();
        $this->assertCount(1, $list->json('data'));
        $this->assertSame('TEK-JB-2024-0012', $list->json('data.0.id'));
    }
}
