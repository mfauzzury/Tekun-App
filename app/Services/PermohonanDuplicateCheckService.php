<?php

namespace App\Services;

use App\Models\Permohonan;
use App\Models\Usahawan;

class PermohonanDuplicateCheckService
{
    /**
     * @param  array<string, mixed>  $details
     * @return array{no_ic: string, no_telefon: string, email: string}
     */
    public function extractIdentity(array $details): array
    {
        return [
            'no_ic' => $this->normalizeIc((string) ($details['no_ic_baru'] ?? $details['no_ic'] ?? '')),
            'no_telefon' => $this->normalizePhone((string) ($details['no_telefon_bimbit'] ?? $details['no_telefon'] ?? '')),
            'email' => $this->normalizeEmail((string) ($details['email'] ?? '')),
        ];
    }

    /**
     * @param  array<string, mixed>  $details
     * @return array<string, string>
     */
    public function findDuplicateFieldErrors(array $details, ?int $excludePermohonanId = null): array
    {
        $identity = $this->extractIdentity($details);

        if ($identity['no_ic'] === '' && $identity['no_telefon'] === '' && $identity['email'] === '') {
            return [];
        }

        $errors = [];

        if ($identity['no_ic'] !== '') {
            $permohonan = $this->findPermohonanByIc($identity['no_ic'], $excludePermohonanId);
            if ($permohonan !== null) {
                $errors['details.no_ic_baru'] = $this->icDuplicateMessage($permohonan);
            } elseif ($this->findUsahawanByIc($identity['no_ic']) !== null) {
                $errors['details.no_ic_baru'] = 'No. Kad Pengenalan ini telah didaftarkan dalam rekod usahawan sedia ada.';
            }
        }

        if ($identity['no_telefon'] !== '') {
            $permohonan = $this->findPermohonanByPhone($identity['no_telefon'], $excludePermohonanId);
            if ($permohonan !== null) {
                $errors['details.no_telefon_bimbit'] = $this->phoneDuplicateMessage($permohonan);
            } elseif ($this->findUsahawanByPhone($identity['no_telefon']) !== null) {
                $errors['details.no_telefon_bimbit'] = 'No. telefon ini telah didaftarkan dalam rekod usahawan sedia ada.';
            }
        }

        if ($identity['email'] !== '') {
            $permohonan = $this->findPermohonanByEmail($identity['email'], $excludePermohonanId);
            if ($permohonan !== null) {
                $errors['details.email'] = $this->emailDuplicateMessage($permohonan);
            } elseif ($this->findUsahawanByEmail($identity['email']) !== null) {
                $errors['details.email'] = 'E-mel ini telah didaftarkan dalam rekod usahawan sedia ada.';
            }
        }

        return $errors;
    }

    private function findPermohonanByIc(string $normalizedIc, ?int $excludeId): ?Permohonan
    {
        return $this->findPermohonanMatch(
            $excludeId,
            fn (array $details) => $this->normalizeIc((string) ($details['no_ic_baru'] ?? $details['no_ic'] ?? '')) === $normalizedIc
                && $normalizedIc !== '',
        );
    }

    private function findPermohonanByPhone(string $normalizedPhone, ?int $excludeId): ?Permohonan
    {
        return $this->findPermohonanMatch(
            $excludeId,
            fn (array $details) => $this->normalizePhone((string) ($details['no_telefon_bimbit'] ?? $details['no_telefon'] ?? '')) === $normalizedPhone
                && $normalizedPhone !== '',
        );
    }

    private function findPermohonanByEmail(string $normalizedEmail, ?int $excludeId): ?Permohonan
    {
        return $this->findPermohonanMatch(
            $excludeId,
            fn (array $details) => $this->normalizeEmail((string) ($details['email'] ?? '')) === $normalizedEmail
                && $normalizedEmail !== '',
        );
    }

    /**
     * @param  callable(array<string, mixed>): bool  $matches
     */
    private function findPermohonanMatch(?int $excludeId, callable $matches): ?Permohonan
    {
        $query = Permohonan::query()->select(['id', 'no_rujukan', 'nama', 'details']);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        foreach ($query->cursor() as $permohonan) {
            $details = is_array($permohonan->details) ? $permohonan->details : [];
            if ($matches($details)) {
                return $permohonan;
            }
        }

        return null;
    }

    private function findUsahawanByIc(string $normalizedIc): ?Usahawan
    {
        foreach (Usahawan::query()->select(['id', 'no_ic'])->cursor() as $usahawan) {
            $stored = $this->normalizeIc((string) ($usahawan->no_ic ?? ''));
            if ($stored !== '' && $stored === $normalizedIc) {
                return $usahawan;
            }
        }

        return null;
    }

    private function findUsahawanByPhone(string $normalizedPhone): ?Usahawan
    {
        foreach (Usahawan::query()->select(['id', 'no_telefon'])->cursor() as $usahawan) {
            $stored = $this->normalizePhone((string) ($usahawan->no_telefon ?? ''));
            if ($stored !== '' && $stored === $normalizedPhone) {
                return $usahawan;
            }
        }

        return null;
    }

    private function findUsahawanByEmail(string $normalizedEmail): ?Usahawan
    {
        foreach (Usahawan::query()->select(['id', 'email'])->cursor() as $usahawan) {
            $stored = $this->normalizeEmail((string) ($usahawan->email ?? ''));
            if ($stored !== '' && $stored === $normalizedEmail) {
                return $usahawan;
            }
        }

        return null;
    }

    private function icDuplicateMessage(Permohonan $permohonan): string
    {
        return sprintf(
            'No. Kad Pengenalan ini telah didaftarkan dalam permohonan %s (%s).',
            $permohonan->no_rujukan,
            $permohonan->nama,
        );
    }

    private function phoneDuplicateMessage(Permohonan $permohonan): string
    {
        return sprintf(
            'No. telefon ini telah didaftarkan dalam permohonan %s (%s).',
            $permohonan->no_rujukan,
            $permohonan->nama,
        );
    }

    private function emailDuplicateMessage(Permohonan $permohonan): string
    {
        return sprintf(
            'E-mel ini telah didaftarkan dalam permohonan %s (%s).',
            $permohonan->no_rujukan,
            $permohonan->nama,
        );
    }

    private function normalizeIc(string $ic): string
    {
        return strtoupper(preg_replace('/[\s-]+/', '', trim($ic)) ?? '');
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', trim($phone)) ?? '';
        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '60') && strlen($digits) > 10) {
            $digits = '0'.substr($digits, 2);
        }

        return $digits;
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}
