<?php

namespace Tests\Unit;

use App\Services\DocumentClassificationService;
use App\Services\MyKadVerificationService;
use Tests\TestCase;

class DocumentClassificationServiceTest extends TestCase
{
    private DocumentClassificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DocumentClassificationService(new MyKadVerificationService);
    }

    public function test_classifies_penyata_bank_from_filename(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'penyata');
        file_put_contents($path, 'dummy pdf content');

        $result = $this->service->classify($path, 'penyata-bank-maybank.pdf');

        @unlink($path);

        $this->assertSame(DocumentClassificationService::CLASS_PENYATA_BANK, $result['suggested_class']);
        $this->assertGreaterThanOrEqual(55, $result['confidence']);
    }

    public function test_classifies_ssm_form_9_from_filename(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'ssm');
        file_put_contents($path, 'dummy pdf content');

        $result = $this->service->classify($path, 'ssm-form-9.pdf');

        @unlink($path);

        $this->assertSame(DocumentClassificationService::CLASS_SSM_FORM_9, $result['suggested_class']);
    }

    public function test_classifies_ic_pemohon_depan_from_sample_front_image(): void
    {
        $path = base_path('docs/Sample-MalaysianMyKad-Front.jpg');
        $this->assertFileExists($path);

        $result = $this->service->classify(
            $path,
            'ic-depan.jpg',
            '691115-12-5053',
            'MASRI BIN YAKOP',
        );

        $this->assertSame(DocumentClassificationService::CLASS_IC_PEMOHON_DEPAN, $result['suggested_class']);
        $this->assertGreaterThanOrEqual(55, $result['confidence']);
    }

    public function test_classifies_ic_pemohon_belakang_from_filename(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'ic-back');
        file_put_contents($path, 'dummy');

        $result = $this->service->classify($path, 'mykad-belakang-pemohon.jpg');

        @unlink($path);

        $this->assertSame(DocumentClassificationService::CLASS_IC_PEMOHON_BELAKANG, $result['suggested_class']);
    }

    public function test_classifies_ic_pasangan_combined_from_filename(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'ic-spouse');
        file_put_contents($path, 'dummy');

        $result = $this->service->classify($path, 'ic-pasangan-depan-belakang.pdf');

        @unlink($path);

        $this->assertSame(DocumentClassificationService::CLASS_IC_PASANGAN_COMBINED, $result['suggested_class']);
    }

    public function test_allowed_classes_match_config(): void
    {
        $this->assertSame(array_keys(config('sppt.document_classes', [])), DocumentClassificationService::allowedClasses());
    }
}
