<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'project_id',
        'company_id',
        'title',
        'description',
        'status',
        'assigned_to',
        'created_by',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reassignmentLogs()
    {
        return $this->hasMany(TaskReassignmentLog::class)->latest();
    }
}
