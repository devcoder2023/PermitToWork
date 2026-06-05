<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Site;
use App\Models\SubContractor;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermitTypeSeeder::class);

        $projects = Project::factory(2)->create();

        $sites = Site::factory(3)->create([
            'project_id' => $projects->first()->id,
        ]);

        $subContractors = SubContractor::factory(2)->create();

        $projects->first()->subContractors()->attach($subContractors->first()->id);
        $projects->last()->subContractors()->attach($subContractors->last()->id);

        $admin = User::factory()->admin()->create([
            'name' => 'System Admin',
            'email' => 'admin@example.com',
        ]);

        $engineer1 = User::factory()->engineer()->create([
            'name' => 'Engineer Main',
            'email' => 'engineer.main@example.com',
            'project_id' => $projects->first()->id,
        ]);

        $engineer2 = User::factory()->engineer()->create([
            'name' => 'Engineer Sub',
            'email' => 'engineer.sub@example.com',
            'project_id' => $projects->first()->id,
            'sub_contractor_id' => $subContractors->first()->id,
        ]);

        $siteManager = User::factory()->siteManager()->create([
            'name' => 'Site Manager',
            'email' => 'site.manager@example.com',
            'project_id' => $projects->first()->id,
        ]);
        $siteManager->sites()->attach($sites->first()->id, ['role' => 'site_manager']);

        $permitOfficer = User::factory()->permitOfficer()->create([
            'name' => 'Permit Officer',
            'email' => 'permit.officer@example.com',
            'project_id' => $projects->first()->id,
        ]);

        $workSupervisor = User::factory()->workSupervisor()->create([
            'name' => 'Work Supervisor',
            'email' => 'work.supervisor@example.com',
            'project_id' => $projects->first()->id,
        ]);

        $hseOfficer = User::factory()->hseOfficer()->create([
            'name' => 'HSE Officer',
            'email' => 'hse.officer@example.com',
            'project_id' => $projects->first()->id,
        ]);
        $hseOfficer->sites()->attach($sites->first()->id, ['role' => 'hse_officer']);

        $consultant = User::factory()->consultant()->create([
            'name' => 'Consultant',
            'email' => 'consultant@example.com',
            'project_id' => $projects->first()->id,
        ]);

        $qaInspector = User::factory()->qaInspector()->create([
            'name' => 'QA Inspector',
            'email' => 'qa.inspector@example.com',
        ]);
    }
}
