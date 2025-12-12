<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Laravel\Cashier\Subscription;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
        ];
    }

    public function withProSubscription(): static
    {
        return $this->afterCreating(function ($organization) {
            Subscription::create([
                'user_id' => $organization->id,
                'type' => 'default',
                'stripe_id' => 'sub_'.fake()->uuid(),
                'stripe_status' => 'active',
                'stripe_price' => config('subscriptions.plans.pro.prices.monthly'),
                'quantity' => 1,
            ]);
        });
    }
}
