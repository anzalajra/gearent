<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\DocumentType;
use App\Models\CustomerCategory;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $documentTypes = DocumentType::all();
        $customerCategories = CustomerCategory::all();

        foreach ($documentTypes as $documentType) {
            $documentType->customerCategories()->sync($customerCategories->pluck('id'));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse data seeding usually, or we can truncate the table
        // DB::table('customer_category_document_type')->truncate();
    }
};
