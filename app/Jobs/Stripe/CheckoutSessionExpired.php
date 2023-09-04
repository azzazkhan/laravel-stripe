<?php

namespace App\Jobs\Stripe;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class CheckoutSessionExpired implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     *
     * @param  array  $payload
     */
    public function __construct(public array $payload)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $session_id = $this->payload['data']['object']['id'];

        try {
            logger()
                ->channel('single')
                ->debug('[CheckoutSessionExpired] Started');

            // Prevent other queries from reading/updating selected rows
            DB::statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
            DB::transaction(function () use ($session_id) {
                // Lock the transaction record
                $transaction = DB::table('transactions')
                    ->where('session', $session_id)
                    ->lockForUpdate()
                    ->first();

                // Transaction record was removed or it was already paid/cancelled
                if (!$transaction || $transaction->status != 'pending') {
                    logger()
                        ->channel('single')
                        ->debug('[CheckoutSessionExpired] Transaction does not exist or was already paid/cancelled');

                    return;
                }

                // Mark the transaction as cancelled
                DB::table('transactions')
                    ->where('session', $session_id)
                    ->update(['status' => 'cancelled']);

                logger()->channel('single')->debug('[CheckoutSessionExpired] Cancelled the transaction');
            });
        } catch (Exception $e) {
            logger()->channel('single')->debug('[CheckoutSessionExpired] Error ocurred while cancelling transaction!');
            report($e);
        }
    }
}
