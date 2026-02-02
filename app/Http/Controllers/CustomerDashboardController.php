<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class CustomerDashboardController extends Controller
{
    public function index()
    {
        $customer = Auth::guard('customer')->user();
        $activeRentals = $customer->getActiveRentals();
        $pastRentals = $customer->getPastRentals();
        $cartCount = $customer->carts()->count();

        return view('frontend.dashboard.index', compact('customer', 'activeRentals', 'pastRentals', 'cartCount'));
    }

    public function profile()
    {
        $customer = Auth::guard('customer')->user();
        return view('frontend.dashboard.profile', compact('customer'));
    }

    public function updateProfile(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'id_type' => 'nullable|in:ktp,sim,passport',
            'id_number' => 'nullable|string|max:50',
        ]);

        $customer->update($request->only(['name', 'phone', 'address', 'id_type', 'id_number']));

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $customer = Auth::guard('customer')->user();

        if (!Hash::check($request->current_password, $customer->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $customer->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    public function rentals()
    {
        $customer = Auth::guard('customer')->user();
        $rentals = $customer->rentals()->with(['items.productUnit.product'])->orderBy('created_at', 'desc')->paginate(10);

        return view('frontend.dashboard.rentals', compact('rentals'));
    }

    public function rentalDetail($id)
    {
        $customer = Auth::guard('customer')->user();
        $rental = $customer->rentals()
            ->with(['items.productUnit.product', 'items.rentalItemKits.unitKit', 'deliveries'])
            ->findOrFail($id);

        return view('frontend.dashboard.rental-detail', compact('rental'));
    }
}