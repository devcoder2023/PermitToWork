<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Project;
use App\Models\SubContractor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');
        $role = $request->input('role', '');
        $projectId = $request->input('project_id', '');
        $subContractorId = $request->input('sub_contractor_id', '');

        $users = User::query()
            ->with(['project:id,name', 'subContractor:id,name'])
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($role, function ($query, $role) {
                $query->where('role', $role);
            })
            ->when($projectId, function ($query, $projectId) {
                $query->where('project_id', $projectId);
            })
            ->when($subContractorId, function ($query, $subContractorId) {
                $query->where('sub_contractor_id', $subContractorId);
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'role' => $role,
                'project_id' => $projectId,
                'sub_contractor_id' => $subContractorId,
            ],
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            'subContractors' => SubContractor::orderBy('name')->get(['id', 'name']),
            'roles' => collect(Role::cases())->map(fn (Role $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/users/create', [
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            'subContractors' => SubContractor::orderBy('name')->get(['id', 'name']),
            'roles' => collect(Role::cases())->map(fn (Role $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', 'in:'.implode(',', Role::values())],
            'project_id' => ['nullable', 'exists:projects,id'],
            'sub_contractor_id' => ['nullable', 'exists:sub_contractors,id'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('admin/users/edit', [
            'user' => $user,
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            'subContractors' => SubContractor::orderBy('name')->get(['id', 'name']),
            'roles' => collect(Role::cases())->map(fn (Role $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ]),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', 'in:'.implode(',', Role::values())],
            'project_id' => ['nullable', 'exists:projects,id'],
            'sub_contractor_id' => ['nullable', 'exists:sub_contractors,id'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
