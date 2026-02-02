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
            'document_type_id' => 'required|exists:document_types,id',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:512', // 500KB = 512KB max
        ]);

        $customer = Auth::guard('customer')->user();
        $file = $request->file('file');

        // Check if already uploaded
        $existing = $customer->documents()
            ->where('document_type_id', $request->document_type_id)
            ->first();

        // Delete old file if exists
        if ($existing) {
            Storage::delete($existing->file_path);
            $existing->delete();
        }

        // Store new file
        $path = $file->store('customer-documents/' . $customer->id, 'public');

        CustomerDocument::create([
            'customer_id' => $customer->id,
            'document_type_id' => $request->document_type_id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'status' => CustomerDocument::STATUS_PENDING,
        ]);

        return back()->with('success', 'Document uploaded successfully. Waiting for verification.');
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