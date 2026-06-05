<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalStage;
use App\Enums\PermitStatus;
use App\Enums\Role;
use App\Models\ApprovalRecord;
use App\Models\WorkPermit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function approveSiteManager(Request $request, WorkPermit $permit): RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== Role::SiteManager) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'Only Site Managers can approve at this stage.');
        }

        if ($permit->status !== PermitStatus::New) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'This permit is not awaiting Site Manager approval.');
        }

        $isAssignedToSite = $permit->site->users()
            ->where('users.id', $user->id)
            ->exists();

        if (! $isAssignedToSite) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'You are not assigned to this site.');
        }

        ApprovalRecord::create([
            'work_permit_id' => $permit->id,
            'user_id' => $user->id,
            'stage' => ApprovalStage::SiteManager,
            'decision' => ApprovalDecision::Approved,
        ]);

        $permit->update(['status' => PermitStatus::UnderReview]);

        return redirect()->route('permits.show', $permit)
            ->with('success', 'Permit approved. Forwarded to Permit Officer.');
    }

    public function rejectSiteManager(Request $request, WorkPermit $permit): RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== Role::SiteManager) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'Only Site Managers can reject at this stage.');
        }

        if ($permit->status !== PermitStatus::New) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'This permit is not awaiting Site Manager approval.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $isAssignedToSite = $permit->site->users()
            ->where('users.id', $user->id)
            ->exists();

        if (! $isAssignedToSite) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'You are not assigned to this site.');
        }

        ApprovalRecord::create([
            'work_permit_id' => $permit->id,
            'user_id' => $user->id,
            'stage' => ApprovalStage::SiteManager,
            'decision' => ApprovalDecision::Rejected,
            'reason' => $validated['reason'],
        ]);

        $permit->update([
            'status' => PermitStatus::Rejected,
            'rejection_reason' => $validated['reason'],
        ]);

        return redirect()->route('permits.show', $permit)
            ->with('success', 'Permit rejected. Returned to engineer.');
    }

    public function approvePermitOfficer(Request $request, WorkPermit $permit): RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== Role::PermitOfficer) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'Only Permit Officers can approve at this stage.');
        }

        if ($permit->status !== PermitStatus::UnderReview) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'This permit is not awaiting Permit Officer approval.');
        }

        if ($permit->project_id !== $user->project_id) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'You are not assigned to this project.');
        }

        ApprovalRecord::create([
            'work_permit_id' => $permit->id,
            'user_id' => $user->id,
            'stage' => ApprovalStage::PermitOfficer,
            'decision' => ApprovalDecision::Approved,
        ]);

        $permit->update(['status' => PermitStatus::Approved]);

        return redirect()->route('permits.show', $permit)
            ->with('success', 'Permit approved. Awaiting Work Supervisor and HSE Officer.');
    }

    public function rejectPermitOfficer(Request $request, WorkPermit $permit): RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== Role::PermitOfficer) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'Only Permit Officers can reject at this stage.');
        }

        if ($permit->status !== PermitStatus::UnderReview) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'This permit is not awaiting Permit Officer approval.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        if ($permit->project_id !== $user->project_id) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'You are not assigned to this project.');
        }

        ApprovalRecord::create([
            'work_permit_id' => $permit->id,
            'user_id' => $user->id,
            'stage' => ApprovalStage::PermitOfficer,
            'decision' => ApprovalDecision::Rejected,
            'reason' => $validated['reason'],
        ]);

        $permit->update([
            'status' => PermitStatus::Rejected,
            'rejection_reason' => $validated['reason'],
        ]);

        return redirect()->route('permits.show', $permit)
            ->with('success', 'Permit rejected. Returned to engineer.');
    }

    public function acceptWorkSupervisor(Request $request, WorkPermit $permit): RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== Role::WorkSupervisor) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'Only Work Supervisors can accept at this stage.');
        }

        if ($permit->status !== PermitStatus::Approved) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'This permit is not awaiting Work Supervisor acceptance.');
        }

        if ($permit->supervisor_id !== $user->id) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'You are not the assigned supervisor for this permit.');
        }

        ApprovalRecord::create([
            'work_permit_id' => $permit->id,
            'user_id' => $user->id,
            'stage' => ApprovalStage::WorkSupervisor,
            'decision' => ApprovalDecision::Approved,
        ]);

        return redirect()->route('permits.show', $permit)
            ->with('success', 'Work accepted. Awaiting HSE Officer first follow-up.');
    }

    public function declineWorkSupervisor(Request $request, WorkPermit $permit): RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== Role::WorkSupervisor) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'Only Work Supervisors can decline at this stage.');
        }

        if ($permit->status !== PermitStatus::Approved) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'This permit is not awaiting Work Supervisor acceptance.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        if ($permit->supervisor_id !== $user->id) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'You are not the assigned supervisor for this permit.');
        }

        ApprovalRecord::create([
            'work_permit_id' => $permit->id,
            'user_id' => $user->id,
            'stage' => ApprovalStage::WorkSupervisor,
            'decision' => ApprovalDecision::Rejected,
            'reason' => $validated['reason'],
        ]);

        $permit->update([
            'status' => PermitStatus::Rejected,
            'rejection_reason' => 'Work Supervisor declined: '.$validated['reason'],
        ]);

        return redirect()->route('permits.show', $permit)
            ->with('success', 'Work declined. Returned to engineer to select a different supervisor.');
    }
}
