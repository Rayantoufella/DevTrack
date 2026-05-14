<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function projects()
    {
        return $this->belongsToMany(Project::class)
            ->withPivot('role', 'assigned_at', 'removed_at');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function isLead()
    {
        return $this->role === 'lead';
    }
}
