<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['title', 'description', 'deadline'];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role', 'assigned_at', 'removed_at');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    // Counts tasks by status and returns simple numbers used in views
    public function taskStats()
    {
        $tasks = $this->tasks;
        $total = $tasks->count();
        $done = $tasks->where('status', 'done')->count();

        $urgent = 0;
        foreach ($tasks as $task) {
            if ($task->status !== 'done' && $task->deadline) {
                $hoursLeft = now()->diffInHours($task->deadline, false);
                if ($hoursLeft >= 0 && $hoursLeft <= 48) {
                    $urgent++;
                }
            }
        }

        $pct = 0;
        if ($total > 0) {
            $pct = (int) round($done / $total * 100);
        }

        return [
            'total'    => $total,
            'done'     => $done,
            'progress' => $tasks->where('status', 'in_progress')->count(),
            'todo'     => $tasks->where('status', 'todo')->count(),
            'urgent'   => $urgent,
            'pct'      => $pct,
        ];
    }
}
