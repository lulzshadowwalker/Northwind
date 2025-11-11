<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table("orders", function (Blueprint $table) {
            // Shipping address fields
            $table->string("shipping_address")->nullable();
            $table->string("shipping_city")->nullable();
            $table->string("shipping_state")->nullable();
            $table->string("shipping_zip")->nullable();
            $table->string("shipping_country")->default("SA");

            // Billing address fields
            $table->string("billing_address")->nullable();
            $table->string("billing_city")->nullable();
            $table->string("billing_state")->nullable();
            $table->string("billing_zip")->nullable();
            $table->string("billing_country")->default("SA");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("orders", function (Blueprint $table) {
            $table->dropColumn([
                "shipping_address",
                "shipping_city",
                "shipping_state",
                "shipping_zip",
                "shipping_country",
                "billing_address",
                "billing_city",
                "billing_state",
                "billing_zip",
                "billing_country",
            ]);
        });
    }
};
