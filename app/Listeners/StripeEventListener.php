<?php

namespace App\Listeners;

use App\Jobs\Stripe\CheckoutSessionCompleted;
use App\Jobs\Stripe\CheckoutSessionExpired;
use Illuminate\Support\Facades\Storage;
use Laravel\Cashier\Events\WebhookReceived;

class StripeEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(WebhookReceived $event): void
    {
        // TODO: Check if the request IP matches the list of Stripe provided ips
        // @see https://stripe.com/docs/ips

        logger()
            ->channel('single')
            ->debug('Stripe webhook event received!', ['event' => $event->payload['type'] ?? 'unknown']);

        $filename = sprintf('%s_%s.json', now()->format('Y-m-d_H-i-s_u'), $event->payload['type']);
        Storage::put("stripe/events/$filename", json_encode($event->payload, JSON_PRETTY_PRINT));

        switch ($event->payload['type']):
            case 'checkout.session.completed':
                dispatch(new CheckoutSessionCompleted($event->payload));
                break;
            case 'checkout.session.expired':
                dispatch(new CheckoutSessionExpired($event->payload));
                break;
        endswitch;
    }
}
