<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SpptCawangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserCawanganTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $role = Role::create([
            'name' => 'admin',
            'description' => 'Admin',
            'permissions' => ['users.view', 'users.create', 'users.edit'],
        ]);

        $user = User::factory()->create([
            'role' => 'admin',
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($user);

        return $user;
    }

    public function test_user_can_be_assigned_cawangan(): void
    {
        $this->actingAsAdmin();

        $cawangan = SpptCawangan::create([
            'code' => 'gombak',
            'name' => 'TEKUN Nasional Cawangan Gombak',
            'branch_type' => 'cawangan',
            'negeri' => 'Selangor',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->postJson('/api/users', [
            'name' => 'Pegawai Gombak',
            'email' => 'gombak@example.com',
            'password' => 'secret123',
            'role' => 'admin',
            'isActive' => true,
            'spptCawanganId' => $cawangan->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.spptCawanganId', $cawangan->id)
            ->assertJsonPath('data.cawangan.code', 'gombak');

        $this->assertDatabaseHas('users', [
            'email' => 'gombak@example.com',
            'sppt_cawangan_id' => $cawangan->id,
        ]);
    }

    public function test_auth_me_includes_cawangan(): void
    {
        $cawangan = SpptCawangan::create([
            'code' => 'johor',
            'name' => 'TEKUN Nasional Pejabat Negeri Johor',
            'branch_type' => 'negeri',
            'negeri' => 'Johor',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'sppt_cawangan_id' => $cawangan->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.user.spptCawanganId', $cawangan->id)
            ->assertJsonPath('data.user.cawangan.name', 'TEKUN Nasional Pejabat Negeri Johor');
    }

    public function test_cawangan_options_endpoint_returns_active_branches(): void
    {
        $this->actingAsAdmin();

        SpptCawangan::create([
            'code' => 'active-branch',
            'name' => 'Active Branch',
            'branch_type' => 'cawangan',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        SpptCawangan::create([
            'code' => 'inactive-branch',
            'name' => 'Inactive Branch',
            'branch_type' => 'cawangan',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $response = $this->getJson('/api/users/cawangan-options');

        $response->assertStatus(200)
            ->assertJsonFragment(['code' => 'active-branch'])
            ->assertJsonMissing(['code' => 'inactive-branch']);
    }
}
