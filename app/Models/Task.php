<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    /**
     * US13 — Accessor : statut formaté (utilisé par TaskResource).
     * Accessible via $task->status_label sans toucher à la valeur brute.
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn () => match ($this->status) {
            'todo'        => 'À faire',
            'in_progress' => 'En cours',
            'done'        => 'Terminé',
            default       => $this->status,
        });
    }

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'deadline',
        'project_id',
        'user_id'
    ];


    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Bonus — Local scope : tâches dont la deadline est dans moins de 48h
     * et dont le statut n'est pas "completed".
     */
    public function scopeUrgent(Builder $query): Builder
    {
        return $query
            ->where('status', '!=', 'done')
            ->whereBetween('deadline', [now(), now()->addHours(48)]);
    }
}