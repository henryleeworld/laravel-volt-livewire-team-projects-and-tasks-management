<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'user_id',
        'organization_id',
        'assigned_to_user_id',
    ];

    /**
     * Get the user that owns the task.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the organization that owns the task.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function assignedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'user_id', 'organization_id', 'assigned_to_user_id'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => __('Task has been :event_name', ['event_name' => __($eventName)]));
    }
}
