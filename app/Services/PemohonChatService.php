<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Portal Pemohon floating assistant — answers general SPPT questions via Anthropic Messages API.
 */
class PemohonChatService
{
    private const MAX_HISTORY_TURNS = 10;

    /**
     * @param  list<array{role?: string, content?: string}>  $history
     */
    public function reply(string $message, array $history = []): string
    {
        $apiKey = (string) config('services.anthropic.key', '');
        if ($apiKey === '') {
            throw new \RuntimeException('ANTHROPIC_API_KEY tidak dikonfigurasi. Sila tetapkan dalam .env.');
        }

        $model = (string) config('services.anthropic.model', 'claude-haiku-4-5');
        $timeout = (int) config('services.anthropic.timeout', 300);

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->timeout($timeout)->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'system' => $this->systemPrompt(),
            'messages' => $this->buildMessages($history, $message),
            'max_tokens' => 1024,
            'temperature' => 0.3,
        ]);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? $response->body();
            throw new \RuntimeException('Pembantu AI gagal membalas: '.Str::limit((string) $error, 500));
        }

        $reply = $response->json('content.0.text');
        if (! is_string($reply) || trim($reply) === '') {
            throw new \RuntimeException('Pembantu AI tidak mengembalikan jawapan.');
        }

        return trim($reply);
    }

    /**
     * @param  list<array{role?: string, content?: string}>  $history
     * @return list<array{role: string, content: string}>
     */
    private function buildMessages(array $history, string $message): array
    {
        $recent = array_slice($history, -self::MAX_HISTORY_TURNS);

        $messages = [];
        foreach ($recent as $turn) {
            $role = $turn['role'] ?? '';
            $content = $turn['content'] ?? '';
            if (! in_array($role, ['user', 'assistant'], true) || ! is_string($content) || trim($content) === '') {
                continue;
            }
            $messages[] = ['role' => $role, 'content' => $content];
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        return $messages;
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
        Anda ialah pembantu maya Portal Pemohon SPPT (Sistem Pengurusan Pembiayaan TEKUN) bagi TEKUN Nasional.
        Tugas anda ialah membantu pemohon pembiayaan memahami produk, kelayakan, proses permohonan, dokumen diperlukan, dan cara menyemak status permohonan.

        Produk pembiayaan yang ditawarkan: SPUMI, Kontrak-i, Tekun Niaga, Tawarruq, Teman TEKUN.

        Soalan lazim yang perlu anda ketahui:
        - Dokumen asas diperlukan: Kad Pengenalan, Lesen Perniagaan, Penyata Bank 3 bulan, SSM Form 9. Dokumen tambahan bergantung kepada jenis produk.
        - Tempoh semakan permohonan: biasanya 14-21 hari bekerja selepas dokumen lengkap diterima.
        - Semakan status: log masuk ke Portal Pemohon dan pilih "Permohonan Saya" untuk semakan status masa nyata.
        - Tukar tarikh temuduga: pergi ke halaman Temuduga, pilih permohonan berkaitan, klik "Tukar Tarikh".

        Peraturan:
        - Jawab ringkas dan jelas dalam Bahasa Melayu, kecuali pemohon menulis dalam Bahasa Inggeris — dalam kes itu balas dalam Bahasa Inggeris.
        - Jangan guna format markdown (tiada **tebal**, tiada tajuk #, tiada senarai bullet/nombor). Tulis dalam ayat atau perenggan biasa sahaja kerana jawapan dipaparkan sebagai teks biasa.
        - Anda TIDAK mempunyai akses kepada data akaun atau permohonan sebenar pemohon. Jangan reka nombor permohonan, status, atau jumlah pembiayaan. Jika ditanya soalan khusus akaun, arahkan pemohon log masuk dan semak "Permohonan Saya".
        - Jangan berikan nasihat kewangan atau undang-undang formal; nyatakan ia bantuan am sahaja.
        - Jika tidak pasti, katakan anda tidak pasti dan cadangkan hubungi pegawai TEKUN.
        PROMPT;
    }
}
