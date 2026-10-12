<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SpptCawangan;
use App\Models\User;
use Database\Seeders\IbuPejabatApproverUserSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IbuPejabatApproverUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_ibu_pejabat_approver_user(): void
    {
        $this->seed(RoleSeeder::class);

        SpptCawangan::create([
            'code' => 'ibu-pejabat',
            'name' => 'Ibu Pejabat TEKUN Nasional',
            'branch_type' => 'ibu_pejabat',
            'negeri' => 'Kuala Lumpur',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->seed(IbuPejabatApproverUserSeeder::class);

        $pelulusRole = Role::where('name', 'pelulus')->first();

        $this->assertDatabaseHas('users', [
            'email' => 'pelulus.ibupejabat@tekun.gov.my',
            'role' => 'pelulus',
            'role_id' => $pelulusRole?->id,
            'is_active' => true,
        ]);

        $user = User::where('email', 'pelulus.ibupejabat@tekun.gov.my')->first();
        $this->assertNotNull($user);
        $this->assertSame('ibu-pejabat', $user->cawangan?->code);
    }
}
