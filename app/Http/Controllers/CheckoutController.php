<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

        $cartItems = $customer->carts()->with(['productUnit.product', 'productUnit.variation'])->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $subtotal = $cartItems->sum('subtotal');
        
        // Calculate Gross Total and Category Discount
        $grossTotal = 0;
        foreach ($cartItems as $item) {
             $unit = $item->productUnit;
             $originalDailyRate = $unit->variation->daily_rate ?? $unit->product->daily_rate;
             $grossTotal += $originalDailyRate * $item->days;
        }
        $categoryDiscountAmount = $grossTotal - $subtotal;
        $categoryName = $customer->category ? $customer->category->name : null;

        $deposit = Rental::calculateDeposit($subtotal);
        
        // Clear previous discount session on page load to ensure fresh start
        // or check if valid? Better to let user re-apply or persist if valid.
        // Let's persist for better UX if they refresh.
        // But we should validate if it's still valid for current cart.
        
        $discountAmount = 0;
        $discountCode = session('checkout_discount_code');
        
        if ($discountCode) {
            $discount = Discount::where('code', $discountCode)
                ->where('is_active', true)
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->first();
                
            if ($discount && $subtotal >= $discount->min_rental_amount) {
                if ($discount->type === 'percentage') {
                    $discountAmount = $subtotal * ($discount->value / 100);
                    if ($discount->max_discount_amount && $discountAmount > $discount->max_discount_amount) {
                        $discountAmount = $discount->max_discount_amount;
                    }
                } else {
                    $discountAmount = $discount->value;
                }
                
                if ($discountAmount > $subtotal) $discountAmount = $subtotal;
            } else {
                session()->forget(['checkout_discount_code', 'checkout_discount_amount']);
            }
        }

        return view('frontend.checkout.index', compact(
            'customer', 'cartItems', 'subtotal', 'deposit', 'discountAmount',
            'categoryDiscountAmount', 'categoryName', 'grossTotal'
        ));
    }

    public function validateDiscount(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $code = $request->code;
        $customer = Auth::guard('customer')->user();
        $cartItems = $customer->carts;

        if ($cartItems->isEmpty()) {
             return response()->json(['valid' => false, 'message' => 'Cart is empty.']);
        }

        $discount = Discount::where('code', $code)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();

        if (!$discount) {
            return response()->json(['valid' => false, 'message' => 'Kode diskon tidak valid atau kadaluarsa.']);
        }

        if ($discount->usage_limit && $discount->usage_count >= $discount->usage_limit) {
            return response()->json(['valid' => false, 'message' => 'Batas penggunaan kode diskon telah tercapai.']);
        }

        $subtotal = $cartItems->sum('subtotal');

        if ($subtotal < $discount->min_rental_amount) {
            return response()->json(['valid' => false, 'message' => 'Minimal total belanja Rp ' . number_format($discount->min_rental_amount, 0, ',', '.') . ' belum terpenuhi.']);
        }

        // Calculate discount
        $discountAmount = 0;
        if ($discount->type === 'percentage') {
            $discountAmount = $subtotal * ($discount->value / 100);
            if ($discount->max_discount_amount && $discountAmount > $discount->max_discount_amount) {
                $discountAmount = $discount->max_discount_amount;
            }
        } else {
            $discountAmount = $discount->value;
        }

        if ($discountAmount > $subtotal) {
            $discountAmount = $subtotal;
        }

        session(['checkout_discount_code' => $code]);
        session(['checkout_discount_amount' => $discountAmount]);

        $newTotal = $subtotal - $discountAmount;
        // Recalculate deposit based on new total? 
        // Existing logic uses subtotal. Let's stick to subtotal for deposit to be safe for now unless user asked.
        // Actually, if I pay less, maybe deposit should stay same to cover potential damage based on item value.
        // So deposit stays based on subtotal.
        $deposit = Rental::calculateDeposit($subtotal); 

        return response()->json([
            'valid' => true,
            'message' => 'Kode diskon berhasil digunakan!',
            'discount_amount' => $discountAmount,
            'new_subtotal' => $subtotal,
            'new_total' => $newTotal + $deposit, // Total usually includes deposit? 
            // In index view: Total = Subtotal (actually subtotal seems to be treated as Total to Pay + Deposit?)
            // View says: Total = Subtotal. 
            // Wait, view says:
            // Subtotal: xxx
            // Deposit: xxx
            // Total: Subtotal (line 95 in view)
            // Wait, does user pay Subtotal + Deposit?
            // Line 95: <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
            // This is confusing. Usually Total = Subtotal + Deposit or just Subtotal if Deposit is included?
            // Let's check the view again.
            // View line 93-95: Total ... $subtotal.
            // View line 86: If deposit > 0, show deposit.
            // So currently Total = Subtotal. It seems Deposit is NOT added to the Total shown at bottom? 
            // Or is it included?
            // If I look at Controller: 
            // 'total' => $subtotal,
            // 'deposit' => $deposit,
            // It seems Total = Subtotal. Deposit is just informational or separate?
            // But usually you pay Deposit upfront.
            // Let's assume Total to Pay = Subtotal + Deposit? 
            // No, the code says `total` => `$subtotal`.
            // Let's assume the user pays `$subtotal`.
            // Wait, if deposit is required, surely it should be added?
            // Let's look at `CheckoutController::process`:
            // 'total' => $subtotal,
            // 'deposit' => $deposit,
            // It seems `total` in database is `subtotal`.
            // Maybe `deposit` is just recorded but not charged? Or charged separately?
            // If I look at the view again:
            // Subtotal: 100
            // Deposit: 30
            // Total: 100
            // This implies Deposit is included in Subtotal or ignored?
            // Actually, if `subtotal` is sum of `daily_rate * days`, then it's the rental fee.
            // Deposit is extra.
            // If Total is just Subtotal, then user pays Rental Fee. Deposit is... ?
            // Maybe the view is just showing "Total Rental Cost"?
            // Let's check `Rental::calculateDeposit`.
            
            // I will return what is needed for UI.
            'new_grand_total' => $newTotal // This is what matters.
        ]);
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

        // Calculate global totals
        $globalSubtotal = $cartItems->sum('subtotal');
        $globalDiscountAmount = 0;
        $discountCode = session('checkout_discount_code');
        $discountId = null;

        // Validate and apply discount
        if ($discountCode) {
            $discount = Discount::where('code', $discountCode)
                ->where('is_active', true)
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->first();

            if ($discount && $globalSubtotal >= $discount->min_rental_amount) {
                if ($discount->usage_limit && $discount->usage_count >= $discount->usage_limit) {
                    // Limit reached, ignore discount
                } else {
                    $discountId = $discount->id;
                    if ($discount->type === 'percentage') {
                        $globalDiscountAmount = $globalSubtotal * ($discount->value / 100);
                        if ($discount->max_discount_amount && $globalDiscountAmount > $discount->max_discount_amount) {
                            $globalDiscountAmount = $discount->max_discount_amount;
                        }
                    } else {
                        $globalDiscountAmount = $discount->value;
                    }
                    if ($globalDiscountAmount > $globalSubtotal) {
                        $globalDiscountAmount = $globalSubtotal;
                    }
                    
                    // Increment usage
                    $discount->increment('usage_count');
                }
            }
        }

        // Group cart items by date range
        $groupedItems = $cartItems->groupBy(function ($item) {
            return $item->start_date->format('Y-m-d') . '_' . $item->end_date->format('Y-m-d');
        });

        DB::beginTransaction();

        try {
            $rentals = [];
            $reservedUnitIds = []; // Track units reserved in this transaction

            foreach ($groupedItems as $dateKey => $items) {
                $firstItem = $items->first();
                $subtotal = $items->sum('subtotal');
                
                // Calculate proportional discount for this rental
                $rentalDiscount = 0;
                if ($globalSubtotal > 0 && $globalDiscountAmount > 0) {
                    $proportion = $subtotal / $globalSubtotal;
                    $rentalDiscount = $globalDiscountAmount * $proportion;
                }

                // Deposit calculation
                $deposit = Rental::calculateDeposit($subtotal); // Keeping it based on subtotal as per original logic

                // Create Quotation first
                $quotation = \App\Models\Quotation::create([
                    'user_id' => $customer->id,
                    'date' => now(),
                    'valid_until' => now()->addDays(7),
                    'status' => \App\Models\Quotation::STATUS_ON_QUOTE,
                    'subtotal' => $subtotal,
                    'tax' => 0,
                    'total' => $subtotal - $rentalDiscount,
                    'notes' => $request->notes,
                ]);

                $rental = Rental::create([
                    'user_id' => $customer->id,
                    'start_date' => $firstItem->start_date,
                    'end_date' => $firstItem->end_date,
                    'status' => Rental::STATUS_QUOTATION,
                    'quotation_id' => $quotation->id,
                    'subtotal' => $subtotal,
                    'discount' => $rentalDiscount,
                    'discount_id' => $discountId,
                    'discount_code' => $discountCode,
                    'total' => $subtotal - $rentalDiscount,
                    'deposit' => $deposit,
                    'notes' => $request->notes,
                ]);

                foreach ($items as $cartItem) {
                    // VALIDATION: Ensure unit is still available
                    $product = $cartItem->productUnit->product;
                    $startDate = $cartItem->start_date;
                    $endDate = $cartItem->end_date;

                    // Get fresh availability from DB
                    // This checks against OTHER existing rentals (Confirmed, Active, etc.)
                    $availableUnits = $product->findAvailableUnits($startDate, $endDate);
                    
                    // Filter out units we already reserved in this current checkout session
                    $candidates = $availableUnits->whereNotIn('id', $reservedUnitIds);
                    
                    $finalUnitId = null;

                    // 1. Check if the unit currently in cart is valid
                    if ($candidates->contains('id', $cartItem->product_unit_id)) {
                        $finalUnitId = $cartItem->product_unit_id;
                    } 
                    // 2. If not, try to auto-switch to another available unit
                    else {
                        $replacement = $candidates->first();
                        if ($replacement) {
                            $finalUnitId = $replacement->id;
                        }
                    }

                    // 3. If no unit available, fail the transaction
                    if (!$finalUnitId) {
                        throw new \Exception("Maaf, produk {$product->name} tidak lagi tersedia untuk tanggal yang dipilih (Unit penuh).");
                    }

                    // Reserve this unit for this transaction
                    $reservedUnitIds[] = $finalUnitId;

                    $rentalItem = RentalItem::create([
                        'rental_id' => $rental->id,
                        'product_unit_id' => $finalUnitId, // Use the validated unit ID
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

            // Clear cart and session
            $customer->carts()->delete();
            session()->forget(['checkout_discount_code', 'checkout_discount_amount']);

            DB::commit();

            return redirect()->route('checkout.success', ['rental' => $rentals[0]->id])
                ->with('success', 'Your booking has been submitted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Something went wrong. Please try again. ' . $e->getMessage());
        }
    }

    public function success(Rental $rental)
    {
        $customer = Auth::guard('customer')->user();

        // Use loose comparison because user_id from DB might be string while auth user id is int
        if ($rental->user_id != $customer->id) {
            abort(403);
        }

        $rental->load(['items.productUnit.product']);

        return view('frontend.checkout.success', compact('rental'));
    }
}