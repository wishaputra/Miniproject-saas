<?php

namespace App\Livewire\ActivityLogs;

use App\Models\ActivityLog;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests, WithPagination;

    public function mount()
    {
        // Only admin can access this page
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function render()
    {
        // CompanyScope automatically applies to ActivityLog
        // We order by latest and eager load user and loggable models
        $logs = ActivityLog::with(['user', 'loggable'])
            ->latest()
            ->paginate(20);

        return view('livewire.activity-logs.index', [
            'logs' => $logs,
        ]);
    }
}
