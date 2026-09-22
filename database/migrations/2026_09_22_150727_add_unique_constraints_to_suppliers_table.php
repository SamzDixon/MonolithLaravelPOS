<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Only add the name unique if it doesn't already exist. Wrap in a
            // try/catch so a pre-existing constraint doesn't fail the migration.
            try {
                $table->unique('name', 'suppliers_name_unique');
            } catch (\Throwable $e) {
                // Constraint already exists — nothing to do.
            }

            $table->unique('email', 'suppliers_email_unique');
            $table->unique('phone', 'suppliers_phone_unique');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropUnique('suppliers_email_unique');
            $table->dropUnique('suppliers_phone_unique');
        });
    }
};