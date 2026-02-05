<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\ProductUnit;
use App\Models\Rental;
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
        $deposit = Rental::calculateDeposit($total);
        $canCheckout = $customer->canRent();

        return view('frontend.cart.index', compact('cartItems', 'total', 'deposit', 'canCheckout'));
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
        
        // Check for existing cart items to enforce global dates
        $existingCartItem = $customer->carts()->first();
        if ($existingCartItem) {
            $startDate = $existingCartItem->start_date;
            $endDate = $existingCartItem->end_date;
            $usingGlobalDates = true;
        } else {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $usingGlobalDates = false;
        }
        
        // Find available unit automatically
        $unit = $product->findAvailableUnit($startDate, $endDate);

        if (!$unit) {
            $dateMsg = $usingGlobalDates ? " (mengikuti tanggal di keranjang: {$startDate->format('d M Y')})" : "";
            return back()->with('error', "Maaf, alat ini tidak tersedia untuk tanggal yang dipilih{$dateMsg}.");
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
            return back()->with('error', 'Item ini sudah ada di keranjang Anda.');
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

        $msg = 'Berhasil ditambahkan ke keranjang.';
        if ($usingGlobalDates) {
            $msg .= " Tanggal disesuaikan dengan item lain di keranjang ({$startDate->format('d M')} - {$endDate->format('d M')}).";
        }

        return back()->with('success', $msg);
    }

    public function updateAll(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $days = max(1, $startDate->diffInDays($endDate));
        
        $cartItems = $customer->carts()->with('productUnit.product')->get();
        $errors = [];
        $updatedCount = 0;

        foreach ($cartItems as $item) {
            $product = $item->productUnit->product;
            
            // Try to find a unit available for the NEW dates
            // First check if the CURRENT unit is available (excluding the current cart reservation if it overlaps? 
            // Actually findAvailableUnit checks rentals. Cart items are not rentals yet, so they don't block availability 
            // EXCEPT for other cart items. But we are updating THIS cart item.)
            
            // However, findAvailableUnit might return a DIFFERENT unit if the current one is booked by someone else.
            // So we should be flexible.
            $unit = $product->findAvailableUnit($startDate, $endDate);

            if (!$unit) {
                $errors[] = "Produk {$product->name} tidak tersedia untuk tanggal baru.";
                continue;
            }

            $item->update([
                'product_unit_id' => $unit->id, // Switch unit if necessary
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days' => $days,
                'subtotal' => $product->daily_rate * $days,
            ]);
            $updatedCount++;
        }

        if (count($errors) > 0) {
            return back()->with('error', implode(' ', $errors) . ' Item lain berhasil diperbarui.');
        }

        return back()->with('success', 'Semua item di keranjang berhasil diperbarui ke tanggal baru.');
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