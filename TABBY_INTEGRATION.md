# Tabby Payment Integration Summary

## Overview
This document outlines the custom integration of the Tabby Payment Gateway into the Aura application. The integration allows customers to pay in installments (Split in 4).

## Core Files
- **Service Logic:** `app/Services/TabbyPaymentGatewayService.php`
  - Handles API communication (Session creation, Capture, Refund).
- **Webhook Handling:** `app/Http/Controllers/TabbyWebhookController.php`
  - Receives asynchronous updates from Tabby (Authorized, Captured, etc.).
- **Checkout Logic:** `app/Http/Controllers/CheckoutController.php`
  - Checks eligibility before showing the payment option.
- **Configuration:** `config/services.php` (contains `tabby` keys).

## Integration Flow

### 1. Eligibility Check (Pre-scoring)
**File:** `app/Services/TabbyPaymentGatewayService.php` -> `checkEligibility`
Before the user selects Tabby, we send the cart total and user details to Tabby to see if they qualify.
- **Endpoint:** `POST /api/v2/checkout` (without creating a session)
- **Response:** `created` (Eligible) or `rejected`.

### 2. Session Creation
**File:** `app/Services/TabbyPaymentGatewayService.php` -> `start`
When the user clicks "Place Order":
1. We send full order details (Items, Tax, Shipping, Buyer info) to Tabby.
2. Tabby returns a `web_url`.
3. We redirect the user to this URL.

### 3. Payment Authorization
The user completes the process on Tabby's page.
- **Success:** User is redirected to our `success` URL.
- **Cancel/Failure:** User is redirected to our `cancel` or `failure` URL.

### 4. Webhook & Capture (Critical Step)
**File:** `app/Http/Controllers/TabbyWebhookController.php`
Even if the user doesn't return to our site, Tabby sends a server-to-server webhook.
1. **Event:** Tabby sends `status: authorized`.
2. **Action:** Our system receives this, verifies the payment ID via API.
3. **Capture:** We must send a `POST /captures` request to finalize the transaction. **Crucial:** The amount captured must match the authorized amount exactly.

## Troubleshooting
- **419 Errors on Webhook:** Ensure the webhook route is excluded from CSRF protection in `bootstrap/app.php` or `VerifyCsrfToken` middleware.
- **Capture Failures:** Usually due to amount mismatch (Local DB total != Tabby Authorized total).
