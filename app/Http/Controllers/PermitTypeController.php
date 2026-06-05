<?php

namespace App\Http\Controllers;

use App\Models\PermitType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PermitTypeController extends Controller
{
    public function index(): Response
    {
        $permitTypes = PermitType::orderBy('name_en')->get();

        return Inertia::render('admin/permit-types/index', [
            'permitTypes' => $permitTypes,
        ]);
    }

    public function update(Request $request, PermitType $permitType): RedirectResponse
    {
        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $permitType->update($validated);

        return redirect()->route('admin.permit-types.index')
            ->with('success', 'Permit type updated successfully.');
    }
}
