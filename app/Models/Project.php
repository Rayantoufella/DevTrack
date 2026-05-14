<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    // Columns we allow to be filled from a form
    protected $fillable = ['title', 'description', 'deadline'];

    // A project has many users (through the project_user table)
    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role', 'assigned_at', 'removed_at');
    }

    // A project has many tasks
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    // Counts tasks by status and returns simple numbers used in views
    public function taskStats()
    {
        $tasks = $this->tasks;

        // Count tasks by their status
        $total    = $tasks->count();
        $done     = $tasks->where('status', 'done')->count();
        $progress = $tasks->where('status', 'in_progress')->count();
        $todo     = $tasks->where('status', 'todo')->count();

        // Count tasks that are not done and have a deadline in the next 48 hours
        $urgent = 0;
        foreach ($tasks as $task) {
            if ($task->status === 'done' || ! $task->deadline) {
                continue;
            }

            $hoursLeft = now()->diffInHours($task->deadline, false);
            if ($hoursLeft >= 0 && $hoursLeft <= 48) {
                $urgent++;
            }
        }

        // Percentage of done tasks (0 if there are no tasks)
        $pct = 0;
        if ($total > 0) {
            $pct = (int) round($done / $total * 100);
        }

        return [
            'total'    => $total,
            'done'     => $done,
            'progress' => $progress,
            'todo'     => $todo,
            'urgent'   => $urgent,
            'pct'      => $pct,
        ];
    }
}
