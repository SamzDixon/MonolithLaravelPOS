<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the plain unique indexes — they block reuse of identifiers
        // after a soft delete, because MySQL does not filter by deleted_at.
        Schema::table('suppliers', function ($table) {
            $table->dropUnique('suppliers_name_unique');
            $table->dropUnique('suppliers_email_unique');
            $table->dropUnique('suppliers_phone_unique');
        });

        // Add shadow columns that hold the value only while the row is
        // active, and NULL once it's soft-deleted. A unique index on a
        // nullable column permits any number of NULLs, so soft-deleted
        // rows never collide — but only one active row can hold a value.
        DB::statement("
            ALTER TABLE suppliers
            ADD COLUMN name_active VARCHAR(150)
                GENERATED ALWAYS AS (IF(deleted_at IS NULL, name, NULL)) STORED
        ");
        DB::statement("
            ALTER TABLE suppliers
            ADD COLUMN email_active VARCHAR(150)
                GENERATED ALWAYS AS (IF(deleted_at IS NULL, email, NULL)) STORED
        ");
        DB::statement("
            ALTER TABLE suppliers
            ADD COLUMN phone_active VARCHAR(40)
                GENERATED ALWAYS AS (IF(deleted_at IS NULL, phone, NULL)) STORED
        ");

        DB::statement('CREATE UNIQUE INDEX suppliers_name_active_unique ON suppliers(name_active)');
        DB::statement('CREATE UNIQUE INDEX suppliers_email_active_unique ON suppliers(email_active)');
        DB::statement('CREATE UNIQUE INDEX suppliers_phone_active_unique ON suppliers(phone_active)');
    }

    public function down(): void
    {
        Schema::table('suppliers', function ($table) {
            $table->dropUnique('suppliers_name_active_unique');
            $table->dropUnique('suppliers_email_active_unique');
            $table->dropUnique('suppliers_phone_active_unique');
        });

        DB::statement('ALTER TABLE suppliers DROP COLUMN name_active');
        DB::statement('ALTER TABLE suppliers DROP COLUMN email_active');
        DB::statement('ALTER TABLE suppliers DROP COLUMN phone_active');

        Schema::table('suppliers', function ($table) {
            $table->unique('name', 'suppliers_name_unique');
            $table->unique('email', 'suppliers_email_unique');
            $table->unique('phone', 'suppliers_phone_unique');
        });
    }
};