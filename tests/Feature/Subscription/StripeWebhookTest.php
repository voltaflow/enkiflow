<?php

namespace Tests\Feature\Subscription;

use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Tests\StripeTestCase;

class StripeWebhookTest extends StripeTestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable CSRF verification for Stripe webhooks
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }
    
    /** @test */
    public function it_handles_customer_subscription_updated()
    {
        // Create a user with Stripe ID
        $user = $this->createUserWithStripeId([
            'stripe_id' => 'cus_test123',
        ]);
        
        // Create a space owned by the user
        $space = Space::create([
            'id' => 'test-space',
            'name' => 'Test Space',
            'owner_id' => $user->id,
            'data' => [],
        ]);
        
        // Generate webhook payload for subscription updated event
        $payload = $this->generateWebhookPayload('customer.subscription.updated', [
            'id' => 'sub_test123',
            'customer' => 'cus_test123',
            'status' => 'active',
            'items' => [
                'data' => [
                    [
                        'price' => [
                            'id' => 'price_monthly'
                        ]
                    ]
                ]
            ],
            'metadata' => [
                'space_id' => $space->id
            ],
            'current_period_end' => time() + 86400,
        ]);
        
        // Post the webhook
        $response = $this->postJson('/stripe/webhook', $payload);
        
        // Check response
        $response->assertOk();
        
        // Refresh space from database
        $space->refresh();
        
        // Verify the space was properly updated with subscription info
        $this->assertArrayHasKey('subscription_id', $space->data);
        $this->assertEquals('sub_test123', $space->data['subscription_id']);
        $this->assertEquals('active', $space->data['subscription_status']);
    }
    
    /** @test */
    public function it_handles_customer_subscription_deleted()
    {
        // Create a user with Stripe ID
        $user = $this->createUserWithStripeId([
            'stripe_id' => 'cus_test123',
        ]);
        
        // Create a space with active subscription
        $space = Space::create([
            'id' => 'test-space',
            'name' => 'Test Space',
            'owner_id' => $user->id,
            'data' => [
                'subscription_id' => 'sub_test123',
                'subscription_status' => 'active',
                'plan' => 'price_monthly',
            ],
        ]);
        
        // Generate webhook payload for subscription deleted event
        $payload = $this->generateWebhookPayload('customer.subscription.deleted', [
            'id' => 'sub_test123',
            'customer' => 'cus_test123',
            'status' => 'canceled',
            'metadata' => [
                'space_id' => $space->id
            ],
        ]);
        
        // Post the webhook
        $response = $this->postJson('/stripe/webhook', $payload);
        
        // Check response
        $response->assertOk();
        
        // Refresh space from database
        $space->refresh();
        
        // Verify the space was properly updated with subscription info
        $this->assertEquals('canceled', $space->data['subscription_status']);
    }
    
    /** @test */
    public function it_handles_invoice_payment_succeeded()
    {
        // Create a user with Stripe ID
        $user = $this->createUserWithStripeId([
            'stripe_id' => 'cus_test123',
        ]);
        
        // Create a space with active subscription
        $space = Space::create([
            'id' => 'test-space',
            'name' => 'Test Space',
            'owner_id' => $user->id,
            'data' => [
                'subscription_id' => 'sub_test123',
                'subscription_status' => 'active',
                'plan' => 'price_monthly',
            ],
        ]);
        
        // Mock Stripe Subscription::retrieve to return an object with metadata
        $this->mock(\Stripe\Subscription::class, function ($mock) use ($space) {
            $subscription = new \stdClass();
            $subscription->metadata = new \stdClass();
            $subscription->metadata->space_id = $space->id;
            
            $mock->shouldReceive('retrieve')
                ->with('sub_test123')
                ->andReturn($subscription);
        });
        
        // Generate webhook payload for invoice payment succeeded event
        $payload = $this->generateWebhookPayload('invoice.payment_succeeded', [
            'id' => 'in_test123',
            'customer' => 'cus_test123',
            'subscription' => 'sub_test123',
            'paid' => true,
        ]);
        
        // Post the webhook
        $response = $this->postJson('/stripe/webhook', $payload);
        
        // Check response
        $response->assertOk();
        
        // Refresh space from database
        $space->refresh();
        
        // Verify the space was properly updated
        $this->assertEquals('active', $space->data['subscription_status']);
        $this->assertArrayHasKey('last_payment_date', $space->data);
    }
    
    /** @test */
    public function it_handles_invoice_payment_failed()
    {
        // Create a user with Stripe ID
        $user = $this->createUserWithStripeId([
            'stripe_id' => 'cus_test123',
        ]);
        
        // Create a space with active subscription
        $space = Space::create([
            'id' => 'test-space',
            'name' => 'Test Space',
            'owner_id' => $user->id,
            'data' => [
                'subscription_id' => 'sub_test123',
                'subscription_status' => 'active',
                'plan' => 'price_monthly',
            ],
        ]);
        
        // Mock Stripe Subscription::retrieve to return an object with metadata
        $this->mock(\Stripe\Subscription::class, function ($mock) use ($space) {
            $subscription = new \stdClass();
            $subscription->metadata = new \stdClass();
            $subscription->metadata->space_id = $space->id;
            
            $mock->shouldReceive('retrieve')
                ->with('sub_test123')
                ->andReturn($subscription);
        });
        
        // Generate webhook payload for invoice payment failed event
        $payload = $this->generateWebhookPayload('invoice.payment_failed', [
            'id' => 'in_test123',
            'customer' => 'cus_test123',
            'subscription' => 'sub_test123',
            'paid' => false,
        ]);
        
        // Post the webhook
        $response = $this->postJson('/stripe/webhook', $payload);
        
        // Check response
        $response->assertOk();
        
        // Refresh space from database
        $space->refresh();
        
        // Verify the space was properly updated
        $this->assertEquals('past_due', $space->data['subscription_status']);
        $this->assertArrayHasKey('payment_failed_date', $space->data);
    }
    
    /** @test */
    public function it_ignores_webhooks_for_unknown_customers()
    {
        // Generate webhook payload with unknown customer
        $payload = $this->generateWebhookPayload('customer.subscription.updated', [
            'id' => 'sub_test123',
            'customer' => 'cus_unknown',
            'status' => 'active',
        ]);
        
        // Post the webhook
        $response = $this->postJson('/stripe/webhook', $payload);
        
        // Check response is still OK (webhooks should always return 200)
        $response->assertOk();
    }
}
