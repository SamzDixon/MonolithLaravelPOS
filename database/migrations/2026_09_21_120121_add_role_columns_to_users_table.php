<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('store_manager')->after('email');
            $table->foreignId('branch_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->foreignId('store_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('store_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
