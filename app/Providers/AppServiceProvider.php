<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\Intern;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Shared with layouts.app (topbar notification dot) for every
        // authenticated dashboard page — pending intern registrations or
        // pending attendance approvals, scoped the same way the API scopes
        // spv_mentor (their mentees only).
        View::composer('layouts.app', function ($view) {
            $admin = Auth::guard('web')->user();

            if (! $admin) {
                $view->with('hasPendingNotifications', false);

                return;
            }

            $internQuery = Intern::query()->where('status', 'pending');
            $attendanceQuery = Attendance::query()->where('approval_status', 'pending');

            if ($admin->role === 'spv_mentor') {
                $internQuery->where('mentor_id', $admin->id);
                $attendanceQuery->whereHas('intern', fn ($q) => $q->where('mentor_id', $admin->id));
            }

            $view->with('hasPendingNotifications', $internQuery->exists() || $attendanceQuery->exists());
        });
    }
}
