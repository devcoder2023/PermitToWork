<?php

namespace App\Http\Controllers;

use App\Models\SubContractor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubContractorController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');

        $subContractors = SubContractor::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('contact_email', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/sub-contractors/index', [
            'subContractors' => $subContractors,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/sub-contractors/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255', 'unique:sub_contractors,contact_email'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        SubContractor::create($validated);

        return redirect()->route('admin.sub-contractors.index')
            ->with('success', 'Sub-contractor created successfully.');
    }

    public function edit(SubContractor $subContractor): Response
    {
        return Inertia::render('admin/sub-contractors/edit', [
            'subContractor' => $subContractor,
        ]);
    }

    public function update(Request $request, SubContractor $subContractor): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255', 'unique:sub_contractors,contact_email,'.$subContractor->id],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        $subContractor->update($validated);

        return redirect()->route('admin.sub-contractors.index')
            ->with('success', 'Sub-contractor updated successfully.');
    }

    public function destroy(SubContractor $subContractor): RedirectResponse
    {
        $subContractor->delete();

        return redirect()->route('admin.sub-contractors.index')
            ->with('success', 'Sub-contractor deleted successfully.');
    }
}
