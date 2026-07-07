<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\WfProcess;
use App\Models\WfProcessDetail;
use App\Models\WfWorkflowName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkflowConfigurationTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $role = Role::create([
            'name' => 'admin',
            'description' => 'Admin',
            'permissions' => ['settings.view'],
        ]);

        $user = User::factory()->create([
            'role' => 'admin',
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($user);

        return $user;
    }

    public function test_workflow_configuration_requires_auth(): void
    {
        $this->getJson('/api/workflow-configuration')->assertStatus(401);
    }

    public function test_workflow_configuration_lists_workflows(): void
    {
        $this->actingAsAdmin();

        WfWorkflowName::create([
            'wfa_workflow_code' => 'TEST_WF',
            'wfa_workflow_title' => 'Test Workflow',
            'wfa_involve_posting' => 1,
        ]);

        $response = $this->getJson('/api/workflow-configuration');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.wfaWorkflowCode', 'TEST_WF')
            ->assertJsonPath('data.0.wfaWorkflowTitle', 'Test Workflow');
    }

    public function test_store_workflow_validation_error(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/workflow-configuration/workflow', []);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_store_workflow_success(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/workflow-configuration/workflow', [
            'wfaWorkflowCode' => 'test_wf',
            'wfaWorkflowTitle' => 'Test Workflow',
            'wfaPreventSelfProcess' => 0,
            'wfaInvolvePosting' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.wfaWorkflowCode', 'TEST_WF');

        $this->assertDatabaseHas('wf_workflow_name', [
            'wfa_workflow_code' => 'TEST_WF',
            'wfa_workflow_title' => 'Test Workflow',
        ]);
    }

    public function test_destroy_process_blocked_when_details_exist(): void
    {
        $this->actingAsAdmin();

        WfWorkflowName::create([
            'wfa_workflow_code' => 'TEST_WF',
            'wfa_workflow_title' => 'Test Workflow',
            'wfa_involve_posting' => 1,
        ]);

        $process = WfProcess::create([
            'wfp_workflow_code' => 'TEST_WF',
            'wfp_process_name' => 'ENTRY',
            'wfp_sequence' => 1,
            'wfp_status' => '1',
        ]);

        WfProcessDetail::create([
            'wpd_process_id' => $process->wfp_process_id,
            'wpd_status_code' => 'APPROVE',
            'wpd_order' => 1,
            'wpd_extended_field' => ['wpd_status_desc' => 'Approved'],
        ]);

        $response = $this->deleteJson("/api/workflow-configuration/process/{$process->wfp_process_id}");

        $response->assertStatus(409)
            ->assertJsonPath('error.code', 'HAS_DETAILS');
    }

    public function test_status_lookup_returns_seeded_codes(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/workflow-configuration/status-lookup');

        $response->assertStatus(200)
            ->assertJsonFragment(['kod' => 'APPROVE', 'keterangan' => 'Approve']);
    }

    public function test_role_lookup_returns_roles(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/workflow-configuration/peranan-rujukan');

        $response->assertStatus(200)
            ->assertJsonFragment(['code' => 'admin', 'description' => 'Admin']);
    }
}
