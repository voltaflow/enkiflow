#!/bin/bash
php artisan test tests/Feature/Subscription/StripeWebhookTest.php --filter=it_ignores_webhooks_for_unknown_customers
