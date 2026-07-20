<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskReassignmentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'admin_id',
        'from_user_id',
        'to_user_id',
        'note',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
