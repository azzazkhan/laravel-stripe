<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Transaction;
use App\Services\TransactionCreditService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Cashier\Cashier;

class CheckoutController extends Controller
{
    /**
     * Handle incoming checkout request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\Support\Responsable
     */
    public function checkout(Request $request): Responsable
    {
        $validated = $request->validate([
            'package' => ['required', 'string', Rule::exists('packages', 'id')],
        ]);

        $package = Package::findOrFail($validated['package']);

        /** @var \App\Models\User */
        $user = $request->user();
        $secret = (string) Str::ulid();

        /** @see https://stripe.com/docs/api/checkout/sessions/create#create_checkout_session-line_items-price_data-product_data */
        // $productData = [
        //     'description' => '',
        //     'images' => [''],
        //     'metadata' => [],
        // ];

        $checkout = $user->checkoutCharge(
            amount: (int) ceil($package->price * 100),
            name: $package->name,
            sessionOptions: [
                'client_reference_id' => $user->id,
                // /checkout/{transaction}/success?session={CHECKOUT_SESSION_ID}
                'success_url' => route(
                    'checkout.success',
                    ['transaction' => $secret],
                    true
                ) . '?session={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('checkout.cancel', ['transaction' => $secret], true)
            ],
        );
        $session = $checkout->asStripeCheckoutSession(); // Stripe Checkout session object

        $transaction = new Transaction();

        $transaction->ulid = $secret;
        $transaction->amount = (int) ceil($package->price * 100);
        $transaction->status = 'pending';
        $transaction->session = $session->id;
        $transaction->expires_at = $session->expires_at;
        $transaction->user_id = $user->id;
        $transaction->package_id = $package->id;
        $transaction->metadata = [
            'products' => [
                [
                    'name' => $package->name,
                    'price' => $package->price,
                    'type' => 'coins-package',
                    'items' => ['coins' => $package->coins]
                ]
            ],
        ];

        $transaction->save();

        return $checkout;
    }

    /**
     * Show Stripe checkout session success page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\View\View
     */
    public function success(Request $request, Transaction $transaction): View
    {
        $validated = $request->validate([
            'session' => ['required', 'string']
        ]);

        /** @var \App\Models\User */
        $user = $request->user();

        // Make sure the transaction initiated by the user and the session
        // ID matches with the transaction
        abort_if($transaction->user_id != $user->id, Response::HTTP_NOT_FOUND);
        abort_if($transaction->session != $validated['session'], Response::HTTP_NOT_FOUND);

        logger()->channel('stderr')->debug('Received checkout success request');

        abort_if($transaction->cancelled, Response::HTTP_BAD_REQUEST, 'The order was cancelled!');
        abort_if($transaction->expired, Response::HTTP_BAD_REQUEST, 'The order expired!');

        $amount = number_format($transaction->amount / 100, 2);
        $package = $transaction->package;

        // Transaction already marked successful by webhooks and resources were
        // credited to the user's account
        if ($transaction->successful)
            return view('checkout.success', compact('amount', 'package'));

        logger()->channel('stderr')->debug('Retrieving and validating checkout session');

        // Retrieve session from Strip and validate it
        $session = Cashier::stripe()->checkout->sessions->retrieve($validated['session']);

        // Save the retrieved session in storage for inspection
        $filename = sprintf('%s_%s.json', now()->format('H-i-s-u'), $session->object);
        Storage::put("checkout/$filename", $session->toJSON());

        // Stripe may have mistakenly redirected the user, redirect back to the
        // payment URL
        if ($session->payment_status != 'paid')
            return redirect($session->url);

        // Mark the transaction as successful and credit the resources to user
        // if not already credited
        TransactionCreditService::credit($transaction);

        return view('checkout.success', compact('amount', 'package'));
    }

    /**
     * Show Stripe checkout session cancellation page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\View\View
     */
    public function cancel(Request $request, Transaction $transaction): View
    {
        /** @var \App\Models\User */
        $user = $request->user();

        // Make sure the transaction was initiated by the current user
        abort_if($transaction->user_id != $user->id, Response::HTTP_NOT_FOUND);

        // Show error if transaction was already marked successful
        abort_if($transaction->successful, Response::HTTP_BAD_REQUEST, 'The payment was already received!');

        $amount = number_format($transaction->amount / 100, 2);
        $package = $transaction->package->name;

        // Transaction was either cancelled by user or the checkout session expired
        if ($transaction->cancelled || $transaction->expired)
            return view('checkout.cancel', compact('amount', 'package'));

        $session = Cashier::stripe()->checkout->sessions->retrieve($transaction->session);
        // Checkout session was already paid!
        abort_if($session->payment_status == 'paid', Response::HTTP_BAD_REQUEST, 'The payment was already received!');

        @$session->expire(); // Expire the stripe checkout session

        $transaction->status = 'cancelled';
        $transaction->save();

        logger()->channel('stderr')->debug('Expired the session and cancelled transaction!');
        $filename = sprintf('%s_%s.json', now()->format('H-i-s-u'), $session->object);
        Storage::put("checkout/$filename", $session->toJSON());

        return view('checkout.cancel', compact('amount', 'package'));
    }
}
