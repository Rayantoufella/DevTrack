<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'deadline'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id', 'user_id')
            ->withPivot('role', 'assigned_at', 'removed_at');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Statistiques des tâches du projet (nécessite le chargement préalable de la relation tasks).
     */
    public function taskStats(): array
    {
        $tasks = $this->tasks;

        $total = $tasks->count();
        $done  = $tasks->where('status', 'done')->count();

        return [
            'total'    => $total,
            'done'     => $done,
            'progress' => $tasks->where('status', 'in_progress')->count(),
            'todo'     => $tasks->where('status', 'todo')->count(),
            'urgent'   => $tasks->filter(fn ($t) =>
                $t->status !== 'done'
                && $t->deadline
                && now()->lte($t->deadline)
                && now()->diffInHours($t->deadline, false) <= 48
            )->count(),
            'pct'      => $total > 0 ? (int) round($done / $total * 100) : 0,
        ];
    }
}
