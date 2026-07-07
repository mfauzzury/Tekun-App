<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\SpptCawangan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class HuluLangatCawanganUserSeeder extends Seeder
{
    public function run(): void
    {
        $cawangan = SpptCawangan::where('code', 'hulu-langat')->first();

        if (! $cawangan) {
            $this->command?->error('Cawangan hulu-langat not found. Run CawanganSeeder first.');

            return;
        }

        $penyemakRole = Role::where('name', 'penyemak')->first();
        $penyokongRole = Role::where('name', 'penyokong')->first();

        if (! $penyemakRole || ! $penyokongRole) {
            $this->command?->error('Roles penyemak/penyokong not found. Run RoleSeeder first.');

            return;
        }

        $password = Hash::make(env('HULU_LANGAT_USER_PASSWORD', 'Tekun123!'));

        $users = [
            [
                'email' => 'penyemak1.hululangat@tekun.gov.my',
                'name' => 'Penyemak 1 — Hulu Langat',
                'role' => 'penyemak',
                'role_id' => $penyemakRole->id,
            ],
            [
                'email' => 'penyemak2.hululangat@tekun.gov.my',
                'name' => 'Penyemak 2 — Hulu Langat',
                'role' => 'penyemak',
                'role_id' => $penyemakRole->id,
            ],
            [
                'email' => 'penyokong.hululangat@tekun.gov.my',
                'name' => 'Penyokong — Hulu Langat',
                'role' => 'penyokong',
                'role_id' => $penyokongRole->id,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $password,
                    'role' => $userData['role'],
                    'role_id' => $userData['role_id'],
                    'sppt_cawangan_id' => $cawangan->id,
                    'is_active' => true,
                ],
            );
        }

        // Retire earlier placeholder accounts for this cawangan, if present.
        User::whereIn('email', [
            'ketua.hululangat@tekun.gov.my',
            'pegawai1.hululangat@tekun.gov.my',
            'pegawai2.hululangat@tekun.gov.my',
        ])->update(['is_active' => false]);

        $this->command?->info('Created/updated 3 users for TEKUN Nasional Cawangan Hulu Langat.');
        $this->command?->line('  Penyemak (2):');
        $this->command?->line('    penyemak1.hululangat@tekun.gov.my');
        $this->command?->line('    penyemak2.hululangat@tekun.gov.my');
        $this->command?->line('  Penyokong (1):');
        $this->command?->line('    penyokong.hululangat@tekun.gov.my');
        $this->command?->line('  Password: '.env('HULU_LANGAT_USER_PASSWORD', 'Tekun123!'));
    }
}
