<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
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
        $verificationStatus = $customer->getVerificationStatus();

        return view('frontend.dashboard.index', compact('customer', 'activeRentals', 'pastRentals', 'cartCount', 'verificationStatus'));
    }

    public function profile()
    {
        $customer = Auth::guard('customer')->user();
        $documentTypes = DocumentType::getActiveTypes();
        $uploadedDocuments = $customer->documents()->with('documentType')->get()->keyBy('document_type_id');
        $verificationStatus = $customer->getVerificationStatus();

        return view('frontend.dashboard.profile', compact('customer', 'documentTypes', 'uploadedDocuments', 'verificationStatus'));
    }

    public function updateProfile(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'nik' => 'required|string|size:16',
            'address' => 'nullable|string',
        ]);

        $customer->update($request->only(['name', 'phone', 'nik', 'address']));

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