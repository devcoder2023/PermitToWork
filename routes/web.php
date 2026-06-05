<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DailyOperationsController;
use App\Http\Controllers\ObservationController;
use App\Http\Controllers\PermitTypeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SubContractorController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkPermitController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::get('/greeting', function () {
    return 'Hello World 99';
});

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::inertia('/dashboard', 'dashboard')->name('dashboard');

    Route::middleware('role:system_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('sub-contractors', SubContractorController::class);
        Route::resource('projects', ProjectController::class);
        Route::resource('projects.sites', SiteController::class)->shallow();
        Route::resource('users', UserController::class);
        Route::resource('permit-types', PermitTypeController::class)->only(['index', 'update']);
    });

    Route::middleware('role:execution_engineer')->group(function () {
        Route::resource('permits', WorkPermitController::class)->except(['index', 'show']);
    });

    Route::get('permits', [WorkPermitController::class, 'index'])->name('permits.index');
    Route::get('permits/{permit}', [WorkPermitController::class, 'show'])->name('permits.show');

    Route::middleware('role:site_manager')->group(function () {
        Route::post('permits/{permit}/approve/site-manager', [ApprovalController::class, 'approveSiteManager'])->name('permits.approve.site-manager');
        Route::post('permits/{permit}/reject/site-manager', [ApprovalController::class, 'rejectSiteManager'])->name('permits.reject.site-manager');
    });

    Route::middleware('role:permit_officer')->group(function () {
        Route::post('permits/{permit}/approve/permit-officer', [ApprovalController::class, 'approvePermitOfficer'])->name('permits.approve.permit-officer');
        Route::post('permits/{permit}/reject/permit-officer', [ApprovalController::class, 'rejectPermitOfficer'])->name('permits.reject.permit-officer');
    });

    Route::middleware('role:work_supervisor')->group(function () {
        Route::post('permits/{permit}/accept/work-supervisor', [ApprovalController::class, 'acceptWorkSupervisor'])->name('permits.accept.work-supervisor');
        Route::post('permits/{permit}/decline/work-supervisor', [ApprovalController::class, 'declineWorkSupervisor'])->name('permits.decline.work-supervisor');
    });

    Route::middleware('role:hse_officer')->group(function () {
        Route::post('permits/{permit}/first-follow-up', [DailyOperationsController::class, 'firstFollowUp'])->name('permits.first-follow-up');
    });

    Route::middleware('role:hse_officer,work_supervisor')->group(function () {
        Route::post('permits/{permit}/follow-up', [DailyOperationsController::class, 'recordFollowUp'])->name('permits.follow-up');
        Route::post('permits/{permit}/close-day', [DailyOperationsController::class, 'closeDay'])->name('permits.close-day');
    });

    Route::middleware('role:hse_officer,consultant')->group(function () {
        Route::post('permits/{permit}/observations', [ObservationController::class, 'store'])->name('permits.observations.store');
        Route::post('permits/{permit}/suspend', [ObservationController::class, 'suspend'])->name('permits.suspend');
        Route::post('permits/{permit}/terminate', [ObservationController::class, 'terminate'])->name('permits.terminate');
    });

    Route::post('observations/{observation}/resolve', [ObservationController::class, 'resolve'])->name('observations.resolve');

    Route::middleware('role:hse_officer')->group(function () {
        Route::post('observations/{observation}/accept-resolution', [ObservationController::class, 'acceptResolution'])->name('observations.accept-resolution');
        Route::post('observations/{observation}/reject-resolution', [ObservationController::class, 'rejectResolution'])->name('observations.reject-resolution');
        Route::post('permits/{permit}/approve-resume', [ObservationController::class, 'approveResume'])->name('permits.approve-resume');
        Route::post('permits/{permit}/reject-resume', [ObservationController::class, 'rejectResume'])->name('permits.reject-resume');
    });

    Route::middleware('role:execution_engineer,work_supervisor')->group(function () {
        Route::post('permits/{permit}/request-resume', [ObservationController::class, 'requestResume'])->name('permits.request-resume');
    });
});
