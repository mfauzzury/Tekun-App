<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SpptCawangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CawanganTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $role = Role::create([
            'name' => 'admin',
            'description' => 'Admin',
            'permissions' => ['sppt.view', 'sppt.create', 'sppt.edit', 'sppt.delete'],
        ]);

        $user = User::factory()->create([
            'role' => 'admin',
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($user);

        return $user;
    }

    public function test_cawangan_requires_auth(): void
    {
        $this->getJson('/api/sppt/cawangan')->assertStatus(401);
    }

    public function test_cawangan_list_success(): void
    {
        $this->actingAsAdmin();

        SpptCawangan::create([
            'code' => 'johor',
            'name' => 'TEKUN Nasional Pejabat Negeri Johor',
            'branch_type' => 'negeri',
            'negeri' => 'Johor',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->getJson('/api/sppt/cawangan');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.code', 'johor')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_cawangan_store_validation_error(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/sppt/cawangan', []);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_cawangan_store_success(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/sppt/cawangan', [
            'code' => 'test-cawangan',
            'name' => 'Pejabat Cawangan Test',
            'branchType' => 'cawangan',
            'negeri' => 'Selangor',
            'isActive' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'test-cawangan');

        $this->assertDatabaseHas('sppt_cawangan', [
            'code' => 'test-cawangan',
            'name' => 'Pejabat Cawangan Test',
            'negeri' => 'Selangor',
        ]);
    }

    public function test_cawangan_destroy_requires_permission(): void
    {
        $role = Role::create([
            'name' => 'viewer',
            'description' => 'Viewer',
            'permissions' => ['sppt.view'],
        ]);

        $user = User::factory()->create([
            'role' => 'viewer',
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($user);

        $row = SpptCawangan::create([
            'code' => 'delete-me',
            'name' => 'Delete Me',
            'branch_type' => 'cawangan',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->deleteJson("/api/sppt/cawangan/{$row->id}")->assertStatus(403);
    }
}
