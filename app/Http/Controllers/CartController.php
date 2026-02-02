<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\ProductUnit;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CartController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $customer = Auth::guard('customer')->user();
        $cartItems = $customer->carts()->with(['productUnit.product'])->get();
        $total = $cartItems->sum('subtotal');

        return view('frontend.cart.index', compact('cartItems', 'total'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_unit_id' => 'required|exists:product_units,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
        ]);

        $customer = Auth::guard('customer')->user();
        $unit = ProductUnit::with('product')->findOrFail($request->product_unit_id);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $days = max(1, $startDate->diffInDays($endDate));

        // Check if already in cart
        $existingCart = $customer->carts()->where('product_unit_id', $unit->id)->first();
        if ($existingCart) {
            return back()->with('error', 'This item is already in your cart.');
        }

        // Check availability
        $isConflict = $unit->rentalItems()
            ->whereHas('rental', function ($query) use ($startDate, $endDate) {
                $query->whereIn('status', ['pending', 'active', 'late_pickup', 'late_return'])
                    ->where(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<', $endDate)
                          ->where('end_date', '>', $startDate);
                    });
            })
            ->exists();

        if ($isConflict) {
            return back()->with('error', 'This unit is not available for the selected dates.');
        }

        Cart::create([
            'customer_id' => $customer->id,
            'product_unit_id' => $unit->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days' => $days,
            'daily_rate' => $unit->product->daily_rate,
            'subtotal' => $unit->product->daily_rate * $days,
        ]);

        return back()->with('success', 'Item added to cart.');
    }

    public function update(Request $request, Cart $cart)
    {
        $customer = Auth::guard('customer')->user();
        
        if ($cart->customer_id !== $customer->id) {
            abort(403);
        }

        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $cart->update([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
        $cart->recalculate();

        return back()->with('success', 'Cart updated.');
    }

    public function remove(Cart $cart)
    {
        $customer = Auth::guard('customer')->user();
        
        if ($cart->customer_id !== $customer->id) {
            abort(403);
        }

        $cart->delete();

        return back()->with('success', 'Item removed from cart.');
    }

    public function clear()
    {
        $customer = Auth::guard('customer')->user();
        $customer->carts()->delete();

        return back()->with('success', 'Cart cleared.');
    }
}