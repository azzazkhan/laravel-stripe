<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Database\Query\Builder;

class TransactionCreditService
{
    /**
     * Checks the transaction status and credits the user with specified amount
     * of resources.
     *
     * @param  \App\Models\Transaction|string  $transaction
     * @param  bool  $usesSession
     * @return void
     */
    public static function credit(Transaction|string $transaction, bool $usesSession = false): void
    {
        // Convert the Eloquent model into primary key or session ID
        $transactionId = $transaction instanceof Transaction ? $transaction->ulid : $transaction;

        try {
            logger()
                ->channel('single')
                ->debug('[TransactionCreditService] Started');

            // Prevent other queries from reading/updating selected rows
            DB::statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
            DB::transaction(function () use ($transactionId, $usesSession) {

                // Lock multiple tables in a single join operation
                $query = DB::table('transactions')
                    ->select([
                        'transactions.*',
                        'users.balance',
                        'packages.coins as pack_coins',
                        'packages.additional as pack_add_coins'
                    ])
                    ->join('users', 'transactions.user_id', '=', 'users.id')
                    ->join('packages', 'transactions.package_id', '=', 'packages.id')
                    ->where(function (Builder $query) use ($transactionId, $usesSession) {
                        $query
                            ->when($usesSession, fn ($query) => $query->where('transactions.session', $transactionId))
                            ->when(!$usesSession, fn ($query) => $query->where('transactions.ulid', $transactionId));
                    });

                $result = $query->lockForUpdate()->first();

                // TODO: Handle record does not exists error
                if (!$result) {
                    logger()
                        ->channel('single')
                        ->debug('[TransactionCreditService] Transaction, user or package record was not found', [
                            'query' => $query->toRawSql(),
                            'transaction_key' => $transactionId,
                            'use_session' => $usesSession,
                        ]);
                    return;
                }

                // Transaction was successful and resources were already
                // credited to the user
                if ($result->status == 'successful') {
                    logger()
                        ->channel('single')
                        ->debug('[TransactionCreditService] Transaction already marked successful');
                    return;
                }

                $coins = $result->pack_coins + $result->pack_add_coins;
                $balance = (int) decrypt($result->balance) + $coins;

                // Set transaction as successful and credit the user's account
                DB::table('transactions')->where('ulid', $result->ulid)->update(['status' => 'successful']);
                DB::table('users')->where('id', $result->user_id)->update([
                    'balance' => encrypt($balance),
                    'coins' => $balance,
                ]);

                logger()
                    ->channel('single')
                    ->debug(sprintf(
                        '[TransactionCreditService] Credited user %d with %d coins, new balance is %d',
                        $result->user_id,
                        $coins,
                        $balance
                    ));
            });
        } catch (Exception $e) {
            logger()->channel('single')->debug('[TransactionCreditService] Error ocurred while crediting users account');
            report($e);
        }
    }
}
