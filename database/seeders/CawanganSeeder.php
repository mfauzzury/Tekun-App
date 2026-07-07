<?php

namespace Database\Seeders;

use App\Models\SpptCawangan;
use Illuminate\Database\Seeder;

class CawanganSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/tekun_cawangan.json');

        if (! is_file($path)) {
            $this->command?->warn('CawanganSeeder: tekun_cawangan.json not found — run php scripts/fetch_tekun_cawangan.php first.');

            return;
        }

        $rows = json_decode((string) file_get_contents($path), true);

        if (! is_array($rows)) {
            $this->command?->error('CawanganSeeder: invalid JSON in tekun_cawangan.json');

            return;
        }

        foreach ($rows as $row) {
            SpptCawangan::updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $this->clean($row['name'] ?? ''),
                    'branch_type' => $row['branch_type'] ?? 'cawangan',
                    'negeri' => $this->clean($row['negeri'] ?? null),
                    'locality' => $this->clean($row['locality'] ?? null),
                    'postal_code' => $this->clean($row['postal_code'] ?? null),
                    'address' => $this->cleanAddress($row['address'] ?? null),
                    'phone' => $this->clean($row['phone'] ?? null),
                    'fax' => $this->clean($row['fax'] ?? null) ?: null,
                    'contact_person' => $this->clean($row['contact_person'] ?? null) ?: null,
                    'external_id' => $row['external_id'] ?? null,
                    'is_active' => (bool) ($row['is_active'] ?? true),
                    'sort_order' => (int) ($row['sort_order'] ?? 0),
                ],
            );
        }

        $this->command?->info('CawanganSeeder: seeded '.count($rows).' branches.');
    }

    private function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);

        return $value === '' ? null : $value;
    }

    private function cleanAddress(?string $value): ?string
    {
        $value = $this->clean($value);
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/,\s*,/u', ', ', $value) ?? $value;
        $value = preg_replace('/,\s*$/u', '', $value) ?? $value;

        return $value;
    }
}
