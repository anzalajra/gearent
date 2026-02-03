<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\RentalItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function index()
    {
        $customer = Auth::guard('customer')->user();

        // Check if customer is verified
        if (!$customer->canRent()) {
            return redirect()->route('customer.profile')
                ->with('error', 'Anda harus menyelesaikan verifikasi akun sebelum dapat melakukan checkout. Silakan lengkapi dokumen yang diperlukan.');
        }

        $cartItems = $customer->carts()->with(['productUnit.product'])->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $subtotal = $cartItems->sum('subtotal');
        $deposit = $subtotal * 0.3;

        return view('frontend.checkout.index', compact('customer', 'cartItems', 'subtotal', 'deposit'));
    }

    public function process(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        // Check if customer is verified
        if (!$customer->canRent()) {
            return redirect()->route('customer.profile')
                ->with('error', 'Anda harus menyelesaikan verifikasi akun sebelum dapat melakukan checkout.');
        }

        $request->validate([
            'notes' => 'nullable|string|max:500',
            'agree_terms' => 'required|accepted',
        ]);

        $cartItems = $customer->carts()->with(['productUnit.product'])->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        // Group cart items by date range
        $groupedItems = $cartItems->groupBy(function ($item) {
            return $item->start_date->format('Y-m-d') . '_' . $item->end_date->format('Y-m-d');
        });

        DB::beginTransaction();

        try {
            $rentals = [];

            foreach ($groupedItems as $dateKey => $items) {
                $firstItem = $items->first();
                $subtotal = $items->sum('subtotal');
                $deposit = $subtotal * 0.3;

                $rental = Rental::create([
                    'customer_id' => $customer->id,
                    'start_date' => $firstItem->start_date,
                    'end_date' => $firstItem->end_date,
                    'status' => Rental::STATUS_PENDING,
                    'subtotal' => $subtotal,
                    'discount' => 0,
                    'total' => $subtotal,
                    'deposit' => $deposit,
                    'notes' => $request->notes,
                ]);

                foreach ($items as $cartItem) {
                    $rentalItem = RentalItem::create([
                        'rental_id' => $rental->id,
                        'product_unit_id' => $cartItem->product_unit_id,
                        'daily_rate' => $cartItem->daily_rate,
                        'days' => $cartItem->days,
                        'subtotal' => $cartItem->subtotal,
                    ]);

                    // Attach kits automatically
                    $rentalItem->attachKitsFromUnit();
                }

                // Create initial deliveries (Draft SJK & SJM)
                $rental->load('items.rentalItemKits');
                $rental->createDeliveries();

                $rentals[] = $rental;
            }

            // Clear cart
            $customer->carts()->delete();

            DB::commit();

            return redirect()->route('checkout.success', ['rental' => $rentals[0]->id])
                ->with('success', 'Your booking has been submitted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    public function success(Rental $rental)
    {
        $customer = Auth::guard('customer')->user();

        if ($rental->customer_id !== $customer->id) {
            abort(403);
        }

        $rental->load(['items.productUnit.product']);

        return view('frontend.checkout.success', compact('rental'));
    }
}