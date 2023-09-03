<?php

namespace App\Listeners;

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
        logger()->channel('stderr')->debug('Stripe webhook event received!', ['event' => $event->payload['type'] ?? 'unknown']);

        $filename = sprintf('%s_%s.json', now()->format('H-i-s-u'), $event->payload['type']);
        Storage::put("stripe/$filename", json_encode($event->payload, JSON_PRETTY_PRINT));
    }
}
