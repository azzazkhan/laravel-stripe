<?php

namespace App\Jobs\Stripe;

use App\Services\TransactionCreditService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class CheckoutSessionCompleted implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     *
     * @param  array  $payload
     */
    public function __construct(public array $payload)
    {
        $session_id = $this->payload['data']['object']['id'];

        try {
            logger()
                ->channel('single')
                ->debug('[CheckoutSessionCompleted] Started');

            // This will mark the session as completed and credits the user
            TransactionCreditService::credit($session_id, true);
        } catch (Exception $e) {
            logger()
                ->channel('single')
                ->debug('[CheckoutSessionCompleted] Error ocurred while processing completed transaction!');
            report($e);
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
    }
}
