<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Exception;

class TransactionCreditService
{
    /**
     * Checks the transaction status and credits the user with specified amount
     * of resources.
     *
     * @param  \App\Models\Transaction|string  $transaction
     * @return void
     */
    public static function credit(Transaction|string $transaction): void
    {
        // Convert the Eloquent model into primary key
        $transactionId = $transaction instanceof Transaction ? $transaction->ulid : $transaction;

        try {
            // Prevent other queries from reading/updating selected rows
            DB::statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
            DB::transaction(function () use ($transactionId) {

                // Lock multiple tables in a single join operation
                $result = DB::table('transactions')
                    ->select([
                        'transactions.*',
                        'users.balance',
                        'packages.coins as pack_coins',
                        'packages.additional as pack_add_coins'
                    ])
                    ->join('users', 'transactions.user_id', '=', 'users.id')
                    ->join('packages', 'transactions.package_id', '=', 'packages.id')
                    ->where('transactions.ulid', $transactionId)
                    ->lockForUpdate()
                    ->first();

                // TODO: Handle record does not exists error
                if (!$result) return;

                // Transaction was successful and resources were already
                // credited to the user
                if ($result->status == 'successful') return;

                $coins = (int) decrypt($result->balance) + $result->pack_coins + $result->pack_add_coins;

                // Set transaction as successful and credit the user's account
                DB::table('transactions')->where('ulid', $transactionId)->update(['status' => 'successful']);
                DB::table('users')->where('id', $result->user_id)->update([
                    'balance' => encrypt($coins),
                    'coins' => $coins,
                ]);

                logger()
                    ->channel('stderr')
                    ->debug(sprintf('Credited user %d with %d coins', $result->user_id, $coins));
            });
        } catch (Exception $e) {
            logger()->channel('stderr')->debug('Error ocurred while crediting users account');
            report($e);
        }
    }
}
