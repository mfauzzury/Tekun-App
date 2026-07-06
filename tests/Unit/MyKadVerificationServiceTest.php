<?php

namespace Tests\Unit;

use App\Services\MyKadVerificationService;
use Tests\TestCase;

class MyKadVerificationServiceTest extends TestCase
{
    public function test_sample_front_image_is_verified_as_ic_front(): void
    {
        $service = new MyKadVerificationService;
        $path = base_path('docs/Sample-MalaysianMyKad-Front.jpg');

        $this->assertFileExists($path);

        $result = $service->verify($path, '691115-12-5053', 'ic-depan.jpg', 'MASRI BIN YAKOP');

        $this->assertSame('verified', $result['status']);
        $this->assertSame('ic_front', $result['document_type']);
        $this->assertGreaterThanOrEqual(55, $result['confidence']);
        $this->assertTrue($result['identity_matched']);
        $this->assertSame('applicant', $result['subject']);
    }

    public function test_sample_front_image_rejects_mismatched_applicant(): void
    {
        $service = new MyKadVerificationService;
        $path = base_path('docs/Sample-MalaysianMyKad-Front.jpg');

        $this->assertFileExists($path);

        $result = $service->verify($path, '850101-01-1234', 'ic-depan.jpg', 'AHMAD BIN ALI');

        $this->assertSame('failed', $result['status']);
        $this->assertSame('ic_front', $result['document_type']);
        $this->assertFalse($result['identity_matched']);
    }

    public function test_sample_back_image_is_verified_as_ic_back(): void
    {
        $service = new MyKadVerificationService;
        $path = base_path('docs/Sample-MalaysianMyKad-Back.png');

        $this->assertFileExists($path);

        $result = $service->verify($path);

        $this->assertSame('verified', $result['status']);
        $this->assertSame('ic_back', $result['document_type']);
        $this->assertGreaterThanOrEqual(55, $result['confidence']);
    }

    public function test_sample_combined_front_and_back_image_is_verified(): void
    {
        $service = new MyKadVerificationService;
        $path = base_path('docs/Masri-ic-test.jpg');

        $this->assertFileExists($path);

        $result = $service->verify($path, '691115-12-5053', 'Masri ic.jpg', 'MASRI BIN YAKOP');

        $this->assertSame('verified', $result['status']);
        $this->assertSame('ic_combined', $result['document_type']);
        $this->assertGreaterThanOrEqual(55, $result['confidence']);
        $this->assertTrue($result['identity_matched']);
    }

    public function test_summarize_ic_coverage(): void
    {
        $service = new MyKadVerificationService;

        $attachments = [
            ['verification' => ['status' => 'verified', 'document_type' => 'ic_front']],
            ['verification' => ['status' => 'verified', 'document_type' => 'ic_back']],
        ];

        $summary = $service->summarizeIcCoverage($attachments);

        $this->assertTrue($summary['has_front']);
        $this->assertTrue($summary['has_back']);
    }

    public function test_summarize_ic_coverage_accepts_combined_attachment(): void
    {
        $service = new MyKadVerificationService;

        $attachments = [
            ['verification' => ['status' => 'verified', 'document_type' => 'ic_combined', 'subject' => 'applicant']],
        ];

        $summary = $service->summarizeIcCoverage($attachments);

        $this->assertTrue($summary['has_front']);
        $this->assertTrue($summary['has_back']);
    }
}
