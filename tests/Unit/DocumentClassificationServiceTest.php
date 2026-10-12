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
        $this->assertTrue($result['identity_matched']);
    }

    public function test_rejects_mismatched_ic_front_identity_on_classify(): void
    {
        $path = base_path('docs/Sample-MalaysianMyKad-Front.jpg');
        $this->assertFileExists($path);

        $result = $this->service->classify(
            $path,
            'ic-depan.jpg',
            '850101-01-1234',
            'AHMAD BIN ALI',
        );

        $this->assertSame(DocumentClassificationService::CLASS_LAIN_LAIN, $result['suggested_class']);
        $this->assertFalse($result['identity_matched']);
        $this->assertStringContainsString('tidak sepadan', $result['message']);
    }

    public function test_filename_alone_does_not_accept_ic_front_when_applicant_context_exists(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'ic-front');
        file_put_contents($path, 'dummy');

        $result = $this->service->classify(
            $path,
            'mykad-depan-pemohon.jpg',
            '850101-01-1234',
            'AHMAD BIN ALI',
        );

        @unlink($path);

        $this->assertSame(DocumentClassificationService::CLASS_LAIN_LAIN, $result['suggested_class']);
        $this->assertFalse($result['identity_matched']);
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
