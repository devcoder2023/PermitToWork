<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Project;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SiteController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $search = $request->input('search', '');

        $sites = $project->sites()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/projects/sites/index', [
            'project' => $project,
            'sites' => $sites,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(Project $project): Response
    {
        $availableManagers = User::query()
            ->where('role', Role::SiteManager)
            ->where(function ($query) use ($project) {
                $query->whereNull('project_id')
                    ->orWhere('project_id', $project->id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $availableHseOfficers = User::query()
            ->where('role', Role::HseOfficer)
            ->where(function ($query) use ($project) {
                $query->whereNull('project_id')
                    ->orWhere('project_id', $project->id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('admin/projects/sites/create', [
            'project' => $project,
            'availableManagers' => $availableManagers,
            'availableHseOfficers' => $availableHseOfficers,
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'site_managers' => ['array'],
            'site_managers.*' => ['exists:users,id'],
            'hse_officers' => ['array'],
            'hse_officers.*' => ['exists:users,id'],
        ]);

        $site = $project->sites()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        if (! empty($validated['site_managers'])) {
            foreach ($validated['site_managers'] as $managerId) {
                $site->users()->attach($managerId, ['role' => 'site_manager']);
            }
        }

        if (! empty($validated['hse_officers'])) {
            foreach ($validated['hse_officers'] as $officerId) {
                $site->users()->attach($officerId, ['role' => 'hse_officer']);
            }
        }

        return redirect()->route('admin.projects.sites.index', $project)
            ->with('success', 'Site created successfully.');
    }

    public function edit(Site $site): Response
    {
        $site->load(['users' => function ($query) {
            $query->select('users.id', 'users.name', 'users.email');
        }]);

        $project = $site->project;

        $availableManagers = User::query()
            ->where('role', Role::SiteManager)
            ->where(function ($query) use ($project) {
                $query->whereNull('project_id')
                    ->orWhere('project_id', $project->id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $availableHseOfficers = User::query()
            ->where('role', Role::HseOfficer)
            ->where(function ($query) use ($project) {
                $query->whereNull('project_id')
                    ->orWhere('project_id', $project->id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $currentManagers = $site->users
            ->filter(fn ($user) => $user->pivot->role === 'site_manager')
            ->pluck('id')
            ->toArray();

        $currentHseOfficers = $site->users
            ->filter(fn ($user) => $user->pivot->role === 'hse_officer')
            ->pluck('id')
            ->toArray();

        return Inertia::render('admin/projects/sites/edit', [
            'project' => $project,
            'site' => $site,
            'availableManagers' => $availableManagers,
            'availableHseOfficers' => $availableHseOfficers,
            'currentManagers' => $currentManagers,
            'currentHseOfficers' => $currentHseOfficers,
        ]);
    }

    public function update(Request $request, Site $site): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'site_managers' => ['array'],
            'site_managers.*' => ['exists:users,id'],
            'hse_officers' => ['array'],
            'hse_officers.*' => ['exists:users,id'],
        ]);

        $site->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $site->users()->detach();

        if (! empty($validated['site_managers'])) {
            foreach ($validated['site_managers'] as $managerId) {
                $site->users()->attach($managerId, ['role' => 'site_manager']);
            }
        }

        if (! empty($validated['hse_officers'])) {
            foreach ($validated['hse_officers'] as $officerId) {
                $site->users()->attach($officerId, ['role' => 'hse_officer']);
            }
        }

        return redirect()->route('admin.projects.sites.index', $site->project)
            ->with('success', 'Site updated successfully.');
    }

    public function destroy(Site $site): RedirectResponse
    {
        $project = $site->project;
        $site->users()->detach();
        $site->delete();

        return redirect()->route('admin.projects.sites.index', $project)
            ->with('success', 'Site deleted successfully.');
    }
}
