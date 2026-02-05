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
            $msg = 'Anda harus menyelesaikan verifikasi akun sebelum dapat melakukan rental. Silakan lengkapi dokumen di halaman Profile.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 403);
            }
            return back()->with('error', $msg);
        }

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        
        $product = \App\Models\Product::findOrFail($request->product_id);
        
        // Find available unit for the requested product
        $unit = $product->findAvailableUnit($startDate, $endDate);

        if (!$unit) {
            $msg = "Maaf, alat ini tidak tersedia untuk tanggal yang dipilih.";
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $days = max(1, $startDate->diffInDays($endDate));

        // Check for existing cart items and handle date synchronization
        $cartItems = $customer->carts()->with('productUnit.product')->get();
        $firstItem = $cartItems->first();
        
        $updates = [];
        $conflicts = [];
        $needsSync = false;

        if ($firstItem) {
            // Check if dates are different (using timestamp comparison for precision)
            if ($firstItem->start_date->ne($startDate) || $firstItem->end_date->ne($endDate)) {
                $needsSync = true;
                
                foreach ($cartItems as $item) {
                    $p = $item->productUnit->product;
                    // Check availability for new dates
                    // We can reuse the current unit if it's available, otherwise find another unit of same product
                    $newUnit = $p->findAvailableUnit($startDate, $endDate);
                    
                    if ($newUnit) {
                        $updates[] = [
                            'cart_item' => $item,
                            'new_unit_id' => $newUnit->id
                        ];
                    } else {
                        $conflicts[] = $p->name;
                    }
                }
            }
        }

        // Handle Conflicts
        if (!empty($conflicts)) {
            if (!$request->boolean('confirm_changes')) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'status' => 'conflict',
                        'conflicts' => $conflicts,
                        'message' => 'Beberapa item di keranjang tidak tersedia untuk tanggal baru.'
                    ], 409);
                }
                // Fallback for non-AJAX (though we should prioritize AJAX)
                return back()->with('error', 'Konflik ketersediaan item di keranjang. Harap gunakan fitur sinkronisasi.');
            }

            // Remove conflicting items
            foreach ($cartItems as $item) {
                if (in_array($item->productUnit->product->name, $conflicts)) {
                    $item->delete();
                }
            }
        }

        // Apply Updates (Sync Dates)
        if ($needsSync) {
            foreach ($updates as $update) {
                $update['cart_item']->update([
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'days' => $days,
                    'product_unit_id' => $update['new_unit_id'],
                    'subtotal' => $update['cart_item']->daily_rate * $days,
                ]);
            }
        }

        // Check if THIS product is already in cart (after potential sync/cleanup)
        // If it was in cart and updated, we might duplicate it if we are not careful?
        // Actually, if the user is adding Product A, and Product A is already in cart:
        // - If dates matched: standard check "already in cart"
        // - If dates differed: We updated the existing Product A in the loop above.
        // So we should check if we just updated THIS product.
        
        $alreadyInCart = false;
        foreach ($updates as $update) {
            if ($update['cart_item']->productUnit->product_id == $product->id) {
                $alreadyInCart = true;
                break;
            }
        }
        
        if (!$alreadyInCart) {
            // Double check if it exists (in case it wasn't updated but dates matched)
            $existing = $customer->carts()
                ->whereHas('productUnit', function($q) use ($product) {
                    $q->where('product_id', $product->id);
                })->first();
                
            if ($existing) {
                 if ($request->wantsJson()) {
                    return response()->json(['message' => 'Item ini sudah ada di keranjang Anda.'], 422);
                }
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
        }

        $msg = 'Berhasil ditambahkan ke keranjang.';
        if ($needsSync) {
            $msg = empty($conflicts) 
                ? 'Tanggal sewa keranjang telah diperbarui mengikuti pilihan terbaru Anda.' 
                : 'Tanggal diperbarui. Beberapa item dihapus karena tidak tersedia.';
        }

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $msg]);
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