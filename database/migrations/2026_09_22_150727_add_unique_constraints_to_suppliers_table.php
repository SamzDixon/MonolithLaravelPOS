<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Name unique was already declared in create_suppliers_table;
            try {
                $table->unique('email', 'suppliers_email_unique');
            } catch (\Throwable $e) {
                // Already exists.
            }

            try {
                $table->unique('phone', 'suppliers_phone_unique');
            } catch (\Throwable $e) {
                // Already exists.
            }
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