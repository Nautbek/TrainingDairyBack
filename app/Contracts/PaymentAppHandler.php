<?php

namespace App\Contracts;

use App\Models\DonationPayment;

/**
 * Implemented by any per-app module (MyCar, TripSplit, ...) that owns its own
 * paid-tier flow on top of the shared DonationPayment ledger.
 *
 * The core payment/webhook code never references a module's service class
 * directly — it only talks to this contract, resolved through the
 * PaymentHandlerRegistry. That is what lets a module folder be deleted
 * without leaving broken references in the core app.
 */
interface PaymentAppHandler
{
    /**
     * Whether this handler owns the given payment (matched by DonationPayment::app).
     */
    public function supports(DonationPayment $payment): bool;

    /**
     * Handle a YooKassa webhook payload for a payment this handler supports.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload): void;
}
