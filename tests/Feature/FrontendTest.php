<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;

class FrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_loads()
    {
        $this->get('/login')->assertStatus(200)->assertSeeLivewire('auth.login');
    }

    public function test_register_page_loads()
    {
        $this->get('/register')->assertStatus(200)->assertSeeLivewire('auth.register');
    }

    public function test_projects_page_redirects_if_unauthenticated()
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_projects_page_loads_for_authenticated_user()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->get('/')->assertStatus(200)->assertSeeLivewire('projects');
    }
}
