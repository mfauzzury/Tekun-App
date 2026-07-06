<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SpptTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $role = Role::create([
            'name' => 'admin',
            'description' => 'Admin',
            'permissions' => [
                'sppt.view', 'sppt.create', 'sppt.edit', 'sppt.delete',
            ],
        ]);

        $user = User::factory()->create([
            'role' => 'admin',
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($user);

        return $user;
    }

    public function test_sppt_dashboard_requires_auth(): void
    {
        $this->getJson('/api/sppt/dashboard/summary')->assertStatus(401);
    }

    public function test_sppt_dashboard_returns_summary(): void
    {
        $this->actingAsAdmin();
        $this->seed(\Database\Seeders\SpptSeeder::class);

        $response = $this->getJson('/api/sppt/dashboard/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'permohonanDalamProses',
                    'akaunAktif',
                    'kutipanBulanIni',
                    'tunggakan',
                ],
            ]);
    }

    public function test_sppt_reference_data_returns_options(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/sppt/reference-data');

        $response->assertStatus(200)
            ->assertJsonPath('data.negeriOptions.0', 'Johor');
    }

    public function test_permohonan_create_validation_error(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/sppt/permohonan', []);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_permohonan_create_draft_success(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/sppt/permohonan', [
            'status' => 'Draf',
            'details' => [
                'kategoriPembiayaan' => 'TEKUN Niaga',
                'namaBank' => 'Maybank',
                'currentStep' => 0,
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'Draf')
            ->assertJsonPath('data.nama', 'Draf');
    }

    public function test_permohonan_create_success(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/sppt/permohonan', [
            'nama' => 'Test Pemohon',
            'kategoriPembiayaan' => 'TEKUN Niaga',
            'jumlahPermohonan' => 10000,
            'status' => 'Dalam Proses',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.nama', 'Test Pemohon')
            ->assertJsonPath('data.status', 'Dalam Proses');
    }

    public function test_sppt_forbidden_without_permission(): void
    {
        $role = Role::create([
            'name' => 'viewer',
            'description' => 'Viewer',
            'permissions' => [],
        ]);

        $user = User::factory()->create([
            'role' => 'viewer',
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/sppt/dashboard/summary')->assertStatus(403);
    }

    public function test_permohonan_document_upload_persists_in_draft(): void
    {
        $this->actingAsAdmin();
        \Illuminate\Support\Facades\Storage::fake('public');

        $create = $this->postJson('/api/sppt/permohonan', [
            'status' => 'Draf',
            'nama' => 'Test Pemohon',
        ]);

        $create->assertStatus(201);
        $permohonanId = $create->json('data.id');

        $file = \Illuminate\Http\UploadedFile::fake()->create('salinan-ic.pdf', 100, 'application/pdf');

        $upload = $this->postJson("/api/sppt/permohonan/{$permohonanId}/dokumen", [
            'file' => $file,
        ]);

        $upload->assertStatus(201)
            ->assertJsonPath('data.name', 'salinan-ic.pdf');

        $show = $this->getJson("/api/sppt/permohonan/{$permohonanId}");
        $show->assertStatus(200)
            ->assertJsonCount(1, 'data.details.attachments');

        $attachmentId = $show->json('data.details.attachments.0.id');

        $this->get("/api/sppt/permohonan/{$permohonanId}/dokumen/{$attachmentId}")
            ->assertStatus(200);
    }

    public function test_permohonan_submit_requires_supporting_documents(): void
    {
        $this->actingAsAdmin();

        $create = $this->postJson('/api/sppt/permohonan', [
            'status' => 'Draf',
            'nama' => 'Test Pemohon',
        ]);

        $create->assertStatus(201);
        $permohonanId = $create->json('data.id');

        $response = $this->putJson("/api/sppt/permohonan/{$permohonanId}", [
            'nama' => 'Test Pemohon',
            'status' => 'Dalam Proses',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonPath('error.details.attachments.0', 'Sila lampirkan dokumen sokongan.');
    }

    public function test_permohonan_document_verify_detects_sample_mykad(): void
    {
        $this->actingAsAdmin();

        $front = new \Illuminate\Http\UploadedFile(
            base_path('docs/Sample-MalaysianMyKad-Front.jpg'),
            'ic-depan.jpg',
            'image/jpeg',
            null,
            true,
        );

        $response = $this->postJson('/api/sppt/permohonan/dokumen/verify', [
            'file' => $front,
            'applicant_ic' => '691115-12-5053',
            'applicant_name' => 'MASRI BIN YAKOP',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.verification.status', 'verified')
            ->assertJsonPath('data.verification.documentType', 'ic_front');
    }

    public function test_permohonan_submit_requires_verified_ic_front_and_back(): void
    {
        $this->actingAsAdmin();
        \Illuminate\Support\Facades\Storage::fake('public');

        $create = $this->postJson('/api/sppt/permohonan', [
            'status' => 'Draf',
            'nama' => 'MASRI BIN YAKOP',
            'details' => [
                'nama' => 'MASRI BIN YAKOP',
                'no_ic_baru' => '691115-12-5053',
            ],
        ]);

        $create->assertStatus(201);
        $permohonanId = $create->json('data.id');

        $front = new \Illuminate\Http\UploadedFile(
            base_path('docs/Sample-MalaysianMyKad-Front.jpg'),
            'ic-depan.jpg',
            'image/jpeg',
            null,
            true,
        );

        $this->postJson("/api/sppt/permohonan/{$permohonanId}/dokumen", ['file' => $front])
            ->assertStatus(201);

        $response = $this->putJson("/api/sppt/permohonan/{$permohonanId}", [
            'nama' => 'MASRI BIN YAKOP',
            'status' => 'Dalam Proses',
            'details' => [
                'nama' => 'MASRI BIN YAKOP',
                'no_ic_baru' => '691115-12-5053',
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonPath('error.details.attachments.0', 'Sila lampirkan salinan MyKad depan dan belakang pemohon dalam dokumen sokongan.');

        $back = new \Illuminate\Http\UploadedFile(
            base_path('docs/Sample-MalaysianMyKad-Back.png'),
            'ic-belakang.png',
            'image/png',
            null,
            true,
        );

        $this->postJson("/api/sppt/permohonan/{$permohonanId}/dokumen", ['file' => $back])
            ->assertStatus(201);

        $submit = $this->putJson("/api/sppt/permohonan/{$permohonanId}", [
            'nama' => 'MASRI BIN YAKOP',
            'status' => 'Dalam Proses',
            'details' => [
                'nama' => 'MASRI BIN YAKOP',
                'no_ic_baru' => '691115-12-5053',
            ],
        ]);

        $submit->assertStatus(200)
            ->assertJsonPath('data.status', 'Dalam Proses');
    }

    public function test_permohonan_submit_rejects_mismatched_ic_front_identity(): void
    {
        $this->actingAsAdmin();
        \Illuminate\Support\Facades\Storage::fake('public');

        $create = $this->postJson('/api/sppt/permohonan', [
            'status' => 'Draf',
            'nama' => 'AHMAD BIN ALI',
            'details' => [
                'nama' => 'AHMAD BIN ALI',
                'no_ic_baru' => '850101-01-1234',
            ],
        ]);

        $create->assertStatus(201);
        $permohonanId = $create->json('data.id');

        $front = new \Illuminate\Http\UploadedFile(
            base_path('docs/Sample-MalaysianMyKad-Front.jpg'),
            'MyICDepan.jpg',
            'image/jpeg',
            null,
            true,
        );

        $back = new \Illuminate\Http\UploadedFile(
            base_path('docs/Sample-MalaysianMyKad-Back.png'),
            'MyICBelakang.jpg',
            'image/png',
            null,
            true,
        );

        $this->postJson("/api/sppt/permohonan/{$permohonanId}/dokumen", ['file' => $front])
            ->assertStatus(201);
        $this->postJson("/api/sppt/permohonan/{$permohonanId}/dokumen", ['file' => $back])
            ->assertStatus(201);

        $response = $this->putJson("/api/sppt/permohonan/{$permohonanId}", [
            'nama' => 'AHMAD BIN ALI',
            'status' => 'Dalam Proses',
            'details' => [
                'nama' => 'AHMAD BIN ALI',
                'no_ic_baru' => '850101-01-1234',
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonPath(
                'error.details.attachments.0',
                'MyKad depan tidak sepadan dengan nama atau No. Kad Pengenalan pemohon.',
            );
    }

    public function test_permohonan_submit_requires_spouse_ic_when_pasangan_has_ic(): void
    {
        $this->actingAsAdmin();
        \Illuminate\Support\Facades\Storage::fake('public');

        $create = $this->postJson('/api/sppt/permohonan', [
            'status' => 'Draf',
            'nama' => 'MASRI BIN YAKOP',
            'details' => [
                'nama' => 'MASRI BIN YAKOP',
                'no_ic_baru' => '691115-12-5053',
                'ada_pasangan' => true,
                'nama_pasangan' => 'SITI AMINAH BINTI ALI',
                'no_ic_pasangan' => '880315-08-5678',
            ],
        ]);

        $create->assertStatus(201);
        $permohonanId = $create->json('data.id');

        $front = new \Illuminate\Http\UploadedFile(
            base_path('docs/Sample-MalaysianMyKad-Front.jpg'),
            'ic-depan.jpg',
            'image/jpeg',
            null,
            true,
        );

        $back = new \Illuminate\Http\UploadedFile(
            base_path('docs/Sample-MalaysianMyKad-Back.png'),
            'ic-belakang.png',
            'image/png',
            null,
            true,
        );

        $this->postJson("/api/sppt/permohonan/{$permohonanId}/dokumen", ['file' => $front])
            ->assertStatus(201);
        $this->postJson("/api/sppt/permohonan/{$permohonanId}/dokumen", ['file' => $back])
            ->assertStatus(201);

        $response = $this->putJson("/api/sppt/permohonan/{$permohonanId}", [
            'nama' => 'MASRI BIN YAKOP',
            'status' => 'Dalam Proses',
            'details' => [
                'nama' => 'MASRI BIN YAKOP',
                'no_ic_baru' => '691115-12-5053',
                'ada_pasangan' => true,
                'nama_pasangan' => 'SITI AMINAH BINTI ALI',
                'no_ic_pasangan' => '880315-08-5678',
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonPath(
                'error.details.attachments.0',
                'Sila lampirkan salinan MyKad depan dan belakang pasangan dalam dokumen sokongan.',
            );
    }

    public function test_permohonan_document_upload_with_json_accept_header(): void
    {
        $this->actingAsAdmin();
        \Illuminate\Support\Facades\Storage::fake('public');

        $create = $this->postJson('/api/sppt/permohonan', [
            'status' => 'Draf',
            'nama' => 'MASRI BIN YAKOP',
        ]);

        $create->assertStatus(201);
        $permohonanId = $create->json('data.id');

        $file = new \Illuminate\Http\UploadedFile(
            base_path('docs/Masri-ic-test.jpg'),
            'Masri ic.jpg',
            'image/jpeg',
            null,
            true,
        );

        $response = $this->call(
            'POST',
            "/api/sppt/permohonan/{$permohonanId}/dokumen",
            [],
            [],
            ['file' => $file],
            ['HTTP_ACCEPT' => 'application/json'],
        );

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Masri ic.jpg');
    }

    public function test_permohonan_document_upload_accepts_large_jpg_over_2mb(): void
    {
        $this->actingAsAdmin();
        \Illuminate\Support\Facades\Storage::fake('public');

        $path = base_path('docs/Sample-MalaysianMyKad-frontAndBack.jpg');
        $this->assertFileExists($path);
        $this->assertGreaterThan(2 * 1024 * 1024, filesize($path));

        $create = $this->postJson('/api/sppt/permohonan', [
            'status' => 'Draf',
            'nama' => 'MASRI BIN YAKOP',
        ]);

        $create->assertStatus(201);
        $permohonanId = $create->json('data.id');

        $file = new \Illuminate\Http\UploadedFile(
            $path,
            'Masri ic.jpg',
            'image/jpeg',
            null,
            true,
        );

        $upload = $this->postJson("/api/sppt/permohonan/{$permohonanId}/dokumen", [
            'file' => $file,
        ]);

        $upload->assertStatus(201)
            ->assertJsonPath('data.name', 'Masri ic.jpg');
    }

    public function test_permohonan_document_upload_accepts_jpg_with_generic_mime_type(): void
    {
        $this->actingAsAdmin();
        \Illuminate\Support\Facades\Storage::fake('public');

        $create = $this->postJson('/api/sppt/permohonan', [
            'status' => 'Draf',
            'nama' => 'MASRI BIN YAKOP',
        ]);

        $create->assertStatus(201);
        $permohonanId = $create->json('data.id');

        $file = new \Illuminate\Http\UploadedFile(
            base_path('docs/Masri-ic-test.jpg'),
            'Masri ic.jpg',
            'application/octet-stream',
            null,
            true,
        );

        $upload = $this->postJson("/api/sppt/permohonan/{$permohonanId}/dokumen", [
            'file' => $file,
        ]);

        $upload->assertStatus(201)
            ->assertJsonPath('data.name', 'Masri ic.jpg');
    }

    public function test_permohonan_document_upload_accepts_combined_ic_scan(): void
    {
        $this->actingAsAdmin();
        \Illuminate\Support\Facades\Storage::fake('public');

        $create = $this->postJson('/api/sppt/permohonan', [
            'status' => 'Draf',
            'nama' => 'MASRI BIN YAKOP',
        ]);

        $create->assertStatus(201);
        $permohonanId = $create->json('data.id');

        $file = new \Illuminate\Http\UploadedFile(
            base_path('docs/Masri-ic-test.jpg'),
            'Masri ic.jpg',
            'image/jpeg',
            null,
            true,
        );

        $upload = $this->postJson("/api/sppt/permohonan/{$permohonanId}/dokumen", [
            'file' => $file,
        ]);

        $upload->assertStatus(201)
            ->assertJsonPath('data.name', 'Masri ic.jpg');
    }

    public function test_permohonan_submit_accepts_combined_ic_front_and_back_in_one_file(): void
    {
        $this->actingAsAdmin();
        \Illuminate\Support\Facades\Storage::fake('public');

        $create = $this->postJson('/api/sppt/permohonan', [
            'status' => 'Draf',
            'nama' => 'MASRI BIN YAKOP',
            'details' => [
                'nama' => 'MASRI BIN YAKOP',
                'no_ic_baru' => '691115-12-5053',
            ],
        ]);

        $create->assertStatus(201);
        $permohonanId = $create->json('data.id');

        $combined = new \Illuminate\Http\UploadedFile(
            base_path('docs/Masri-ic-test.jpg'),
            'Masri ic.jpg',
            'image/jpeg',
            null,
            true,
        );

        $this->postJson("/api/sppt/permohonan/{$permohonanId}/dokumen", ['file' => $combined])
            ->assertStatus(201);

        $submit = $this->putJson("/api/sppt/permohonan/{$permohonanId}", [
            'nama' => 'MASRI BIN YAKOP',
            'status' => 'Dalam Proses',
            'details' => [
                'nama' => 'MASRI BIN YAKOP',
                'no_ic_baru' => '691115-12-5053',
            ],
        ]);

        $submit->assertStatus(200)
            ->assertJsonPath('data.status', 'Dalam Proses');
    }

    public function test_permohonan_document_verify_detects_combined_ic_scan(): void
    {
        $this->actingAsAdmin();

        $combined = new \Illuminate\Http\UploadedFile(
            base_path('docs/Masri-ic-test.jpg'),
            'Masri ic.jpg',
            'image/jpeg',
            null,
            true,
        );

        $response = $this->postJson('/api/sppt/permohonan/dokumen/verify', [
            'file' => $combined,
            'applicant_ic' => '691115-12-5053',
            'applicant_name' => 'MASRI BIN YAKOP',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.verification.status', 'verified')
            ->assertJsonPath('data.verification.documentType', 'ic_combined');
    }

    public function test_permohonan_document_classify_from_filename(): void
    {
        $this->actingAsAdmin();

        $file = \Illuminate\Http\UploadedFile::fake()->create('penyata-bank-maybank.pdf', 100, 'application/pdf');

        $response = $this->postJson('/api/sppt/permohonan/dokumen/classify', [
            'file' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.classification.suggestedClass', 'penyata_bank')
            ->assertJsonStructure([
                'data' => [
                    'classification' => ['suggestedClass', 'confidence', 'message'],
                    'name',
                ],
            ]);
    }

    public function test_permohonan_document_classify_requires_auth(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('penyata-bank.pdf', 100, 'application/pdf');

        $this->postJson('/api/sppt/permohonan/dokumen/classify', ['file' => $file])
            ->assertStatus(401);
    }

    public function test_permohonan_document_upload_stores_document_class(): void
    {
        $this->actingAsAdmin();
        \Illuminate\Support\Facades\Storage::fake('public');

        $create = $this->postJson('/api/sppt/permohonan', [
            'status' => 'Draf',
            'nama' => 'Test Pemohon',
        ]);
        $create->assertStatus(201);
        $permohonanId = $create->json('data.id');

        $file = \Illuminate\Http\UploadedFile::fake()->create('penyata-bank.pdf', 100, 'application/pdf');

        $upload = $this->postJson("/api/sppt/permohonan/{$permohonanId}/dokumen", [
            'file' => $file,
            'document_class' => 'penyata_bank',
        ]);

        $upload->assertStatus(201)
            ->assertJsonPath('data.documentClass', 'penyata_bank')
            ->assertJsonPath('data.documentClassLabel', 'Penyata Bank');

        $show = $this->getJson("/api/sppt/permohonan/{$permohonanId}");
        $show->assertStatus(200)
            ->assertJsonPath('data.details.attachments.0.documentClass', 'penyata_bank');
    }

    public function test_permohonan_document_upload_stores_camel_case_document_class_from_multipart(): void
    {
        $this->actingAsAdmin();
        \Illuminate\Support\Facades\Storage::fake('public');

        $create = $this->postJson('/api/sppt/permohonan', [
            'status' => 'Draf',
            'nama' => 'Test Pemohon',
        ]);
        $create->assertStatus(201);
        $permohonanId = $create->json('data.id');

        $file = \Illuminate\Http\UploadedFile::fake()->image('mykad-depan.png');

        $upload = $this->post("/api/sppt/permohonan/{$permohonanId}/dokumen", [
            'file' => $file,
            'documentClass' => 'ic_pemohon_depan',
        ], [
            'Accept' => 'application/json',
        ]);

        $upload->assertStatus(201)
            ->assertJsonPath('data.documentClass', 'ic_pemohon_depan')
            ->assertJsonPath('data.documentClassLabel', 'IC Pemohon (Depan)');

        $show = $this->getJson("/api/sppt/permohonan/{$permohonanId}");
        $show->assertStatus(200)
            ->assertJsonPath('data.details.attachments.0.documentClass', 'ic_pemohon_depan');
    }

    public function test_permohonan_document_upload_stores_document_class_without_accept_header(): void
    {
        $this->actingAsAdmin();
        \Illuminate\Support\Facades\Storage::fake('public');

        $create = $this->postJson('/api/sppt/permohonan', [
            'status' => 'Draf',
            'nama' => 'Test Pemohon',
        ]);
        $permohonanId = $create->json('data.id');

        $file = \Illuminate\Http\UploadedFile::fake()->image('mykad-depan.png');

        $upload = $this->post("/api/sppt/permohonan/{$permohonanId}/dokumen", [
            'file' => $file,
            'documentClass' => 'ic_pemohon_depan',
        ]);

        $upload->assertStatus(201)
            ->assertJsonPath('data.documentClass', 'ic_pemohon_depan');
    }

    public function test_permohonan_document_update_class_persists(): void
    {
        $this->actingAsAdmin();
        \Illuminate\Support\Facades\Storage::fake('public');

        $create = $this->postJson('/api/sppt/permohonan', [
            'status' => 'Draf',
            'nama' => 'Test Pemohon',
        ]);
        $permohonanId = $create->json('data.id');

        $file = \Illuminate\Http\UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf');
        $upload = $this->postJson("/api/sppt/permohonan/{$permohonanId}/dokumen", ['file' => $file]);
        $upload->assertStatus(201);
        $attachmentId = $upload->json('data.id');

        $update = $this->patchJson("/api/sppt/permohonan/{$permohonanId}/dokumen/{$attachmentId}", [
            'document_class' => 'lain_lain',
            'document_class_other' => 'Surat Pengesahan Majikan',
        ]);

        $update->assertStatus(200)
            ->assertJsonPath('data.documentClass', 'lain_lain')
            ->assertJsonPath('data.documentClassOther', 'Surat Pengesahan Majikan');
    }

    public function test_permohonan_form_ocr_requires_auth(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('borang.pdf', 100, 'application/pdf');

        $this->postJson('/api/sppt/permohonan/ocr/extract', ['file' => $file])
            ->assertStatus(401);
    }

    public function test_permohonan_form_ocr_validation_error_without_file(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/sppt/permohonan/ocr/extract', [])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_permohonan_form_ocr_extracts_fields(): void
    {
        $this->actingAsAdmin();

        \Illuminate\Support\Facades\Http::fake([
            'api.anthropic.com/*' => \Illuminate\Support\Facades\Http::response([
                'content' => [[
                    'type' => 'text',
                    'text' => json_encode([
                            'confidence' => 90,
                            'field_confidence' => ['nama' => 95],
                            'fields' => [
                                'nama' => 'MASRI BIN YAKOP',
                                'no_ic_baru' => '691115-12-5053',
                                'kategori_pembiayaan' => 'TEKUN Niaga',
                            ],
                        ], JSON_THROW_ON_ERROR),
                ]],
            ], 200),
        ]);

        config(['services.anthropic.key' => 'test-key']);

        $file = \Illuminate\Http\UploadedFile::fake()->image('borang.jpg');

        $response = $this->postJson('/api/sppt/permohonan/ocr/extract', ['file' => $file]);

        $response->assertStatus(200)
            ->assertJsonPath('data.confidence', 90)
            ->assertJsonPath('data.fields.nama', 'MASRI BIN YAKOP')
            ->assertJsonPath('data.fields.noIcBaru', '691115-12-5053')
            ->assertJsonPath('data.populatedCount', 3);
    }
}
