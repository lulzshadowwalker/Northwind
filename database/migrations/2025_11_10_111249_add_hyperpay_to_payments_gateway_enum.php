<?php

use App\Enums\PaymentGateway;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the enum to include hyperpay
        DB::statement(
            "ALTER TABLE payments MODIFY COLUMN gateway ENUM('".
                implode("','", PaymentGateway::values()).
                "')",
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to exclude hyperpay (myfatoorah and tabby only)
        DB::statement(
            "ALTER TABLE payments MODIFY COLUMN gateway ENUM('myfatoorah','tabby')",
        );
    }
};
