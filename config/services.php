<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    "postmark" => [
        "token" => env("POSTMARK_TOKEN"),
    ],

    "resend" => [
        "key" => env("RESEND_KEY"),
    ],

    "ses" => [
        "key" => env("AWS_ACCESS_KEY_ID"),
        "secret" => env("AWS_SECRET_ACCESS_KEY"),
        "region" => env("AWS_DEFAULT_REGION", "us-east-1"),
    ],

    "slack" => [
        "notifications" => [
            "bot_user_oauth_token" => env("SLACK_BOT_USER_OAUTH_TOKEN"),
            "channel" => env("SLACK_BOT_USER_DEFAULT_CHANNEL"),
        ],
    ],

    "google" => [
        "client_id" => env("GOOGLE_CLIENT_ID"),
        "client_secret" => env("GOOGLE_CLIENT_SECRET"),
        "redirect" => env("GOOGLE_REDIRECT_URI"),
    ],

    "myfatoorah" => [
        "api_key" => env("MYFATOORAH_API_KEY"),
        "vc_code" => env("MYFATOORAH_VC_CODE"),
        "is_test" => env(
            "MYFATOORAH_IS_TEST",
            config("app.env") !== "production",
        ),
    ],

    "tabby" => [
        "base_url" => env("TABBY_API_BASE_URL", "https://api.tabby.ai"),
        "secret_key" => env("TABBY_SECRET_KEY"),
        "public_key" => env("TABBY_PUBLIC_KEY"),
        "merchant_code" => env("TABBY_MERCHANT_CODE", "NWSA"),
        "is_test" => env("TABBY_IS_TEST", config("app.env") !== "production"),
    ],
];
