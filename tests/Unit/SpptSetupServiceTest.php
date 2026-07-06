<?php

namespace Tests\Unit;

use App\Models\SpptDataset;
use App\Services\SpptSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpptSetupServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_format_knowledge_for_prompt_uses_config_defaults(): void
    {
        $service = new SpptSetupService;

        $prompt = $service->formatKnowledgeForPrompt('kelayakan_tekun_niaga');

        $this->assertStringContainsString(
            'Siapa yang layak untuk memohon skim pembiayaan TEKUN Niaga',
            $prompt
        );
        $this->assertStringContainsString('1. Bumiputera dan Warganegara Malaysia', $prompt);
        $this->assertStringContainsString('13. Aktiviti utama perniagaan usahawan mestilah patuh Syariah', $prompt);
    }

    public function test_format_knowledge_for_prompt_prefers_database_payload(): void
    {
        SpptDataset::create([
            'module' => 'setup',
            'dataset_key' => 'kelayakan_tekun_niaga',
            'payload' => [
                [
                    'value' => '1',
                    'label' => 'Kriteria ujian dari pangkalan data',
                    'color' => 'slate',
                    'active' => true,
                    'sort' => 1,
                ],
            ],
        ]);

        $service = new SpptSetupService;
        $prompt = $service->formatKnowledgeForPrompt('kelayakan_tekun_niaga');

        $this->assertStringContainsString('Kriteria ujian dari pangkalan data', $prompt);
        $this->assertStringNotContainsString('Bumiputera dan Warganegara Malaysia', $prompt);
    }
}
