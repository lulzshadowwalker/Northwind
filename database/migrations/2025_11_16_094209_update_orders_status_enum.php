<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update the enum to include both old and new values
        DB::statement(
            "ALTER TABLE orders MODIFY COLUMN status ENUM('yes','no','" .
                implode("','", OrderStatus::values()) .
                "')",
        );

        // Then update all existing orders to 'unknown' status
        DB::table("orders")->update(["status" => "unknown"]);

        // Finally, update the enum to only include the new Tabby-compliant values
        DB::statement(
            "ALTER TABLE orders MODIFY COLUMN status ENUM('" .
                implode("','", OrderStatus::values()) .
                "')",
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to old yes/no enum
        DB::statement(
            "ALTER TABLE orders MODIFY COLUMN status ENUM('yes','no')",
        );
    }
};
