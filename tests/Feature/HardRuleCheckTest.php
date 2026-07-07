<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HardRuleCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_hard_rules_summary_is_accessible(): void
    {
        $response = $this->getJson('/api/public/sppt/hard-rules');

        $response->assertStatus(200)
            ->assertJsonPath('data.active', true)
            ->assertJsonStructure([
                'data' => [
                    'active',
                    'rules' => [
                        ['code', 'label', 'hint'],
                    ],
                ],
            ]);
    }

    public function test_public_hard_rules_check_auto_rejects_blacklisted_ic(): void
    {
        $response = $this->postJson('/api/public/sppt/hard-rules/check', [
            'umur' => 30,
            'noKp' => '800101-01-0001',
            'pendapatanBulanan' => 3000,
            'jumlahKomitmenSediaAda' => 500,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.eligible', false)
            ->assertJsonPath('data.autoReject', true);
    }

    public function test_public_hard_rules_check_passes_valid_input(): void
    {
        $response = $this->postJson('/api/public/sppt/hard-rules/check', [
            'umur' => 35,
            'noKp' => '850101145678',
            'pendapatanBulanan' => 5000,
            'jumlahKomitmenSediaAda' => 1000,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.eligible', true)
            ->assertJsonPath('data.autoReject', false);
    }

    public function test_admin_can_update_hard_rules_setup(): void
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

        $response = $this->putJson('/api/sppt/setup/saringan_auto_kelayakan', [
            'active' => true,
            'rules' => [
                [
                    'code' => 'age_limit',
                    'label' => 'Had umur pemohon',
                    'active' => true,
                    'sort' => 1,
                    'config' => ['minAge' => 20, 'maxAge' => 60],
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.hardRules.active', true)
            ->assertJsonPath('data.hardRules.rules.0.config.minAge', 20);

        $this->assertDatabaseHas('sppt_datasets', [
            'module' => 'setup',
            'dataset_key' => 'saringan_auto_kelayakan',
        ]);

        $check = $this->postJson('/api/public/sppt/hard-rules/check', [
            'umur' => 19,
            'noKp' => '850101145678',
        ]);

        $check->assertStatus(200)
            ->assertJsonPath('data.eligible', false);
    }

    public function test_hard_rules_setup_requires_auth_for_update(): void
    {
        $response = $this->putJson('/api/sppt/setup/saringan_auto_kelayakan', [
            'active' => true,
            'rules' => [],
        ]);

        $response->assertStatus(401);
    }
}
