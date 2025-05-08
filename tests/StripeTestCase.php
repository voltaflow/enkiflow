<?php

namespace Tests;

use App\Models\Space;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Laravel\Cashier\Cashier;
use Stripe\Stripe;

abstract class StripeTestCase extends TestCase
{
    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Configure Stripe to use mock server
        Config::set('cashier.secret', 'sk_test_12345');
        Config::set('cashier.key', 'pk_test_12345');
        
        // If running in CI with stripe-mock, use the CI URL, otherwise use the default
        if (env('STRIPE_BASE')) {
            $baseUrl = env('STRIPE_BASE', 'http://localhost:12111');
            Config::set('cashier.api_base', $baseUrl);
            Stripe::setApiBase($baseUrl);
        }
    }

    /**
     * Generate a webhook payload for testing.
     *
     * @param string $type The event type
     * @param array $data The object data
     * @return array
     */
    protected function generateWebhookPayload(string $type, array $data): array
    {
        return [
            'id' => 'evt_' . md5(uniqid()),
            'object' => 'event',
            'api_version' => Cashier::STRIPE_VERSION,
            'created' => time(),
            'data' => [
                'object' => $data,
            ],
            'livemode' => false,
            'pending_webhooks' => 0,
            'request' => [
                'id' => 'req_' . md5(uniqid()),
                'idempotency_key' => md5(uniqid()),
            ],
            'type' => $type,
        ];
    }

    /**
     * Create a user with Stripe customer ID.
     *
     * @param array $attributes
     * @return \App\Models\User
     */
    protected function createUserWithStripeId(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'stripe_id' => 'cus_' . md5(uniqid()),
        ], $attributes));
    }

    /**
     * Create a space with an owner and Stripe subscription data.
     *
     * @param array $attributes
     * @param User|null $owner
     * @return \App\Models\Space
     */
    protected function createSpaceWithSubscription(array $attributes = [], ?User $owner = null): Space
    {
        $owner = $owner ?? $this->createUserWithStripeId();
        
        $space = Space::create(array_merge([
            'id' => 'space-' . md5(uniqid()),
            'name' => 'Test Space',
            'owner_id' => $owner->id,
            'data' => [
                'plan' => 'price_monthly',
                'subscription_id' => 'sub_' . md5(uniqid()),
                'subscription_status' => 'active',
                'current_period_end' => now()->addMonth()->toDateTimeString(),
            ],
        ], $attributes));

        // Add owner as member
        $space->users()->attach($owner->id, ['role' => 'admin']);
        
        return $space;
    }
}
