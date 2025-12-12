<?php

return [
    'plans' => [
        'free' => [
            'name' => 'Free',
            'task_limit' => 10,
            'projects_enabled' => false,
        ],
        'pro' => [
            'name' => 'Pro',
            'task_limit' => 100,
            'projects_enabled' => true,
            'prices' => [
                'monthly' => env('STRIPE_PRO_MONTHLY_PRICE_ID'),
                'yearly' => env('STRIPE_PRO_YEARLY_PRICE_ID'),
            ],
            'price_amounts' => [
                'monthly' => 19.00,
                'yearly' => 14.00,
            ],
        ],
        'ultimate' => [
            'name' => 'Ultimate',
            'task_limit' => null,
            'projects_enabled' => true,
            'prices' => [
                'monthly' => env('STRIPE_ULTIMATE_MONTHLY_PRICE_ID'),
                'yearly' => env('STRIPE_ULTIMATE_YEARLY_PRICE_ID'),
            ],
            'price_amounts' => [
                'monthly' => 59.00,
                'yearly' => 49.00,
            ],
        ],
    ],
];
