<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

    /**
     * Bonus — Mutator : stocke le titre avec la première lettre en majuscule.
     */
    protected function title(): Attribute
    {
        return Attribute::set(fn($value) => ucfirst($value));
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id', 'user_id')
            ->withPivot('role', 'assigned_at', 'removed_at');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
