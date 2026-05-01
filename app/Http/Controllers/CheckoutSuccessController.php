<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lunar\Models\Order;

class CheckoutSuccessController extends Controller
{
    /**
     * Show the order confirmation page.
     *
     * The order reference is passed via query string: /checkout/success?order=REF-001
     */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        $reference = $request->query('order');

        if (!$reference) {
            return redirect()->route('home');
        }

        $order = Order::where('reference', $reference)->first();

        if (!$order) {
            return redirect()->route('home');
        }

        return Inertia::render('Checkout/Success', [
            'order' => [
                'id' => $order->id,
                'reference' => $order->reference,
                'status' => $order->status,
                'sub_total' => $order->sub_total?->formatted(),
                'total' => $order->total?->formatted(),
                'placed_at' => $order->placed_at?->toDateTimeString(),
            ],
        ]);
    }
}
