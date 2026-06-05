<?php

namespace App\Http\Controllers;

use App\Enums\ObservationStatus;
use App\Enums\PermitStatus;
use App\Enums\Role;
use App\Models\Observation;
use App\Models\ObservationAttachment;
use App\Models\WorkPermit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ObservationController extends Controller
{
    public function store(Request $request, WorkPermit $permit): RedirectResponse
    {
        $user = $request->user();

        if (! in_array($user->role, [Role::HseOfficer, Role::Consultant])) {
            abort(403, 'Only HSE Officers and Consultants can create observations.');
        }

        if ($permit->status !== PermitStatus::Active) {
            return back()->with('error', 'Observations can only be created for active permits.');
        }

        $isHseAssigned = $permit->site->users()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'hse_officer')
            ->exists();

        if ($user->role === Role::HseOfficer && ! $isHseAssigned) {
            return back()->with('error', 'You are not assigned to this site.');
        }

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:5000'],
            'attachments.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,pdf', 'max:10240'],
        ]);

        $observation = Observation::create([
            'work_permit_id' => $permit->id,
            'created_by' => $user->id,
            'description' => $validated['description'],
            'status' => ObservationStatus::Open,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('observations', 'local');
                ObservationAttachment::create([
                    'observation_id' => $observation->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                ]);
            }
        }

        return back()->with('success', 'Observation created successfully.');
    }

    public function resolve(Request $request, Observation $observation): RedirectResponse
    {
        $user = $request->user();

        if (! $observation->canBeResolvedBy($user)) {
            return back()->with('error', 'You are not authorized to resolve this observation.');
        }

        $validated = $request->validate([
            'resolution_note' => ['required', 'string', 'max:5000'],
        ]);

        $observation->update([
            'status' => ObservationStatus::Resolved,
            'resolution_note' => $validated['resolution_note'],
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Observation resolved successfully.');
    }

    public function acceptResolution(Observation $observation): RedirectResponse
    {
        $user = request()->user();

        if ($user->role !== Role::HseOfficer) {
            abort(403, 'Only HSE Officers can accept resolutions.');
        }

        $isHseAssigned = $observation->workPermit->site->users()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'hse_officer')
            ->exists();

        if (! $isHseAssigned) {
            return back()->with('error', 'You are not assigned to this site.');
        }

        if ($observation->status !== ObservationStatus::Resolved) {
            return back()->with('error', 'Only resolved observations can be accepted.');
        }

        $observation->update([
            'status' => ObservationStatus::Closed,
            'closed_at' => now(),
            'rejection_reason' => null,
        ]);

        return back()->with('success', 'Observation accepted and closed.');
    }

    public function rejectResolution(Request $request, Observation $observation): RedirectResponse
    {
        $user = request()->user();

        if ($user->role !== Role::HseOfficer) {
            abort(403, 'Only HSE Officers can reject resolutions.');
        }

        $isHseAssigned = $observation->workPermit->site->users()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'hse_officer')
            ->exists();

        if (! $isHseAssigned) {
            return back()->with('error', 'You are not assigned to this site.');
        }

        if ($observation->status !== ObservationStatus::Resolved) {
            return back()->with('error', 'Only resolved observations can be rejected.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $observation->update([
            'status' => ObservationStatus::Open,
            'rejection_reason' => $validated['rejection_reason'],
            'resolved_at' => null,
        ]);

        return back()->with('success', 'Observation rejected and returned for re-resolution.');
    }

    public function suspend(Request $request, WorkPermit $permit): RedirectResponse
    {
        $user = $request->user();

        if (! in_array($user->role, [Role::HseOfficer, Role::Consultant])) {
            abort(403, 'Only HSE Officers and Consultants can suspend permits.');
        }

        if ($permit->status !== PermitStatus::Active) {
            return back()->with('error', 'Only active permits can be suspended.');
        }

        if ($user->role === Role::HseOfficer) {
            $isHseAssigned = $permit->site->users()
                ->where('users.id', $user->id)
                ->wherePivot('role', 'hse_officer')
                ->exists();

            if (! $isHseAssigned) {
                return back()->with('error', 'You are not assigned to this site.');
            }
        }

        $validated = $request->validate([
            'suspension_reason' => ['required', 'string', 'max:2000'],
        ]);

        $permit->update([
            'status' => PermitStatus::Suspended,
            'suspension_reason' => $validated['suspension_reason'],
        ]);

        return back()->with('success', 'Permit suspended successfully.');
    }

    public function requestResume(Request $request, WorkPermit $permit): RedirectResponse
    {
        $user = $request->user();

        if ($permit->status !== PermitStatus::Suspended) {
            return back()->with('error', 'Only suspended permits can request resumption.');
        }

        $isEngineerOrSupervisor = $user->id === $permit->engineer_id || $user->id === $permit->supervisor_id;

        if (! $isEngineerOrSupervisor) {
            abort(403, 'Only the engineer or supervisor can request resumption.');
        }

        $validated = $request->validate([
            'resumption_note' => ['required', 'string', 'max:2000'],
        ]);

        $permit->update([
            'resumption_requested' => true,
            'resumption_note' => $validated['resumption_note'],
        ]);

        return back()->with('success', 'Resumption request submitted successfully.');
    }

    public function approveResume(Request $request, WorkPermit $permit): RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== Role::HseOfficer) {
            abort(403, 'Only HSE Officers can approve resumption requests.');
        }

        if ($permit->status !== PermitStatus::Suspended) {
            return back()->with('error', 'Only suspended permits can be resumed.');
        }

        $isHseAssigned = $permit->site->users()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'hse_officer')
            ->exists();

        if (! $isHseAssigned) {
            return back()->with('error', 'You are not assigned to this site.');
        }

        if (! $permit->resumption_requested) {
            return back()->with('error', 'No resumption request found for this permit.');
        }

        $validated = $request->validate([
            'resumption_approval_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $permit->update([
            'status' => PermitStatus::Active,
            'resumption_requested' => false,
            'resumption_approval_note' => $validated['resumption_approval_note'] ?? null,
            'resumption_approved_by' => $user->id,
            'resumption_approved_at' => now(),
        ]);

        return back()->with('success', 'Permit resumed successfully.');
    }

    public function rejectResume(Request $request, WorkPermit $permit): RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== Role::HseOfficer) {
            abort(403, 'Only HSE Officers can reject resumption requests.');
        }

        if ($permit->status !== PermitStatus::Suspended) {
            return back()->with('error', 'Only suspended permits can reject resumption.');
        }

        $isHseAssigned = $permit->site->users()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'hse_officer')
            ->exists();

        if (! $isHseAssigned) {
            return back()->with('error', 'You are not assigned to this site.');
        }

        $validated = $request->validate([
            'resumption_rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $permit->update([
            'resumption_requested' => false,
            'resumption_rejection_reason' => $validated['resumption_rejection_reason'],
        ]);

        return back()->with('success', 'Resumption request rejected.');
    }

    public function terminate(Request $request, WorkPermit $permit): RedirectResponse
    {
        $user = $request->user();

        if (! in_array($user->role, [Role::HseOfficer, Role::Consultant])) {
            abort(403, 'Only HSE Officers and Consultants can terminate permits.');
        }

        if (! in_array($permit->status, [PermitStatus::Active, PermitStatus::Suspended])) {
            return back()->with('error', 'Only active or suspended permits can be terminated.');
        }

        if ($user->role === Role::HseOfficer) {
            $isHseAssigned = $permit->site->users()
                ->where('users.id', $user->id)
                ->wherePivot('role', 'hse_officer')
                ->exists();

            if (! $isHseAssigned) {
                return back()->with('error', 'You are not assigned to this site.');
            }
        }

        $validated = $request->validate([
            'termination_reason' => ['required', 'string', 'max:2000'],
        ]);

        $permit->update([
            'status' => PermitStatus::Terminated,
            'termination_reason' => $validated['termination_reason'],
            'terminated_by' => $user->id,
            'terminated_at' => now(),
        ]);

        return back()->with('success', 'Permit terminated permanently.');
    }
}
