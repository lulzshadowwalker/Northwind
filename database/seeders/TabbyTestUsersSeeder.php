<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TabbyTestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Tabby test users with unique phone numbers to avoid constraints
        $users = [
            [
                "name" => "Tabby Success User",
                "email" => "otp.success@tabby.ai",
                "phone" => "+966500000100", // Unique phone for DB
                "password" => Hash::make("password"),
                "is_admin" => false,
            ],
            [
                "name" => "Tabby Rejected User",
                "email" => "otp.rejected@tabby.ai",
                "phone" => "+966500000200", // Unique phone for DB
                "password" => Hash::make("password"),
                "is_admin" => false,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ["email" => $userData["email"]],
                $userData,
            );

            // Create customer if not exists
            if (!$user->customer) {
                Customer::create([
                    "user_id" => $user->id,
                ]);
            }
        }
    }
}
