<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RegisterTabbyWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tabby:register-webhook
                            {--url= : The webhook URL (defaults to APP_URL/webhooks/tabby)}
                            {--generate-signature : Generate a random signature value}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Register webhook with Tabby API";

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🔗 Registering Tabby Webhook...");
        $this->newLine();

        // Get configuration
        $baseUrl = config("services.tabby.base_url");
        $secretKey = config("services.tabby.secret_key");
        $merchantCode = config("services.tabby.merchant_code");

        if (!$secretKey || !$merchantCode) {
            $this->error(
                "❌ Missing Tabby configuration. Please set TABBY_SECRET_KEY and TABBY_MERCHANT_CODE in .env",
            );
            return 1;
        }

        // Get or generate webhook URL
        $webhookUrl =
            $this->option("url") ?? config("app.url") . "/webhooks/tabby";

        $this->info("📍 Webhook URL: {$webhookUrl}");
        $this->newLine();

        // Generate or get signature
        $signatureHeader = "X-Tabby-Signature";
        $signatureValue = $this->option("generate-signature")
            ? Str::random(64)
            : config("services.tabby.webhook_signature_value");

        if (!$signatureValue) {
            $this->warn(
                "⚠️  No signature configured. Generating random signature...",
            );
            $signatureValue = Str::random(64);
        }

        // Prepare payload
        $payload = [
            "url" => $webhookUrl,
            "header" => [
                "title" => $signatureHeader,
                "value" => $signatureValue,
            ],
        ];

        // Make API request
        $this->info("📤 Sending registration request to Tabby...");

        try {
            $response = Http::withHeaders([
                "Authorization" => "Bearer " . $secretKey,
                "Content-Type" => "application/json",
                "X-Merchant-Code" => $merchantCode,
            ])->post($baseUrl . "/api/v1/webhooks", $payload);

            if ($response->successful()) {
                $data = $response->json();

                $this->newLine();
                $this->info("✅ Webhook registered successfully!");
                $this->newLine();

                $this->line("📋 Webhook Details:");
                $this->table(
                    ["Key", "Value"],
                    [
                        ["Webhook ID", $data["id"] ?? "N/A"],
                        ["URL", $data["url"] ?? $webhookUrl],
                        ["Is Test", $data["is_test"] ?? false ? "Yes" : "No"],
                        ["Signature Header", $signatureHeader],
                    ],
                );

                $this->newLine();
                $this->warn(
                    "⚠️  IMPORTANT: Add the following to your .env file:",
                );
                $this->newLine();
                $this->line(
                    "TABBY_WEBHOOK_SIGNATURE_HEADER=" . $signatureHeader,
                );
                $this->line("TABBY_WEBHOOK_SIGNATURE_VALUE=" . $signatureValue);
                $this->newLine();

                $this->info("Then run: php artisan config:clear");

                return 0;
            } else {
                $this->error("❌ Failed to register webhook");
                $this->error("Status: " . $response->status());
                $this->error("Response: " . $response->body());
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ Exception occurred: " . $e->getMessage());
            return 1;
        }
    }
}
