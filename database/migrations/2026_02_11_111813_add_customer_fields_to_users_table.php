<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add columns to users
        if (!Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone')->nullable()->after('email');
                $table->string('nik')->nullable()->after('phone');
                $table->text('address')->nullable()->after('nik');
                $table->foreignId('customer_category_id')->nullable()->after('address')->constrained('customer_categories')->nullOnDelete();
                $table->boolean('is_verified')->default(false)->after('email_verified_at');
                $table->timestamp('verified_at')->nullable()->after('is_verified');
                $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
                $table->json('custom_fields')->nullable()->after('verified_by');
            });
        }

        // 2. Migrate Data
        $customers = DB::table('customers')->get();
        $map = []; // customer_id => user_id

        foreach ($customers as $customer) {
            $user = User::where('email', $customer->email)->first();
            if ($user) {
                // Update existing user
                DB::table('users')->where('id', $user->id)->update([
                    'phone' => $customer->phone,
                    'nik' => $customer->nik,
                    'address' => $customer->address,
                    'customer_category_id' => $customer->customer_category_id,
                    'is_verified' => $customer->is_verified,
                    'verified_at' => $customer->verified_at,
                    'verified_by' => $customer->verified_by,
                    'custom_fields' => $customer->custom_fields,
                ]);
                $map[$customer->id] = $user->id;
            } else {
                // Create new user
                $userId = DB::table('users')->insertGetId([
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'password' => $customer->password ?? Hash::make(Str::random(32)),
                    'email_verified_at' => $customer->email_verified_at,
                    'phone' => $customer->phone,
                    'nik' => $customer->nik,
                    'address' => $customer->address,
                    'customer_category_id' => $customer->customer_category_id,
                    'is_verified' => $customer->is_verified,
                    'verified_at' => $customer->verified_at,
                    'verified_by' => $customer->verified_by,
                    'custom_fields' => $customer->custom_fields,
                    'created_at' => $customer->created_at,
                    'updated_at' => $customer->updated_at,
                ]);
                $map[$customer->id] = $userId;
            }
        }

        // 3. Update related tables
        $tables = ['rentals', 'carts', 'quotations', 'customer_documents'];
        $validCustomerIds = array_keys($map);

        foreach ($tables as $tableName) {
            if (Schema::hasColumn($tableName, 'user_id')) {
                // Already renamed. Clean invalid IDs and ensure FK.
                // This handles failed previous runs where column was renamed but FK failed.
                DB::delete("DELETE FROM {$tableName} WHERE user_id NOT IN (SELECT id FROM users)");
                
                try {
                    Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                        $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                        if ($tableName === 'customer_documents') {
                             // Use a short name for unique index if needed, or let Laravel generate it
                             // But checking if index exists is hard. Just try-catch.
                            $table->unique(['user_id', 'document_type_id']);
                        }
                    });
                } catch (\Throwable $e) {
                    // Ignore if FK or Unique exists
                }
                continue;
            }

            // Clean orphans before processing
            DB::table($tableName)->whereNotIn('customer_id', $validCustomerIds)->delete();

            // Drop FK
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                // Try to catch exception if FK doesn't exist (or just assume standard naming)
                // Standard naming: table_column_foreign
                try {
                    $table->dropForeign([ 'customer_id' ]); 
                } catch (\Throwable $e) {}
                
                if ($tableName === 'customer_documents') {
                    try {
                        $table->dropUnique(['customer_id', 'document_type_id']);
                    } catch (\Throwable $e) {}
                }
            });

            // Update IDs
            foreach ($map as $custId => $userId) {
                DB::table($tableName)->where('customer_id', $custId)->update(['customer_id' => $userId]);
            }

            // Rename column
            Schema::table($tableName, function (Blueprint $table) {
                $table->renameColumn('customer_id', 'user_id');
            });
            
            // Add new FK and constraints
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                
                if ($tableName === 'customer_documents') {
                    $table->unique(['user_id', 'document_type_id']);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting this is complex and risky due to data merge.
        // We will only drop columns from users for now.
        // Restoration of customers table and splitting data is not implemented.
        
        $tables = ['rentals', 'carts', 'quotations', 'customer_documents'];
        foreach ($tables as $tableName) {
             Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropForeign(['user_id']);
                if ($tableName === 'customer_documents') {
                    $table->dropUnique(['user_id', 'document_type_id']);
                }
                $table->renameColumn('user_id', 'customer_id');
                // Note: FK to customers cannot be restored easily as customers table might be empty or IDs mismatch
                if ($tableName === 'customer_documents') {
                    $table->unique(['customer_id', 'document_type_id']);
                }
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['customer_category_id']);
            $table->dropForeign(['verified_by']);
            $table->dropColumn([
                'phone', 
                'nik', 
                'address', 
                'customer_category_id', 
                'is_verified', 
                'verified_at', 
                'verified_by', 
                'custom_fields'
            ]);
        });
    }
};
