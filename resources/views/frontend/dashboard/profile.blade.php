@extends('layouts.frontend')

@section('title', 'Profile')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold mb-8">Profile & Verification</h1>

    <!-- Verification Status Card -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">Status Verifikasi</h2>
                <p class="text-sm text-gray-600 mt-1">
                    @if($verificationStatus === 'verified')
                        Akun Anda sudah terverifikasi dan dapat melakukan rental.
                    @elseif($verificationStatus === 'pending')
                        Dokumen Anda sedang ditinjau oleh admin.
                    @else
                        Lengkapi dokumen yang diperlukan untuk dapat melakukan rental.
                    @endif
                </p>
            </div>
            <span class="px-4 py-2 rounded-full text-sm font-semibold
                @if($verificationStatus === 'verified') bg-green-100 text-green-800
                @elseif($verificationStatus === 'pending') bg-yellow-100 text-yellow-800
                @else bg-red-100 text-red-800 @endif">
                {{ $customer->getVerificationStatusLabel() }}
            </span>
        </div>
    </div>

    <!-- Profile Form -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Informasi Pribadi</h2>
        
        <form action="{{ route('customer.profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $customer->name) }}" required class="w-full border rounded-lg px-3 py-2">
                    @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" value="{{ $customer->email }}" disabled class="w-full border rounded-lg px-3 py-2 bg-gray-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">No. Telepon <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" required class="w-full border rounded-lg px-3 py-2">
                    @error('phone') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NIK (No. KTP) <span class="text-red-500">*</span></label>
                    <input type="text" name="nik" value="{{ old('nik', $customer->nik) }}" required maxlength="16" minlength="16" placeholder="16 digit NIK" class="w-full border rounded-lg px-3 py-2">
                    @error('nik') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                <textarea name="address" rows="3" class="w-full border rounded-lg px-3 py-2">{{ old('address', $customer->address) }}</textarea>
            </div>

            <button type="submit" class="bg-primary-600 text-white px-6 py-2 rounded-lg hover:bg-primary-700">
                Simpan Perubahan
            </button>
        </form>
    </div>

    <!-- Document Upload -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Dokumen Verifikasi</h2>
        <p class="text-sm text-gray-600 mb-6">Upload dokumen yang diperlukan untuk verifikasi akun. Format: JPG, PNG, PDF. Maksimal 500KB.</p>

        <div class="space-y-4">
            @foreach($documentTypes as $type)
                @php
                    $uploadedDoc = $uploadedDocuments->get($type->id);
                @endphp
                <div class="border rounded-lg p-4 @if($type->is_required) border-primary-300 bg-primary-50 @endif">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="font-medium">{{ $type->name }}</h3>
                                @if($type->is_required)
                                    <span class="px-2 py-0.5 bg-primary-100 text-primary-700 text-xs rounded">Wajib</span>
                                @endif
                            </div>
                            @if($type->description)
                                <p class="text-sm text-gray-500 mt-1">{{ $type->description }}</p>
                            @endif

                            @if($uploadedDoc)
                                <div class="mt-3 flex items-center gap-4">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <span class="text-sm">{{ $uploadedDoc->file_name }}</span>
                                        <span class="text-xs text-gray-400">({{ $uploadedDoc->getFileSizeFormatted() }})</span>
                                    </div>
                                    <span class="px-2 py-1 rounded text-xs font-medium
                                        @if($uploadedDoc->status === 'approved') bg-green-100 text-green-700
                                        @elseif($uploadedDoc->status === 'rejected') bg-red-100 text-red-700
                                        @else bg-yellow-100 text-yellow-700 @endif">
                                        @if($uploadedDoc->status === 'approved') ✓ Disetujui
                                        @elseif($uploadedDoc->status === 'rejected') ✗ Ditolak
                                        @else ⏳ Menunggu Review @endif
                                    </span>
                                </div>

                                @if($uploadedDoc->status === 'rejected' && $uploadedDoc->rejection_reason)
                                    <p class="mt-2 text-sm text-red-600">Alasan: {{ $uploadedDoc->rejection_reason }}</p>
                                @endif
                            @endif
                        </div>

                        <div class="ml-4">
                            @if($uploadedDoc)
                                <div class="flex items-center gap-2">
                                    <a href="{{ Storage::url($uploadedDoc->file_path) }}" target="_blank" class="text-primary-600 hover:underline text-sm">Lihat</a>
                                    @if($uploadedDoc->status !== 'approved')
                                        <form action="{{ route('customer.documents.delete', $uploadedDoc) }}" method="POST" class="inline" onsubmit="return confirm('Hapus dokumen ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline text-sm">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    @if(!$uploadedDoc || $uploadedDoc->status === 'rejected')
                        <form action="{{ route('customer.documents.upload') }}" method="POST" enctype="multipart/form-data" class="mt-4">
                            @csrf
                            <input type="hidden" name="document_type_id" value="{{ $type->id }}">
                            <div class="flex items-center gap-3">
                                <input type="file" name="file" accept=".jpg,.jpeg,.png,.pdf" required class="text-sm">
                                <button type="submit" class="bg-primary-600 text-white px-4 py-1.5 rounded text-sm hover:bg-primary-700">
                                    Upload
                                </button>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">JPG, PNG, PDF - Max 500KB</p>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Password Form -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Ubah Password</h2>
        
        <form action="{{ route('customer.password.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="space-y-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Saat Ini</label>
                    <input type="password" name="current_password" required class="w-full border rounded-lg px-3 py-2">
                    @error('current_password') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                    <input type="password" name="password" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation" required class="w-full border rounded-lg px-3 py-2">
                </div>
            </div>

            <button type="submit" class="bg-primary-600 text-white px-6 py-2 rounded-lg hover:bg-primary-700">
                Ubah Password
            </button>
        </form>
    </div>
</div>
@endsection