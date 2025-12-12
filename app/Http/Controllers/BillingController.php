<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-billing');

        $organization = auth()->user()->organization;
        $currentPlan = $organization->getCurrentPlan();
        $subscription = $organization->subscription('default');

        $plans = config('subscriptions.plans');

        $usage = [
            'tasks' => [
                'current' => $organization->tasksCount(),
                'limit' => $organization->getTaskLimit(),
            ],
            'projects' => [
                'current' => $organization->projectsCount(),
            ],
        ];

        return view('billing.index', [
            'organization' => $organization,
            'currentPlan' => $currentPlan,
            'subscription' => $subscription,
            'plans' => $plans,
            'usage' => $usage,
        ]);
    }

    public function subscribe(Request $request)
    {
        Gate::authorize('manage-billing');

        $request->validate([
            'price_id' => 'required|string',
        ]);

        $organization = $request->user()->organization;

        try {
            return $organization->newSubscription('default', $request->price_id)
                ->checkout([
                    'success_url' => route('billing.success'),
                    'cancel_url' => route('billing.checkout-cancel'),
                ]);
        } catch (\Exception $e) {
            return redirect()
                ->route('billing.index')
                ->with('error', 'Unable to start checkout. Please try again.');
        }
    }

    public function portal(Request $request): RedirectResponse
    {
        Gate::authorize('manage-billing');

        return $request->user()->organization->redirectToBillingPortal(
            route('billing.index')
        );
    }

    public function cancel(Request $request): RedirectResponse
    {
        Gate::authorize('manage-billing');

        $organization = $request->user()->organization;
        $subscription = $organization->subscription('default');

        if ($subscription && ! $subscription->canceled()) {
            $subscription->cancel();

            return redirect()
                ->route('billing.index')
                ->with('success', 'Subscription cancelled. You will retain access until the end of your billing period.');
        }

        return redirect()
            ->route('billing.index')
            ->with('error', 'No active subscription to cancel.');
    }

    public function resume(Request $request): RedirectResponse
    {
        Gate::authorize('manage-billing');

        $organization = $request->user()->organization;
        $subscription = $organization->subscription('default');

        if ($subscription && $subscription->onGracePeriod()) {
            $subscription->resume();

            return redirect()
                ->route('billing.index')
                ->with('success', 'Subscription resumed successfully.');
        }

        return redirect()
            ->route('billing.index')
            ->with('error', 'No cancelled subscription to resume.');
    }
}
