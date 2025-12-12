<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Subscription;

class Organization extends Model
{
    use Billable;

    /** @use HasFactory<\Database\Factories\OrganizationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
        ];
    }

    /**
     * Get the projects for the organization.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get the subscriptions for the organization.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'user_id');
    }

    /**
     * Get the tasks for the organization.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the users for the organization.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getCurrentPlan(): string
    {
        if ($this->subscribed('default')) {
            $subscription = $this->subscription('default');
            $priceId = $subscription->stripe_price;

            return match ($priceId) {
                config('subscriptions.plans.pro.prices.monthly'),
                config('subscriptions.plans.pro.prices.yearly') => 'pro',
                config('subscriptions.plans.ultimate.prices.monthly'),
                config('subscriptions.plans.ultimate.prices.yearly') => 'ultimate',
                default => 'free',
            };
        }

        return 'free';
    }

    public function tasksCount(): int
    {
        return $this->tasks()->count();
    }

    public function projectsCount(): int
    {
        return $this->projects()->count();
    }

    public function getTaskLimit(): ?int
    {
        $plan = $this->getCurrentPlan();

        return config("subscriptions.plans.{$plan}.task_limit");
    }

    public function canCreateTask(): bool
    {
        $limit = $this->getTaskLimit();

        if ($limit === null) {
            return true;
        }

        return $this->tasksCount() < $limit;
    }

    public function canAccessProjects(): bool
    {
        $plan = $this->getCurrentPlan();

        return config("subscriptions.plans.{$plan}.projects_enabled", false);
    }
}
