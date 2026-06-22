<?php

namespace App\Services\TripSplit;

class TripSplitSettlementCalculator
{
    private const RUB = 'RUB';

    /**
     * Сводит поездку по фактическим оплатам (payer_payments).
     *
     * Доли вводятся по сумме чека и задают долю потребления. При расчёте:
     * 1. Σ оплат → рубли;
     * 2. доли → рубли, коэффициент_i = доля_i / Σ долей;
     * 3. потребление_i = коэффициент_i × Σ оплат (в ₽).
     *
     * Сумма чека (amount) в расчёте не участвует — только доли и фактические оплаты.
     *
     * @param  array<string, mixed>  $trip
     * @return array<string, mixed>
     */
    public function calculate(array $trip): array
    {
        $participants = $this->indexParticipants($trip['participants'] ?? []);
        $rates = $this->buildRates($trip['currencies'] ?? []);

        $paidByCurrency = [];
        $owesRubByParticipant = [];
        $owesByCurrency = [];
        $transactionDetails = [];
        foreach (array_keys($participants) as $participantId) {
            $paidByCurrency[$participantId] = [];
            $owesRubByParticipant[$participantId] = 0.0;
            $owesByCurrency[$participantId] = [];
        }

        foreach ($trip['transactions'] ?? [] as $transaction) {
            $breakdown = $this->breakDownTransaction($transaction, $participants, $rates);
            $transactionDetails[] = $breakdown;

            foreach ($breakdown['consumption'] as $row) {
                $participantId = (int) $row['participant_id'];
                $owesRubByParticipant[$participantId] += (float) $row['consumption_rub'];
                $txCurrency = $breakdown['currency_code'];
                $txRate = $rates[$txCurrency] ?? null;
                if ($txRate !== null && $txRate > 0) {
                    $owesByCurrency[$participantId][$txCurrency] =
                        ($owesByCurrency[$participantId][$txCurrency] ?? 0.0)
                        + (float) $row['consumption_rub'] / $txRate;
                }
            }

            foreach ($breakdown['payer_payments'] as $payment) {
                $participantId = (int) $payment['participant_id'];
                $currency = strtoupper((string) $payment['currency_code']);
                $amount = (float) $payment['amount'];
                $paidByCurrency[$participantId][$currency] =
                    ($paidByCurrency[$participantId][$currency] ?? 0.0) + $amount;
            }
        }

        $participantSummaries = [];
        $balanceSum = 0.0;
        foreach ($participants as $participantId => $name) {
            $paidRub = $this->sumInRub($paidByCurrency[$participantId] ?? [], $rates);
            $owesRub = $owesRubByParticipant[$participantId];
            $balanceRub = round($paidRub - $owesRub, 2);
            $balanceSum += $balanceRub;

            $participantSummaries[] = [
                'id' => $participantId,
                'name' => $name,
                'paid_rub' => round($paidRub, 2),
                'owes_rub' => round($owesRub, 2),
                'balance_rub' => $balanceRub,
                'paid_by_currency' => $this->roundCurrencyMap($paidByCurrency[$participantId] ?? []),
                'owes_by_currency' => $this->roundCurrencyMap($owesByCurrency[$participantId] ?? []),
            ];
        }

        $transfers = $this->buildTransfers($participantSummaries, $participants);

        return [
            'trip_name' => (string) ($trip['name'] ?? ''),
            'calculation_note' => 'Доли вводятся по сумме чека. Потребление участника = (его доля / Σ долей) × Σ фактических оплат (в ₽).',
            'participants' => $participantSummaries,
            'transactions' => $transactionDetails,
            'transfers' => $transfers,
            'books_balanced' => abs($balanceSum) < 0.05,
            'unsettled_rub' => abs($balanceSum) >= 0.05 ? round(abs($balanceSum), 2) : 0.0,
        ];
    }

    /**
     * @param  array<string, mixed>  $transaction
     * @param  array<int, string>  $participants
     * @param  array<string, float>  $rates
     * @return array<string, mixed>
     */
    private function breakDownTransaction(array $transaction, array $participants, array $rates): array
    {
        $txCurrency = strtoupper((string) ($transaction['currency_code'] ?? self::RUB));

        $payerPayments = $transaction['payer_payments'] ?? [];
        if ($payerPayments === []) {
            $payerPayments = [[
                'participant_id' => (int) ($transaction['payer_id'] ?? 0),
                'amount' => (float) ($transaction['amount'] ?? 0),
                'currency_code' => $txCurrency,
            ]];
        }

        $paidTotalRub = 0.0;
        $paidInReceiptCurrency = 0.0;
        foreach ($payerPayments as $payment) {
            $currency = strtoupper((string) ($payment['currency_code'] ?? $txCurrency));
            $amount = (float) $payment['amount'];
            $rate = $rates[$currency] ?? null;
            if ($rate !== null && $rate > 0) {
                $paidTotalRub += $amount * $rate;
            }
            $paidInReceiptCurrency += $this->convertAmount($amount, $currency, $txCurrency, $rates);
        }

        $shareRubByParticipant = [];
        $shareReceiptByParticipant = [];
        $sharesTotalRub = 0.0;
        $sharesTotalReceipt = 0.0;
        foreach ($transaction['shares'] ?? [] as $share) {
            $shareAmount = (float) $share['amount'];
            if ($shareAmount <= 0.001) {
                continue;
            }
            $participantId = (int) $share['participant_id'];
            $shareReceiptByParticipant[$participantId] =
                ($shareReceiptByParticipant[$participantId] ?? 0.0) + $shareAmount;
            $sharesTotalReceipt += $shareAmount;
            $shareRub = $this->convertAmount($shareAmount, $txCurrency, self::RUB, $rates);
            $shareRubByParticipant[$participantId] =
                ($shareRubByParticipant[$participantId] ?? 0.0) + $shareRub;
            $sharesTotalRub += $shareRub;
        }

        $consumption = [];
        if ($sharesTotalRub > 0.001 && $paidTotalRub > 0) {
            foreach ($shareRubByParticipant as $participantId => $shareRub) {
                $coef = $shareRub / $sharesTotalRub;
                $consumptionRub = $coef * $paidTotalRub;
                $txRate = $rates[$txCurrency] ?? null;
                $consumptionReceipt = ($txRate !== null && $txRate > 0)
                    ? $consumptionRub / $txRate
                    : 0.0;

                $consumption[] = [
                    'participant_id' => $participantId,
                    'name' => $participants[$participantId] ?? ('#'.$participantId),
                    'share_receipt' => round($shareReceiptByParticipant[$participantId] ?? 0.0, 2),
                    'share_percent' => round($coef * 100, 2),
                    'consumption_rub' => round($consumptionRub, 2),
                    'consumption_receipt' => round($consumptionReceipt, 2),
                ];
            }
        }

        usort($consumption, fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        $normalizedPayments = [];
        foreach ($payerPayments as $payment) {
            $normalizedPayments[] = [
                'participant_id' => (int) $payment['participant_id'],
                'name' => $participants[(int) $payment['participant_id']] ?? ('#'.($payment['participant_id'] ?? '?')),
                'amount' => round((float) $payment['amount'], 2),
                'currency_code' => strtoupper((string) ($payment['currency_code'] ?? $txCurrency)),
            ];
        }

        return [
            'id' => (int) ($transaction['id'] ?? 0),
            'description' => (string) ($transaction['description'] ?? ''),
            'receipt_amount' => round((float) ($transaction['amount'] ?? 0), 2),
            'currency_code' => $txCurrency,
            'shares_total_receipt' => round($sharesTotalReceipt, 2),
            'paid_total_rub' => round($paidTotalRub, 2),
            'paid_in_receipt_currency' => round($paidInReceiptCurrency, 2),
            'payer_payments' => $normalizedPayments,
            'consumption' => $consumption,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $participants
     * @return array<int, string>
     */
    private function indexParticipants(array $participants): array
    {
        $indexed = [];
        foreach ($participants as $participant) {
            $indexed[(int) $participant['id']] = (string) $participant['name'];
        }

        return $indexed;
    }

    /**
     * @param  array<int, array<string, mixed>>  $currencies
     * @return array<string, float>
     */
    private function buildRates(array $currencies): array
    {
        $rates = [self::RUB => 1.0];
        foreach ($currencies as $currency) {
            $code = strtoupper((string) ($currency['code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $rates[$code] = (float) ($currency['rate_to_rub'] ?? 1.0);
        }

        return $rates;
    }

    /**
     * @param  array<string, float>  $rates
     */
    private function convertAmount(float $amount, string $fromCode, string $toCode, array $rates): float
    {
        if ($amount <= 0) {
            return 0.0;
        }
        if ($fromCode === $toCode) {
            return $amount;
        }
        $fromRate = $rates[$fromCode] ?? null;
        $toRate = $rates[$toCode] ?? null;
        if ($fromRate === null || $toRate === null || $fromRate <= 0 || $toRate <= 0) {
            return 0.0;
        }

        return $amount * $fromRate / $toRate;
    }

    /**
     * @param  array<string, float>  $amounts
     * @param  array<string, float>  $rates
     */
    private function sumInRub(array $amounts, array $rates): float
    {
        $total = 0.0;
        foreach ($amounts as $currency => $amount) {
            $code = strtoupper((string) $currency);
            $rate = $rates[$code] ?? null;
            if ($rate === null || $rate <= 0) {
                continue;
            }
            $total += $amount * $rate;
        }

        return $total;
    }

    /**
     * @param  array<string, float>  $map
     * @return array<string, float>
     */
    private function roundCurrencyMap(array $map): array
    {
        $result = [];
        foreach ($map as $currency => $amount) {
            if (abs($amount) < 0.005) {
                continue;
            }
            $result[strtoupper((string) $currency)] = round($amount, 2);
        }
        ksort($result);

        return $result;
    }

    /**
     * @param  array<int, array<string, mixed>>  $participantSummaries
     * @param  array<int, string>  $participants
     * @return array<int, array<string, mixed>>
     */
    private function buildTransfers(array $participantSummaries, array $participants): array
    {
        $rubBalances = [];
        foreach ($participantSummaries as $summary) {
            $balance = (float) $summary['balance_rub'];
            if (abs($balance) >= 0.01) {
                $rubBalances[(int) $summary['id']] = $balance;
            }
        }

        $transfers = [];
        foreach ($this->simplifyDebts($rubBalances) as $transfer) {
            $amount = round($transfer['amount'], 2);
            $transfers[] = [
                'from_participant_id' => $transfer['from'],
                'from_name' => $participants[$transfer['from']],
                'to_participant_id' => $transfer['to'],
                'to_name' => $participants[$transfer['to']],
                'amount' => $amount,
                'currency_code' => self::RUB,
                'amount_rub' => $amount,
            ];
        }

        return $transfers;
    }

    /**
     * @param  array<int, float>  $balances
     * @return array<int, array{from: int, to: int, amount: float}>
     */
    private function simplifyDebts(array $balances): array
    {
        $debtors = [];
        $creditors = [];

        foreach ($balances as $participantId => $balance) {
            if ($balance < -0.009) {
                $debtors[$participantId] = abs($balance);
            } elseif ($balance > 0.009) {
                $creditors[$participantId] = $balance;
            }
        }

        $transfers = [];
        while ($debtors !== [] && $creditors !== []) {
            arsort($creditors);
            arsort($debtors);

            $debtorId = array_key_first($debtors);
            $creditorId = array_key_first($creditors);
            $amount = min($debtors[$debtorId], $creditors[$creditorId]);

            $transfers[] = [
                'from' => $debtorId,
                'to' => $creditorId,
                'amount' => $amount,
            ];

            $debtors[$debtorId] -= $amount;
            $creditors[$creditorId] -= $amount;

            if ($debtors[$debtorId] < 0.01) {
                unset($debtors[$debtorId]);
            }
            if ($creditors[$creditorId] < 0.01) {
                unset($creditors[$creditorId]);
            }
        }

        return $transfers;
    }
}
