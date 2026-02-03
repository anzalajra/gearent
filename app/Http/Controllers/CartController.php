<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CartController extends Controller
{
    public function index()
    {
        $customer = Auth::guard('customer')->user();
        $cartItems = $customer->carts()->with(['productUnit.product'])->get();
        $total = $cartItems->sum('subtotal');
        $canCheckout = $customer->canRent();

        return view('frontend.cart.index', compact('cartItems', 'total', 'canCheckout'));
    }

    public function add(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        // Check if customer is verified
        if (!$customer->canRent()) {
            return back()->with('error', 'Anda harus menyelesaikan verifikasi akun sebelum dapat melakukan rental. Silakan lengkapi dokumen di halaman Profile.');
        }

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
        ]);

        $product = \App\Models\Product::findOrFail($request->product_id);
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        
        // Find available unit automatically
        $unit = $product->findAvailableUnit($startDate, $endDate);

        if (!$unit) {
            return back()->with('error', 'Maaf, alat ini tidak tersedia untuk tanggal yang dipilih.');
        }

        $days = max(1, $startDate->diffInDays($endDate));

        // Check if already in cart
        $existingCart = $customer->carts()
            ->where('product_unit_id', $unit->id)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where('start_date', '<', $endDate)
                      ->where('end_date', '>', $startDate);
            })
            ->first();

        if ($existingCart) {
            return back()->with('error', 'Item ini sudah ada di keranjang Anda untuk tanggal yang sama.');
        }

        Cart::create([
            'customer_id' => $customer->id,
            'product_unit_id' => $unit->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days' => $days,
            'daily_rate' => $product->daily_rate,
            'subtotal' => $product->daily_rate * $days,
        ]);

        return back()->with('success', 'Berhasil ditambahkan ke keranjang.');
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