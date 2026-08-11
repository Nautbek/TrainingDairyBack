<?php

namespace App\Services\Payment;

use App\Contracts\PaymentAppHandler;
use App\Models\DonationPayment;

/**
 * Central lookup for module payment handlers, filled by tagging module
 * services with the 'payment.app.handlers' container tag in each module's
 * own service provider (see Modules/*\/Providers).
 *
 * Core code depends only on this registry + the PaymentAppHandler contract,
 * never on a specific module's classes — deleting a module folder (plus its
 * provider registration) simply removes it from the loop below.
 */
class PaymentHandlerRegistry
{
    /**
     * @param  iterable<PaymentAppHandler>  $handlers
     */
    public function __construct(private readonly iterable $handlers) {}

    public function findFor(?DonationPayment $payment): ?PaymentAppHandler
    {
        if ($payment === null) {
            return null;
        }

        foreach ($this->handlers as $handler) {
            if ($handler->supports($payment)) {
                return $handler;
            }
        }

        return null;
    }
}
