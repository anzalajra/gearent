<?php

namespace App\Http\Controllers;

use App\Models\CustomerDocument;
use App\Models\DocumentType;
use App\Services\Storage\TenantStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CustomerDocumentController extends Controller
{
    protected TenantStorageService $storage;

    public function __construct(TenantStorageService $storage)
    {
        $this->storage = $storage;
    }

    public function upload(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
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
                $this->storage->delete($existing->file_path);
                $existing->delete();
            }

            // Store new file in R2 with tenant prefix
            $directory = 'customer-documents/' . $customer->id;
            $path = $this->storage->store($file, $directory);
            
            // Store relative path (without tenant prefix) for database
            $relativePath = $directory . '/' . basename($path);

            CustomerDocument::create([
                'user_id' => $customer->id,
                'document_type_id' => $typeId,
                'file_path' => $relativePath,
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

        if ($document->user_id != $customer->id) {
            abort(403);
        }

        // Only allow delete if not approved
        if ($document->status === CustomerDocument::STATUS_APPROVED) {
            return back()->with('error', 'Cannot delete approved document.');
        }

        $this->storage->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document deleted.');
    }

    public function view(CustomerDocument $document)
    {
        // Check if the current user is the owner of the document
        if (Auth::guard('customer')->id() != $document->user_id) {
            abort(403);
        }

        if (!$this->storage->exists($document->file_path)) {
            abort(404);
        }

        // Generate temporary URL for viewing
        $url = $this->storage->temporaryUrl($document->file_path, now()->addMinutes(5));
        return redirect($url);
    }

    public function viewForAdmin(CustomerDocument $document)
    {
        // Check if the current user is an admin (authenticated via default guard)
        if (!Auth::check()) {
            abort(403);
        }

        if (!$this->storage->exists($document->file_path)) {
            abort(404);
        }

        // Generate temporary URL for viewing
        $url = $this->storage->temporaryUrl($document->file_path, now()->addMinutes(5));
        return redirect($url);
    }
}
