<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $admin;
    private User $member1;
    private User $member2;
    private Project $project;
    private Task $taskMember1;
    private Task $taskMember2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'admin',
        ]);
        $this->member1 = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'member',
        ]);
        $this->member2 = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'member',
        ]);

        $this->project = Project::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
        ]);

        $this->taskMember1 = Task::factory()->create([
            'project_id' => $this->project->id,
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
            'assigned_to' => $this->member1->id,
            'status' => 'todo',
        ]);

        $this->taskMember2 = Task::factory()->create([
            'project_id' => $this->project->id,
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
            'assigned_to' => $this->member2->id,
            'status' => 'todo',
        ]);
    }

    public function test_member_cannot_delete_project(): void
    {
        $response = $this->actingAs($this->member1)
            ->deleteJson("/api/v1/projects/{$this->project->id}");

        $response->assertStatus(403);
    }

    public function test_member_cannot_update_task_assigned_to_other_user(): void
    {
        // member1 mencoba update task milik member2
        $response = $this->actingAs($this->member1)
            ->patchJson("/api/v1/projects/{$this->project->id}/tasks/{$this->taskMember2->id}", [
                'status' => 'done',
            ]);

        $response->assertStatus(403);
    }

    public function test_member_can_update_status_of_own_task(): void
    {
        // member1 update task miliknya sendiri
        $response = $this->actingAs($this->member1)
            ->patchJson("/api/v1/projects/{$this->project->id}/tasks/{$this->taskMember1->id}", [
                'status' => 'done',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', [
            'id' => $this->taskMember1->id,
            'status' => 'done',
        ]);
    }

    public function test_member_cannot_create_project(): void
    {
        $response = $this->actingAs($this->member1)
            ->postJson("/api/v1/projects", [
                'name' => 'New Project',
            ]);

        $response->assertStatus(403);
    }
}
