<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'deadline',
        'project_id',
        'user_id',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Use like: Task::urgent()->get();
    public function scopeUrgent($query)
    {
        return $query
            ->where('status', '!=', 'done')
            ->whereBetween('deadline', [now(), now()->addHours(48)]);
    }
}