<?php

namespace App\Http\Controllers;

use App\Enums\PermitStatus;
use App\Enums\Role;
use App\Models\DailyClosure;
use App\Models\DailyFollowUp;
use App\Models\WorkPermit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DailyOperationsController extends Controller
{
    public function firstFollowUp(Request $request, WorkPermit $permit): RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== Role::HseOfficer) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'Only HSE Officers can perform the first follow-up.');
        }

        if ($permit->status !== PermitStatus::Approved) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'This permit is not awaiting first follow-up.');
        }

        if (! $permit->hasWorkSupervisorAccepted()) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'You must accept the assignment first.');
        }

        if ($permit->hasFirstFollowUp()) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'First follow-up has already been completed.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DailyFollowUp::create([
            'work_permit_id' => $permit->id,
            'user_id' => $user->id,
            'follow_up_date' => today(),
            'notes' => $validated['notes'] ?? null,
            'is_first_follow_up' => true,
        ]);

        $permit->update(['status' => PermitStatus::Active]);

        return redirect()->route('permits.show', $permit)
            ->with('success', 'First follow-up completed. Permit is now active. HSE Officer can now perform follow-ups.');
    }

    public function recordFollowUp(Request $request, WorkPermit $permit): RedirectResponse
    {
        $user = $request->user();

        if (! in_array($user->role, [Role::HseOfficer, Role::WorkSupervisor])) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'Only HSE Officers and Work Supervisors can record follow-ups.');
        }

        if (! in_array($permit->status, [PermitStatus::Approved, PermitStatus::Active, PermitStatus::DailyClosed])) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'Follow-ups can only be recorded for approved permits.');
        }

        if ($user->role === Role::HseOfficer) {
            $isAssignedToSite = $permit->site->users()
                ->where('users.id', $user->id)
                ->wherePivot('role', 'hse_officer')
                ->exists();

            if (! $isAssignedToSite) {
                return redirect()->route('permits.show', $permit)
                    ->with('error', 'You are not assigned to this site as an HSE Officer.');
            }

            if (! $permit->hasSupervisorFollowUpToday()) {
                return redirect()->route('permits.show', $permit)
                    ->with('error', 'Work Supervisor must perform follow-up first before HSE Officer can follow up.');
            }
        }

        if ($user->role === Role::WorkSupervisor) {
            if ($permit->supervisor_id !== $user->id) {
                return redirect()->route('permits.show', $permit)
                    ->with('error', 'You are not the assigned supervisor for this permit.');
            }
        }

        $existingFollowUp = $permit->dailyFollowUps()
            ->where('user_id', $user->id)
            ->where('follow_up_date', today())
            ->exists();

        if ($existingFollowUp) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'You have already recorded a follow-up today.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DailyFollowUp::create([
            'work_permit_id' => $permit->id,
            'user_id' => $user->id,
            'follow_up_date' => today(),
            'notes' => $validated['notes'] ?? null,
            'is_first_follow_up' => false,
        ]);

        if ($permit->status === PermitStatus::DailyClosed) {
            if ($permit->hasSupervisorFollowUpToday() && $permit->hasHseFollowUpToday()) {
                $permit->update(['status' => PermitStatus::Active]);

                return redirect()->route('permits.show', $permit)
                    ->with('success', 'Follow-up recorded. Permit is now active again.');
            }
        }

        return redirect()->route('permits.show', $permit)
            ->with('success', 'Follow-up recorded successfully.');
    }

    public function closeDay(Request $request, WorkPermit $permit): RedirectResponse
    {
        $user = $request->user();

        if (! in_array($user->role, [Role::HseOfficer, Role::WorkSupervisor])) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'Only HSE Officers and Work Supervisors can close the day.');
        }

        if (! in_array($permit->status, [PermitStatus::Active, PermitStatus::DailyClosed])) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'This permit is not in an active state.');
        }

        if (! $permit->isWithinPermitPeriod()) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'Today is not within the permit period.');
        }

        $today = today();

        $existingClosure = $permit->dailyClosures()
            ->where('user_id', $user->id)
            ->where('closure_date', $today)
            ->exists();

        if ($existingClosure) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'You have already closed this day.');
        }

        if ($user->role === Role::WorkSupervisor) {
            if ($permit->supervisor_id !== $user->id) {
                return redirect()->route('permits.show', $permit)
                    ->with('error', 'You are not the assigned supervisor for this permit.');
            }

            if (! $permit->hasSupervisorFollowUpToday() || ! $permit->hasHseFollowUpToday()) {
                return redirect()->route('permits.show', $permit)
                    ->with('error', 'Both Work Supervisor and HSE Officer must complete follow-ups before closing.');
            }
        }

        if ($user->role === Role::HseOfficer) {
            $isAssignedToSite = $permit->site->users()
                ->where('users.id', $user->id)
                ->wherePivot('role', 'hse_officer')
                ->exists();

            if (! $isAssignedToSite) {
                return redirect()->route('permits.show', $permit)
                    ->with('error', 'You are not assigned to this site as an HSE Officer.');
            }

            if (! $permit->hasSupervisorClosureToday()) {
                return redirect()->route('permits.show', $permit)
                    ->with('error', 'Work Supervisor must close the day first before HSE Officer can close.');
            }
        }

        DailyClosure::create([
            'work_permit_id' => $permit->id,
            'user_id' => $user->id,
            'closure_date' => $today,
            'closed_at' => now(),
        ]);

        $supervisorClosed = $permit->dailyClosures()
            ->where('user_id', $permit->supervisor_id)
            ->where('closure_date', $today)
            ->exists();

        $hseOfficers = $permit->site->users()->wherePivot('role', 'hse_officer')->pluck('users.id');
        $hseClosed = $permit->dailyClosures()
            ->whereIn('user_id', $hseOfficers)
            ->where('closure_date', $today)
            ->exists();

        if ($supervisorClosed && $hseClosed) {
            if ($permit->isLastDay()) {
                $permit->update(['status' => PermitStatus::Expired]);
            } else {
                $permit->update(['status' => PermitStatus::DailyClosed]);
            }
        }

        return redirect()->route('permits.show', $permit)
            ->with('success', 'Day closed successfully.');
    }
}
