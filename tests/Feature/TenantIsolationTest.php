<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;
    private User $adminA;
    private User $memberA;
    private Project $projectB;
    private Task $taskB;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Company A
        $this->companyA = Company::factory()->create();
        $this->adminA = User::factory()->create([
            'company_id' => $this->companyA->id,
            'role' => 'admin',
        ]);
        $this->memberA = User::factory()->create([
            'company_id' => $this->companyA->id,
            'role' => 'member',
        ]);
        $projectA = Project::factory()->create([
            'company_id' => $this->companyA->id,
            'created_by' => $this->adminA->id,
        ]);
        Task::factory()->create([
            'project_id' => $projectA->id,
            'company_id' => $this->companyA->id,
            'created_by' => $this->adminA->id,
            'assigned_to' => $this->memberA->id,
        ]);

        // Setup Company B
        $this->companyB = Company::factory()->create();
        $adminB = User::factory()->create([
            'company_id' => $this->companyB->id,
            'role' => 'admin',
        ]);
        $this->projectB = Project::factory()->create([
            'company_id' => $this->companyB->id,
            'created_by' => $adminB->id,
        ]);
        $this->taskB = Task::factory()->create([
            'project_id' => $this->projectB->id,
            'company_id' => $this->companyB->id,
            'created_by' => $adminB->id,
            'assigned_to' => $adminB->id, // just someone in company B
        ]);
    }

    public function test_user_cannot_view_project_from_another_company(): void
    {
        // User dari Company A mencoba akses project milik Company B
        $response = $this->actingAs($this->adminA)
            ->getJson("/api/v1/projects/{$this->projectB->id}");

        // Assert 404 karena CompanyScope menyembunyikan project B dari query user A
        $response->assertStatus(404);
    }

    public function test_user_cannot_update_project_from_another_company(): void
    {
        // User dari Company A mencoba update project milik Company B
        $response = $this->actingAs($this->adminA)
            ->patchJson("/api/v1/projects/{$this->projectB->id}", [
                'name' => 'Hacked Name',
            ]);

        // Assert 404 karena CompanyScope
        $response->assertStatus(404);
    }

    public function test_user_cannot_view_task_from_another_company(): void
    {
        // User dari Company A mencoba akses task milik Company B
        $response = $this->actingAs($this->adminA)
            ->getJson("/api/v1/projects/{$this->projectB->id}/tasks/{$this->taskB->id}");

        $response->assertStatus(404);
    }
}
