<?php

namespace App\Http\Controllers;

use App\Models\Space;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;

class StripeWebhookController extends CashierWebhookController
{
    /**
     * Handle customer subscription updated.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleCustomerSubscriptionUpdated(array $payload)
    {
        $subscription = $payload['data']['object'];
        $user = $this->getUserByStripeId($subscription['customer']);

        if ($user) {
            // Update the space if subscription has space_id in metadata
            $this->updateSpaceSubscriptionData($subscription, $user);
        }

        return $this->successMethod();
    }

    /**
     * Handle customer subscription deleted.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleCustomerSubscriptionDeleted(array $payload)
    {
        $subscription = $payload['data']['object'];
        $user = $this->getUserByStripeId($subscription['customer']);

        if ($user) {
            // Update the space if subscription has space_id in metadata
            $this->updateSpaceSubscriptionData($subscription, $user);
        }

        return $this->successMethod();
    }

    /**
     * Handle customer deleted.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleCustomerDeleted(array $payload)
    {
        $stripeCustomer = $payload['data']['object'];
        $user = $this->getUserByStripeId($stripeCustomer['id']);

        if ($user) {
            // Mark spaces as inactive or handle as needed
            $spaces = $user->ownedSpaces;
            foreach ($spaces as $space) {
                $space->update([
                    'data' => array_merge($space->data ?? [], [
                        'subscription_status' => 'inactive',
                    ]),
                ]);
            }
        }

        return $this->successMethod();
    }

    /**
     * Handle invoice payment succeeded.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleInvoicePaymentSucceeded(array $payload)
    {
        $invoice = $payload['data']['object'];
        $user = $this->getUserByStripeId($invoice['customer']);

        if ($user && isset($invoice['subscription'])) {
            // Fetch the subscription details to get metadata
            $stripeSubscription = \Stripe\Subscription::retrieve($invoice['subscription']);

            if (isset($stripeSubscription->metadata->space_id)) {
                $spaceId = $stripeSubscription->metadata->space_id;
                $space = Space::find($spaceId);

                if ($space) {
                    // Update space data
                    $space->update([
                        'data' => array_merge($space->data ?? [], [
                            'subscription_status' => 'active',
                            'last_payment_date' => now()->toDateTimeString(),
                        ]),
                    ]);
                }
            }
        }

        return $this->successMethod();
    }

    /**
     * Handle invoice payment failed.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleInvoicePaymentFailed(array $payload)
    {
        $invoice = $payload['data']['object'];
        $user = $this->getUserByStripeId($invoice['customer']);

        if ($user && isset($invoice['subscription'])) {
            // Fetch the subscription details to get metadata
            $stripeSubscription = \Stripe\Subscription::retrieve($invoice['subscription']);

            if (isset($stripeSubscription->metadata->space_id)) {
                $spaceId = $stripeSubscription->metadata->space_id;
                $space = Space::find($spaceId);

                if ($space) {
                    // Update space data to reflect payment failure
                    $space->update([
                        'data' => array_merge($space->data ?? [], [
                            'subscription_status' => 'past_due',
                            'payment_failed_date' => now()->toDateTimeString(),
                        ]),
                    ]);
                }
            }
        }

        return $this->successMethod();
    }

    /**
     * Update space data based on subscription changes.
     *
     * @param  array  $subscription
     * @return void
     */
    protected function updateSpaceSubscriptionData($subscription, User $user)
    {
        try {
            // If subscription has space_id in metadata, update the space
            if (isset($subscription['metadata']['space_id'])) {
                $spaceId = $subscription['metadata']['space_id'];
                $space = Space::find($spaceId);

                if ($space && $space->owner_id === $user->id) {
                    // Update subscription data in the space
                    $space->update([
                        'data' => array_merge($space->data ?? [], [
                            'subscription_id' => $subscription['id'],
                            'subscription_status' => $subscription['status'],
                            'subscription_plan' => $subscription['items']['data'][0]['price']['id'] ?? null,
                            'current_period_end' => date('Y-m-d H:i:s', $subscription['current_period_end']),
                        ]),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error updating space subscription data: '.$e->getMessage(), [
                'subscription' => $subscription['id'],
                'user_id' => $user->id,
            ]);
        }
    }

    /**
     * Get the user by Stripe ID.
     *
     * @param  string  $stripeId
     * @return \App\Models\User|null
     */
    protected function getUserByStripeId($stripeId)
    {
        return User::where('stripe_id', $stripeId)->first();
    }
}
