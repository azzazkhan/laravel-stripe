<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function checkout(Request $request): RedirectResponse
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
            amount: (int) ($package->price * 100),
            name: $package->name,
            sessionOptions: [
                'client_reference_id' => $user->id,
                // /checkout/{transaction}/success?session={CHECKOUT_SESSION_ID}
                'success_url' => route(
                    'checkout.success',
                    ['transaction' => $secret, 'session' => '{CHECKOUT_SESSION_ID}'],
                    true
                ),
                'cancel_url' => route('checkout.cancel', ['transaction' => $secret], true)
            ],
        );
        $session = $checkout->asStripeCheckoutSession(); // Stripe Checkout session object

        $transaction = new Transaction();

        $transaction->ulid = $secret;
        $transaction->amount = (int) ($package->price * 100);
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

        return redirect(route('home'));
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
            'session' => ['required', 'string', 'min:58']
        ]);

        // Make sure the transaction initiated by the user and the session
        // ID matches with the transaction
        abort_if($transaction->user_id != $request->user()->id, Response::HTTP_NOT_FOUND);
        abort_if($transaction->session != $validated['session'], Response::HTTP_NOT_FOUND);

        logger()->channel('stderr')->debug('Received checkout success request');

        abort_if($transaction->cancelled, Response::HTTP_BAD_REQUEST, 'The order was already cancelled!');
        abort_if($transaction->expired, Response::HTTP_BAD_REQUEST, 'The order was expired!');

        logger()->channel('stderr')->debug('Retrieving and validating checkout session');

        // Retrieve session from Strip and validate it
        $session = Cashier::stripe()->checkout->sessions->retrieve($validated['session']);

        // Save the retrieved session in storage for inspection
        $filename = sprintf('%s_%s.json', now()->format('H-i-s-u'), $session->object);
        Storage::put("checkout/$filename", $session->toJSON());

        // Possible values (paid/unpaid/no_payment_required)
        // $session->payment_status;

        // Stripe may have mistakenly redirected the user, redirect back to the
        // payment URL
        if ($session->payment_status != 'paid')
            return redirect($session->url);

        // Confirmation was successful mark the transaction as completed
        $transaction->received = $session->amount_total;
        $transaction->status = 'completed';
        $transaction->save();

        $transaction->load('package');

        // TODO: Credit the coins to user's account in a DB transaction
        // $coins = $transaction->package->coins;
        // $user->update(['coins' => $user->coins + $coins, 'balance' => $user->balance + $coins]);

        return view('checkout.success', compact('transaction'));
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
        abort_if($transaction->user_id != $request->user()->id, Response::HTTP_NOT_FOUND);
        abort_if($transaction->successful, Response::HTTP_BAD_REQUEST, 'The order was already paid!');
        abort_if($transaction->cancelled, Response::HTTP_BAD_REQUEST, 'The order was already cancelled!');
        abort_if($transaction->expired, Response::HTTP_BAD_REQUEST, 'The order was expired!');

        $session = Cashier::stripe()->checkout->sessions->retrieve($transaction->session);

        // The session was successful, update in database
        if ($session->payment_status == 'paid') {
            $transaction->received = $session->amount_total;
            $transaction->status = 'completed';
            $transaction->save();

            logger()->channel('stderr')->debug('Session already completed cannot cancel!');
            $filename = sprintf('%s_%s.json', now()->format('H-i-s-u'), $session->object);
            Storage::put("checkout/$filename", $session->toJSON());

            // TODO: Credit the user

            abort(Response::HTTP_BAD_REQUEST, 'The order was already paid!');
        }

        // The session is already expired
        if ($session->expires_at >= (int) date('U')) {
            $session->expire();
            $transaction->status = 'pending';
            $transaction->save();

            logger()->channel('stderr')->debug('Session already expired cannot cancel!');
            $filename = sprintf('%s_%s.json', now()->format('H-i-s-u'), $session->object);
            Storage::put("checkout/$filename", $session->toJSON());

            abort(Response::HTTP_BAD_REQUEST, 'The order was expired!');
        }

        $session->expire();
        $transaction->status = 'cancelled';
        $transaction->save();

        logger()->channel('stderr')->debug('Expired the session and cancelled transaction!');
        $filename = sprintf('%s_%s.json', now()->format('H-i-s-u'), $session->object);
        Storage::put("checkout/$filename", $session->toJSON());

        $transaction->load('package');

        return view('checkout.cancelled', compact('transaction'));
    }
}
