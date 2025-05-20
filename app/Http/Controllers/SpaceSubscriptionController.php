<?php

namespace App\Http\Controllers;

use App\Models\Space;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Stripe\Exception\CardException;

class SpaceSubscriptionController extends Controller
{
    /**
     * Show the subscription form for a space.
     */
    public function create(string $spaceId): Response
    {
        $space = Space::findOrFail($spaceId);
        
        // Ensure only the owner can manage billing
        $this->authorize('manageBilling', $space);
        
        $memberCount = $space->users()->count();
        
        return Inertia::render('Subscriptions/Create', [
            'space' => $space,
            'member_count' => $memberCount,
            'intent' => Auth::user()->createSetupIntent(),
            'plans' => [
                [
                    'id' => 'price_monthly', // This would be your actual Stripe price ID
                    'name' => 'Monthly Plan',
                    'price' => '$5 per member / month',
                    'description' => 'Get access to all features on a monthly billing cycle.',
                ],
                [
                    'id' => 'price_yearly', // This would be your actual Stripe price ID
                    'name' => 'Annual Plan',
                    'price' => '$50 per member / year',
                    'description' => 'Get access to all features with a 16% discount.',
                ],
            ],
        ]);
    }

    /**
     * Start a new subscription for a space.
     */
    public function store(Request $request, string $spaceId): RedirectResponse
    {
        $space = Space::findOrFail($spaceId);
        
        // Ensure only the owner can manage billing
        $this->authorize('manageBilling', $space);
        
        $request->validate([
            'payment_method' => 'required',
            'plan' => 'required',
        ]);

        $user = Auth::user();
        $memberCount = $space->users()->count();

        try {
            // Create the subscription with the initial member count
            $user->newSubscription('default', $request->plan)
                 ->quantity($memberCount)
                 ->create($request->payment_method);
                 
            // Store the space ID in the subscription metadata
            $user->subscription('default')->syncMetadata([
                'space_id' => $space->id
            ]);
            
            // Update the space data
            $space->update([
                'data' => array_merge($space->data ?? [], [
                    'plan' => $request->plan,
                ]),
            ]);

            return redirect()->route('spaces.show', $space->id)
                ->with('success', 'Subscription created successfully.');
        } catch (IncompletePayment $exception) {
            return redirect()->route('cashier.payment', [$exception->payment->id])
                ->with('error', 'The payment was not completed. Please complete your payment.');
        } catch (CardException $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    /**
     * Show the subscription details for a space.
     */
    public function show(string $spaceId): Response
    {
        $space = Space::findOrFail($spaceId);
        
        // Ensure the user has access to view billing
        $this->authorize('manageBilling', $space);
        
        $user = User::find($space->owner_id);
        $subscription = $user->subscription('default');
        
        // Check if the subscription exists and belongs to this space
        if (!$subscription || $subscription->asStripeSubscription()->metadata->space_id !== $space->id) {
            return Inertia::render('Subscriptions/None', [
                'space' => $space,
                'member_count' => $space->users()->count(),
            ]);
        }
        
        return Inertia::render('Subscriptions/Show', [
            'space' => $space,
            'subscription' => [
                'name' => $subscription->stripe_price,
                'status' => $subscription->stripe_status,
                'quantity' => $subscription->quantity,
                'ends_at' => $subscription->ends_at,
                'created_at' => $subscription->created_at,
                'on_trial' => $subscription->onTrial(),
                'canceled' => $subscription->canceled(),
            ],
            'payment_method' => $user->defaultPaymentMethod(),
            'member_count' => $space->users()->count(),
        ]);
    }

    /**
     * Update the subscription (change plan or quantity).
     */
    public function update(Request $request, string $spaceId): RedirectResponse
    {
        $space = Space::findOrFail($spaceId);
        
        // Ensure only the owner can manage billing
        $this->authorize('manageBilling', $space);
        
        $request->validate([
            'plan' => 'nullable|string',
        ]);

        $user = User::find($space->owner_id);
        $subscription = $user->subscription('default');
        
        // Check if the subscription exists and belongs to this space
        if (!$subscription || $subscription->asStripeSubscription()->metadata->space_id !== $space->id) {
            return back()->with('error', 'No subscription found for this space.');
        }
        
        // If changing plan
        if ($request->has('plan') && $request->plan !== $subscription->stripe_price) {
            $subscription->swap($request->plan);
            
            // Update the space data
            $space->update([
                'data' => array_merge($space->data ?? [], [
                    'plan' => $request->plan,
                ]),
            ]);
        }
        
        // Sync member count with subscription quantity
        $space->syncMemberCount();

        return back()->with('success', 'Subscription updated successfully.');
    }

    /**
     * Cancel the subscription.
     */
    public function destroy(string $spaceId): RedirectResponse
    {
        $space = Space::findOrFail($spaceId);
        
        // Ensure only the owner can manage billing
        $this->authorize('manageBilling', $space);
        
        $user = User::find($space->owner_id);
        $subscription = $user->subscription('default');
        
        // Check if the subscription exists and belongs to this space
        if (!$subscription || $subscription->asStripeSubscription()->metadata->space_id !== $space->id) {
            return back()->with('error', 'No subscription found for this space.');
        }
        
        // Cancel at period end
        $subscription->cancel();

        return back()->with('success', 'Your subscription has been cancelled and will end at the current billing period.');
    }

    /**
     * Resume a cancelled subscription.
     */
    public function resume(string $spaceId): RedirectResponse
    {
        $space = Space::findOrFail($spaceId);
        
        // Ensure only the owner can manage billing
        $this->authorize('manageBilling', $space);
        
        $user = User::find($space->owner_id);
        $subscription = $user->subscription('default');
        
        // Check if the subscription exists, is canceled, and belongs to this space
        if (!$subscription || !$subscription->canceled() || $subscription->asStripeSubscription()->metadata->space_id !== $space->id) {
            return back()->with('error', 'Cannot resume subscription.');
        }
        
        // Resume the subscription
        $subscription->resume();

        return back()->with('success', 'Your subscription has been resumed.');
    }

    /**
     * Update the payment method.
     */
    public function updatePaymentMethod(Request $request, string $spaceId): RedirectResponse
    {
        $space = Space::findOrFail($spaceId);
        
        // Ensure only the owner can manage billing
        $this->authorize('manageBilling', $space);
        
        $request->validate([
            'payment_method' => 'required',
        ]);

        $user = User::find($space->owner_id);
        
        // Update the payment method
        $user->updateDefaultPaymentMethod($request->payment_method);

        return back()->with('success', 'Payment method updated successfully.');
    }

    /**
     * Show the billing portal for a space.
     */
    public function billingPortal(string $spaceId): RedirectResponse
    {
        $space = Space::findOrFail($spaceId);
        
        // Ensure only the owner can manage billing
        $this->authorize('manageBilling', $space);
        
        $user = User::find($space->owner_id);
        
        // Redirect to the Stripe customer portal
        return $user->redirectToBillingPortal(route('spaces.subscriptions.show', $space->id));
    }
}
