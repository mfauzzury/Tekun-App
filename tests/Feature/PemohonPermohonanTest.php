<?php

namespace Tests\Feature;

use App\Models\Permohonan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PemohonPermohonanTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_draft_with_access_token(): void
    {
        $response = $this->postJson('/api/public/pemohon/permohonan', [
            'status' => 'Draf',
            'nama' => 'Draf',
            'pemohonEmail' => 'pemohon@example.com',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'Draf');
        $this->assertNotEmpty($response->json('data.pemohonAccessToken'));
    }

    public function test_store_validation_error_without_nama_for_final_submit(): void
    {
        $response = $this->postJson('/api/public/pemohon/permohonan', [
            'status' => 'Dalam Semakan',
        ]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_update_requires_matching_access_token(): void
    {
        $create = $this->postJson('/api/public/pemohon/permohonan', ['status' => 'Draf']);
        $id = $create->json('data.id');

        $response = $this->putJson("/api/public/pemohon/permohonan/{$id}", [
            'accessToken' => 'wrong-token',
            'nama' => 'Test',
        ]);

        $response->assertStatus(404);
    }

    public function test_staff_created_permohonan_is_unreachable_via_pemohon_endpoints(): void
    {
        $staffPermohonan = Permohonan::create([
            'no_rujukan' => 'PM-2026-9999',
            'nama' => 'Staff Created',
            'status' => 'Dalam Proses',
        ]);

        $response = $this->getJson("/api/public/pemohon/permohonan/{$staffPermohonan->id}?accessToken=anything");

        $response->assertStatus(404);
    }

    public function test_full_submission_flow_with_verified_mykad(): void
    {
        Storage::fake('public');

        $create = $this->postJson('/api/public/pemohon/permohonan', [
            'status' => 'Draf',
            'nama' => 'MASRI BIN YAKOP',
            'details' => ['nama' => 'MASRI BIN YAKOP', 'noIcBaru' => '691115-12-5053'],
        ]);
        $create->assertStatus(201);
        $id = $create->json('data.id');
        $token = $create->json('data.pemohonAccessToken');

        $front = new UploadedFile(base_path('docs/Sample-MalaysianMyKad-Front.jpg'), 'ic-depan.jpg', 'image/jpeg', null, true);
        $this->postJson("/api/public/pemohon/permohonan/{$id}/dokumen", [
            'accessToken' => $token,
            'file' => $front,
        ])->assertStatus(201);

        $back = new UploadedFile(base_path('docs/Sample-MalaysianMyKad-Back.png'), 'ic-belakang.png', 'image/png', null, true);
        $this->postJson("/api/public/pemohon/permohonan/{$id}/dokumen", [
            'accessToken' => $token,
            'file' => $back,
        ])->assertStatus(201);

        $submit = $this->putJson("/api/public/pemohon/permohonan/{$id}", [
            'accessToken' => $token,
            'nama' => 'MASRI BIN YAKOP',
            'status' => 'Dalam Semakan',
            'details' => ['nama' => 'MASRI BIN YAKOP', 'noIcBaru' => '691115-12-5053'],
        ]);

        $submit->assertStatus(200)->assertJsonPath('data.status', 'Dalam Semakan');
    }

    public function test_submit_requires_supporting_documents(): void
    {
        $create = $this->postJson('/api/public/pemohon/permohonan', [
            'status' => 'Draf',
            'nama' => 'Test Pemohon',
        ]);
        $id = $create->json('data.id');
        $token = $create->json('data.pemohonAccessToken');

        $response = $this->putJson("/api/public/pemohon/permohonan/{$id}", [
            'accessToken' => $token,
            'nama' => 'Test Pemohon',
            'status' => 'Dalam Semakan',
        ]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_classify_document_is_public(): void
    {
        $file = UploadedFile::fake()->create('penyata-bank-maybank.pdf', 100, 'application/pdf');

        $response = $this->postJson('/api/public/pemohon/permohonan/dokumen/classify', ['file' => $file]);

        $response->assertStatus(200);
    }

    public function test_verify_document_is_public(): void
    {
        $front = new UploadedFile(base_path('docs/Sample-MalaysianMyKad-Front.jpg'), 'ic-depan.jpg', 'image/jpeg', null, true);

        $response = $this->postJson('/api/public/pemohon/permohonan/dokumen/verify', [
            'file' => $front,
            'applicantIc' => '691115-12-5053',
            'applicantName' => 'MASRI BIN YAKOP',
        ]);

        $response->assertStatus(200)->assertJsonPath('data.verification.status', 'verified');
    }

    public function test_delete_document_requires_matching_token(): void
    {
        Storage::fake('public');

        $create = $this->postJson('/api/public/pemohon/permohonan', ['status' => 'Draf', 'nama' => 'Test']);
        $id = $create->json('data.id');
        $token = $create->json('data.pemohonAccessToken');

        $file = UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf');
        $upload = $this->postJson("/api/public/pemohon/permohonan/{$id}/dokumen", [
            'accessToken' => $token,
            'file' => $file,
        ]);
        $attachmentId = $upload->json('data.id');

        $this->deleteJson("/api/public/pemohon/permohonan/{$id}/dokumen/{$attachmentId}", [
            'accessToken' => 'wrong-token',
        ])->assertStatus(404);

        $this->deleteJson("/api/public/pemohon/permohonan/{$id}/dokumen/{$attachmentId}", [
            'accessToken' => $token,
        ])->assertStatus(200);
    }
}
