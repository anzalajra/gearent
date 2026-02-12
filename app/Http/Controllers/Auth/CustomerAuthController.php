<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class CustomerAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('frontend.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('customer')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('customer.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegistrationForm()
    {
        if (!Setting::get('registration_open', true)) {
            return redirect()->route('customer.login')->with('error', 'Pendaftaran anggota baru sedang ditutup.');
        }

        $categories = \App\Models\CustomerCategory::where('is_active', true)->get();
        $customFields = json_decode(Setting::get('registration_custom_fields', '[]'), true);
        $defaultCategoryId = Setting::get('default_customer_category_id');
        
        return view('frontend.auth.register', compact('categories', 'customFields', 'defaultCategoryId'));
    }

    public function register(Request $request)
    {
        if (!Setting::get('registration_open', true)) {
            return back()->with('error', 'Pendaftaran anggota baru sedang ditutup.');
        }

        // Base Validation
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'customer_category_id' => 'required|exists:customer_categories,id',
        ];

        // Custom Fields Validation
        $customFields = json_decode(Setting::get('registration_custom_fields', '[]'), true);
        $customData = [];
        $selectedCategory = $request->customer_category_id;

        if (!empty($customFields)) {
            foreach ($customFields as $field) {
                // Check visibility
                $visibleCategories = $field['visible_for_categories'] ?? [];
                if (!empty($visibleCategories) && !in_array($selectedCategory, $visibleCategories)) {
                    continue; // Skip validation if not visible
                }

                $fieldName = 'custom_' . $field['name']; // Prefix to avoid conflict
                $rules[$fieldName] = $field['required'] ? 'required' : 'nullable';
                
                if ($field['type'] === 'number') {
                    $rules[$fieldName] .= '|numeric';
                }
                if ($field['type'] === 'email') {
                    $rules[$fieldName] .= '|email';
                }
                
                // Collect data if present
                if ($request->has($fieldName)) {
                    $customData[$field['name']] = $request->input($fieldName);
                }
            }
        }

        $request->validate($rules);

        $autoVerify = Setting::get('auto_verify_registration', true);

        $customer = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'customer_category_id' => $request->customer_category_id,
            'email_verified_at' => $autoVerify ? now() : null,
            'is_verified' => $autoVerify,
            'verified_at' => $autoVerify ? now() : null,
            'custom_fields' => !empty($customData) ? $customData : null,
        ]);

        Auth::guard('customer')->login($customer);

        return redirect(route('customer.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}