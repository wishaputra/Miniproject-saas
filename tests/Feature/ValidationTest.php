<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;
    private User $adminA;
    private User $memberB;
    private Project $projectA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::factory()->create();
        $this->adminA = User::factory()->create([
            'company_id' => $this->companyA->id,
            'role' => 'admin',
        ]);

        $this->companyB = Company::factory()->create();
        $this->memberB = User::factory()->create([
            'company_id' => $this->companyB->id,
            'role' => 'member',
        ]);

        $this->projectA = Project::factory()->create([
            'company_id' => $this->companyA->id,
            'created_by' => $this->adminA->id,
        ]);
    }

    public function test_create_project_requires_name(): void
    {
        $response = $this->actingAs($this->adminA)
            ->postJson("/api/v1/projects", [
                // 'name' => 'Project 1', // missing name
                'description' => 'Test project',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_task_requires_title(): void
    {
        $response = $this->actingAs($this->adminA)
            ->postJson("/api/v1/projects/{$this->projectA->id}/tasks", [
                // 'title' => 'Task 1', // missing title
                'status' => 'todo',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_create_task_cannot_assign_to_user_from_different_company(): void
    {
        // Admin A mencoba assign task ke Member B (yang beda company)
        $response = $this->actingAs($this->adminA)
            ->postJson("/api/v1/projects/{$this->projectA->id}/tasks", [
                'title' => 'Task 1',
                'assigned_to' => $this->memberB->id, // beda company
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['assigned_to']);
    }
}
