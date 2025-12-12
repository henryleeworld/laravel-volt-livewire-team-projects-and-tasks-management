<x-layouts.app :title="__('Billing')">
    <div class="p-6 space-y-6">
        <flux:heading size="xl">{{ __('Billing') }}</flux:heading>
        <flux:subheading>{{ __('Manage your subscription and billing') }}</flux:subheading>

        @if (session('success'))
            <flux:callout variant="success">
                {{ session('success') }}
            </flux:callout>
        @endif

        @if (session('error'))
            <flux:callout variant="danger">
                {{ session('error') }}
            </flux:callout>
        @endif

        @if (session('info'))
            <flux:callout variant="info">
                {{ session('info') }}
            </flux:callout>
        @endif

        <div class="grid gap-6 md:grid-cols-2">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Current Plan') }}</flux:heading>

                <div class="space-y-3">
                    <div>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Plan') }}</flux:text>
                        <flux:heading size="base" class="capitalize">{{ $plans[$currentPlan]['name'] }}</flux:heading>
                    </div>

                    @if ($subscription && $subscription->active())
                        <div>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</flux:text>
                            <flux:badge variant="success">{{ __('Active') }}</flux:badge>
                        </div>

                        @if ($subscription->onGracePeriod())
                            <div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Cancels On') }}</flux:text>
                                <flux:text>{{ $subscription->ends_at->format('M d, Y') }}</flux:text>
                            </div>
                        @else
                            <div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Renews On') }}</flux:text>
                                <flux:text>{{ $subscription->asStripeSubscription()->current_period_end ? \Carbon\Carbon::createFromTimestamp($subscription->asStripeSubscription()->current_period_end)->format('M d, Y') : 'N/A' }}</flux:text>
                            </div>
                        @endif

                        <div class="flex gap-2 pt-3">
                            @if ($subscription->onGracePeriod())
                                <form action="{{ route('billing.resume') }}" method="POST">
                                    @csrf
                                    <flux:button variant="primary" type="submit">{{ __('Resume Subscription') }}</flux:button>
                                </form>
                            @else
                                <form action="{{ route('billing.cancel') }}" method="POST">
                                    @csrf
                                    <flux:button variant="danger" type="submit" onclick="return confirm('Are you sure you want to cancel your subscription?')">{{ __('Cancel Subscription') }}</flux:button>
                                </form>
                            @endif

                            <form action="{{ route('billing.portal') }}" method="POST">
                                @csrf
                                <flux:button variant="ghost" type="submit">{{ __('Manage Billing') }}</flux:button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Usage') }}</flux:heading>

                <div class="space-y-3">
                    <div>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Tasks') }}</flux:text>
                        <div class="flex items-center gap-2">
                            <flux:heading size="base">
                                {{ $usage['tasks']['current'] }}{{ $usage['tasks']['limit'] ? " / {$usage['tasks']['limit']}" : '' }}
                            </flux:heading>
                            @if ($usage['tasks']['limit'] && $usage['tasks']['current'] >= $usage['tasks']['limit'])
                                <flux:badge variant="danger">{{ __('Limit Reached') }}</flux:badge>
                            @elseif ($usage['tasks']['limit'] && $usage['tasks']['current'] / $usage['tasks']['limit'] >= 0.8)
                                <flux:badge variant="warning">{{ __('Near Limit') }}</flux:badge>
                            @endif
                        </div>
                    </div>

                    <div>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Projects') }}</flux:text>
                        <div class="flex items-center gap-2">
                            <flux:heading size="base">{{ $usage['projects']['current'] }}</flux:heading>
                            @if (!$plans[$currentPlan]['projects_enabled'])
                                <flux:badge variant="warning">{{ __('Not Available') }}</flux:badge>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="mb-8 text-center">
                <flux:heading size="lg" class="mb-6">{{ __('Available Plans') }}</flux:heading>

                <div class="inline-flex items-center gap-3 rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800">
                    <button
                        type="button"
                        onclick="document.querySelectorAll('[data-billing-monthly]').forEach(el => el.classList.remove('hidden')); document.querySelectorAll('[data-billing-yearly]').forEach(el => el.classList.add('hidden')); this.classList.add('bg-white', 'dark:bg-zinc-900', 'shadow-sm'); this.nextElementSibling.classList.remove('bg-white', 'dark:bg-zinc-900', 'shadow-sm');"
                        class="rounded-md px-4 py-2 text-sm font-medium transition-all bg-white dark:bg-zinc-900 shadow-sm"
                    >
                        {{ __('Monthly') }}
                    </button>
                    <button
                        type="button"
                        onclick="document.querySelectorAll('[data-billing-yearly]').forEach(el => el.classList.remove('hidden')); document.querySelectorAll('[data-billing-monthly]').forEach(el => el.classList.add('hidden')); this.classList.add('bg-white', 'dark:bg-zinc-900', 'shadow-sm'); this.previousElementSibling.classList.remove('bg-white', 'dark:bg-zinc-900', 'shadow-sm');"
                        class="rounded-md px-4 py-2 text-sm font-medium transition-all"
                    >
                        {{ __('Yearly') }}
                        <span class="ml-1 text-xs text-green-600 dark:text-green-400">{{ __('Save Money') }}</span>
                    </button>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <div class="rounded-lg border-2 {{ $currentPlan === 'free' ? 'border-blue-500' : 'border-zinc-200 dark:border-zinc-700' }} bg-white p-6 dark:bg-zinc-900">
                    <div class="mb-4">
                        <flux:heading size="lg">{{ __('Free') }}</flux:heading>
                        @if ($currentPlan === 'free')
                            <flux:badge variant="primary" class="mt-2">{{ __('Current Plan') }}</flux:badge>
                        @endif
                    </div>

                    <div class="mb-6 space-y-3">
                        <div>
                            <flux:heading size="xl">$0</flux:heading>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Forever') }}</flux:text>
                        </div>

                        <div class="space-y-2 pt-4">
                            <div class="flex items-center gap-2">
                                <flux:icon.check class="size-5 text-green-500" />
                                <flux:text>{{ __('10 tasks') }}</flux:text>
                            </div>
                            <div class="flex items-center gap-2">
                                <flux:icon.x-mark class="size-5 text-red-500" />
                                <flux:text class="text-zinc-500 dark:text-zinc-400">{{ __('No projects') }}</flux:text>
                            </div>
                        </div>
                    </div>

                    @if ($currentPlan !== 'free')
                        <flux:button variant="ghost" class="w-full" disabled>
                            {{ __('Downgrade requires cancellation') }}
                        </flux:button>
                    @endif
                </div>

                @foreach (['pro', 'ultimate'] as $planKey)
                    @php
                        $plan = $plans[$planKey];
                    @endphp

                    <div class="rounded-lg border-2 {{ $currentPlan === $planKey ? 'border-blue-500' : 'border-zinc-200 dark:border-zinc-700' }} bg-white p-6 dark:bg-zinc-900">
                        <div class="mb-4">
                            <flux:heading size="lg" class="capitalize">{{ $plan['name'] }}</flux:heading>
                            @if ($currentPlan === $planKey)
                                <flux:badge variant="primary" class="mt-2">{{ __('Current Plan') }}</flux:badge>
                            @endif
                        </div>

                        <div class="mb-6 space-y-3">
                            <div data-billing-monthly>
                                <div class="flex items-baseline gap-2">
                                    <flux:heading size="xl">${{ number_format($plan['price_amounts']['monthly'], 0) }}</flux:heading>
                                    <flux:text class="text-zinc-500 dark:text-zinc-400">/{{ __('month') }}</flux:text>
                                </div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Billed monthly') }}</flux:text>
                            </div>

                            <div data-billing-yearly class="hidden">
                                <div class="flex items-baseline gap-2">
                                    <flux:heading size="xl">${{ number_format($plan['price_amounts']['yearly'], 0) }}</flux:heading>
                                    <flux:text class="text-zinc-500 dark:text-zinc-400">/{{ __('month') }}</flux:text>
                                </div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Billed annually') }} (${{ number_format($plan['price_amounts']['yearly'] * 12, 0) }}/{{ __('year') }})</flux:text>
                            </div>

                            <div class="space-y-2 pt-4">
                                <div class="flex items-center gap-2">
                                    <flux:icon.check class="size-5 text-green-500" />
                                    <flux:text>{{ $plan['task_limit'] ? number_format($plan['task_limit']) . __(' tasks') : __('Unlimited tasks') }}</flux:text>
                                </div>
                                <div class="flex items-center gap-2">
                                    <flux:icon.check class="size-5 text-green-500" />
                                    <flux:text>{{ __('Projects access') }}</flux:text>
                                </div>
                            </div>
                        </div>

                        @if ($currentPlan !== $planKey)
                            <form action="{{ route('billing.subscribe') }}" method="POST">
                                @csrf
                                <input type="hidden" name="price_id" value="{{ $plan['prices']['monthly'] }}" data-billing-monthly>
                                <input type="hidden" name="price_id" value="{{ $plan['prices']['yearly'] }}" data-billing-yearly class="hidden">
                                <flux:button variant="primary" type="submit" class="w-full">
                                    {{ __('Subscribe') }}
                                </flux:button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.app>
