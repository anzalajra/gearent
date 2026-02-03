<?php

namespace App\Http\Controllers;

use App\Models\CustomerDocument;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CustomerDocumentController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:512',
        ]);

        $customer = Auth::guard('customer')->user();
        $files = array_filter($request->file('files') ?? []);

        if (empty($files)) {
            return back()->with('error', 'Silakan pilih setidaknya satu dokumen untuk diunggah.');
        }

        $uploadedCount = 0;
        foreach ($files as $typeId => $file) {
            if (!$file->isValid()) continue;

            // Check if document type exists
            if (!DocumentType::find($typeId)) continue;

            // Check if already uploaded
            $existing = $customer->documents()
                ->where('document_type_id', $typeId)
                ->first();

            // Delete old file if exists (only if not approved)
            if ($existing) {
                if ($existing->status === CustomerDocument::STATUS_APPROVED) {
                    continue;
                }
                Storage::delete($existing->file_path);
                $existing->delete();
            }

            // Store new file
            $path = $file->store('customer-documents/' . $customer->id, 'public');

            CustomerDocument::create([
                'customer_id' => $customer->id,
                'document_type_id' => $typeId,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'status' => CustomerDocument::STATUS_PENDING,
            ]);

            $uploadedCount++;
        }

        if ($uploadedCount > 0) {
            return back()->with('success', "$uploadedCount dokumen berhasil diunggah. Menunggu verifikasi.");
        }

        return back()->with('info', 'Tidak ada dokumen baru yang diunggah.');
    }

    public function delete(CustomerDocument $document)
    {
        $customer = Auth::guard('customer')->user();

        if ($document->customer_id !== $customer->id) {
            abort(403);
        }

        // Only allow delete if not approved
        if ($document->status === CustomerDocument::STATUS_APPROVED) {
            return back()->with('error', 'Cannot delete approved document.');
        }

        Storage::delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document deleted.');
    }
}