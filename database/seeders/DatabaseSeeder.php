<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        // =====================================================================
        // COMPANY 1: PT Contoh Alpha
        // =====================================================================
        $companyAlpha = Company::create(['name' => 'PT Contoh Alpha']);

        // Users Alpha
        $adminAlpha = User::create([
            'company_id' => $companyAlpha->id,
            'name'       => 'Admin Alpha',
            'email'      => 'admin@alpha.com',
            'password'   => $password,
            'role'       => 'admin',
        ]);

        $memberAlpha1 = User::create([
            'company_id' => $companyAlpha->id,
            'name'       => 'Budi (Member Alpha)',
            'email'      => 'budi@alpha.com',
            'password'   => $password,
            'role'       => 'member',
        ]);

        $memberAlpha2 = User::create([
            'company_id' => $companyAlpha->id,
            'name'       => 'Siti (Member Alpha)',
            'email'      => 'siti@alpha.com',
            'password'   => $password,
            'role'       => 'member',
        ]);

        // Projects Alpha
        $projectAlpha1 = Project::create([
            'company_id'  => $companyAlpha->id,
            'created_by'  => $adminAlpha->id,
            'name'        => 'Website Redesign',
            'description' => 'Mendesain ulang website utama PT Contoh Alpha.',
        ]);

        $projectAlpha2 = Project::create([
            'company_id'  => $companyAlpha->id,
            'created_by'  => $adminAlpha->id,
            'name'        => 'Mobile App Launch',
            'description' => 'Persiapan rilis aplikasi mobile versi 1.0.',
        ]);

        // Tasks Alpha
        $this->createTasks($projectAlpha1, $companyAlpha, $adminAlpha, [$memberAlpha1, $memberAlpha2]);
        $this->createTasks($projectAlpha2, $companyAlpha, $adminAlpha, [$memberAlpha1, $memberAlpha2]);


        // =====================================================================
        // COMPANY 2: PT Contoh Beta
        // =====================================================================
        $companyBeta = Company::create(['name' => 'PT Contoh Beta']);

        // Users Beta
        $adminBeta = User::create([
            'company_id' => $companyBeta->id,
            'name'       => 'Admin Beta',
            'email'      => 'admin@beta.com',
            'password'   => $password,
            'role'       => 'admin',
        ]);

        $memberBeta1 = User::create([
            'company_id' => $companyBeta->id,
            'name'       => 'Joko (Member Beta)',
            'email'      => 'joko@beta.com',
            'password'   => $password,
            'role'       => 'member',
        ]);

        $memberBeta2 = User::create([
            'company_id' => $companyBeta->id,
            'name'       => 'Ani (Member Beta)',
            'email'      => 'ani@beta.com',
            'password'   => $password,
            'role'       => 'member',
        ]);

        // Projects Beta
        $projectBeta1 = Project::create([
            'company_id'  => $companyBeta->id,
            'created_by'  => $adminBeta->id,
            'name'        => 'Ekspansi Pasar',
            'description' => 'Riset dan eksekusi ekspansi ke pasar luar negeri.',
        ]);

        $projectBeta2 = Project::create([
            'company_id'  => $companyBeta->id,
            'created_by'  => $adminBeta->id,
            'name'        => 'Efisiensi Operasional',
            'description' => 'Audit dan pemangkasan biaya operasional.',
        ]);

        // Tasks Beta
        $this->createTasks($projectBeta1, $companyBeta, $adminBeta, [$memberBeta1, $memberBeta2]);
        $this->createTasks($projectBeta2, $companyBeta, $adminBeta, [$memberBeta1, $memberBeta2]);
    }

    /**
     * Helper untuk generate task bervariasi
     */
    private function createTasks(Project $project, Company $company, User $admin, array $members): void
    {
        $statuses = ['todo', 'in_progress', 'done'];

        // Buat 3-4 task secara random
        $taskCount = rand(3, 4);

        for ($i = 1; $i <= $taskCount; $i++) {
            $isAssigned = rand(0, 1); // 50% chance assigned ke member
            $assignedUser = $isAssigned ? $members[array_rand($members)] : null;

            Task::create([
                'project_id'  => $project->id,
                'company_id'  => $company->id,
                'created_by'  => $admin->id,
                'title'       => 'Tugas ' . $i . ' untuk ' . $project->name,
                'description' => 'Deskripsi detail untuk tugas ' . $i . ' di project ' . $project->name,
                'status'      => $statuses[array_rand($statuses)],
                'assigned_to' => $assignedUser ? $assignedUser->id : null,
            ]);
        }
    }
}
