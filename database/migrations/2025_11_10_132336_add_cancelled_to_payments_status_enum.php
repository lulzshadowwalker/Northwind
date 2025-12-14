<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'cancelled' to the payments status enum
        DB::statement(
            "ALTER TABLE payments MODIFY COLUMN status ENUM('pending', 'paid', 'failed', 'cancelled') NOT NULL",
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'cancelled' from the payments status enum
        DB::statement(
            "ALTER TABLE payments MODIFY COLUMN status ENUM('pending', 'paid', 'failed') NOT NULL",
        );
    }
};
