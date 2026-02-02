@extends('layouts.frontend')

@section('title', 'Profile')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold mb-8">Profile Settings</h1>

    <!-- Profile Form -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Personal Information</h2>
        
        <form action="{{ route('customer.profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="name" value="{{ old('name', $customer->name) }}" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" value="{{ $customer->email }}" disabled class="w-full border rounded-lg px-3 py-2 bg-gray-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ID Type</label>
                    <select name="id_type" class="w-full border rounded-lg px-3 py-2">
                        <option value="">Select ID Type</option>
                        <option value="ktp" {{ old('id_type', $customer->id_type) == 'ktp' ? 'selected' : '' }}>KTP</option>
                        <option value="sim" {{ old('id_type', $customer->id_type) == 'sim' ? 'selected' : '' }}>SIM</option>
                        <option value="passport" {{ old('id_type', $customer->id_type) == 'passport' ? 'selected' : '' }}>Passport</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ID Number</label>
                    <input type="text" name="id_number" value="{{ old('id_number', $customer->id_number) }}" class="w-full border rounded-lg px-3 py-2">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea name="address" rows="3" class="w-full border rounded-lg px-3 py-2">{{ old('address', $customer->address) }}</textarea>
            </div>

            <button type="submit" class="bg-primary-600 text-white px-6 py-2 rounded-lg hover:bg-primary-700">
                Save Changes
            </button>
        </form>
    </div>

    <!-- Password Form -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Change Password</h2>
        
        <form action="{{ route('customer.password.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="space-y-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                    <input type="password" name="current_password" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <input type="password" name="password" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                    <input type="password" name="password_confirmation" required class="w-full border rounded-lg px-3 py-2">
                </div>
            </div>

            <button type="submit" class="bg-primary-600 text-white px-6 py-2 rounded-lg hover:bg-primary-700">
                Update Password
            </button>
        </form>
    </div>
</div>
@endsection