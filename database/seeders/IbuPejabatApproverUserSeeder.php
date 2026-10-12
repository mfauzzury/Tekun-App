<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\SpptCawangan;
use App\Models\User;
use Illuminate\Database\Seeder;

class IbuPejabatApproverUserSeeder extends Seeder
{
    public function run(): void
    {
        $cawangan = SpptCawangan::where('code', 'ibu-pejabat')->first();

        if (! $cawangan) {
            $this->command?->error('Cawangan ibu-pejabat not found. Run CawanganSeeder first.');

            return;
        }

        $pelulusRole = Role::where('name', 'pelulus')->first();

        if (! $pelulusRole) {
            $this->command?->error('Role pelulus not found. Run RoleSeeder first.');

            return;
        }

        $password = env('IBU_PEJABAT_USER_PASSWORD', 'Tekun123!');

        User::updateOrCreate(
            ['email' => 'pelulus.ibupejabat@tekun.gov.my'],
            [
                'name' => 'Pelulus (Approver) — Ibu Pejabat TEKUN',
                'password' => $password,
                'role' => 'pelulus',
                'role_id' => $pelulusRole->id,
                'sppt_cawangan_id' => $cawangan->id,
                'is_active' => true,
            ],
        );

        $this->command?->info('Created/updated approver user for Ibu Pejabat TEKUN Nasional.');
        $this->command?->line('  Role: Pelulus (Approver / Kelulusan)');
        $this->command?->line('  Email: pelulus.ibupejabat@tekun.gov.my');
        $this->command?->line('  Password: '.$password);
        $this->command?->line('  Cawangan: '.$cawangan->name);
    }
}
