<?php

namespace App\Services\TripSplit;

class TripSplitSettlementCalculator
{
    private const RUB = 'RUB';

    /**
     * Сводит поездку:
     * — paid_by_currency / owes_by_currency: фактические суммы в исходных валютах;
     * — paid_rub / owes_rub: всё пересчитано в ₽ по курсам поездки;
     * — balance_rub = paid_rub − owes_rub (переплата +, недоплата −);
     * — transfers: кто кому переводит в ₽ по итоговому balance_rub.
     *
     * @param  array<string, mixed>  $trip
     * @return array<string, mixed>
     */
    public function calculate(array $trip): array
    {
        $participants = $this->indexParticipants($trip['participants'] ?? []);
        $rates = $this->buildRates($trip['currencies'] ?? []);

        $paidByCurrency = [];
        $owesByCurrency = [];
        foreach (array_keys($participants) as $participantId) {
            $paidByCurrency[$participantId] = [];
            $owesByCurrency[$participantId] = [];
        }

        foreach ($trip['transactions'] ?? [] as $transaction) {
            $txCurrency = strtoupper((string) ($transaction['currency_code'] ?? self::RUB));

            foreach ($transaction['shares'] ?? [] as $share) {
                $participantId = (int) $share['participant_id'];
                $amount = (float) $share['amount'];
                $owesByCurrency[$participantId][$txCurrency] =
                    ($owesByCurrency[$participantId][$txCurrency] ?? 0.0) + $amount;
            }

            $payerPayments = $transaction['payer_payments'] ?? [];
            if ($payerPayments === []) {
                $payerPayments = [[
                    'participant_id' => (int) ($transaction['payer_id'] ?? 0),
                    'amount' => (float) ($transaction['amount'] ?? 0),
                    'currency_code' => $txCurrency,
                ]];
            }

            foreach ($payerPayments as $payment) {
                $participantId = (int) $payment['participant_id'];
                $currency = strtoupper((string) ($payment['currency_code'] ?? $txCurrency));
                $amount = (float) $payment['amount'];
                $paidByCurrency[$participantId][$currency] =
                    ($paidByCurrency[$participantId][$currency] ?? 0.0) + $amount;
            }
        }

        $participantSummaries = [];
        $balanceSum = 0.0;
        foreach ($participants as $participantId => $name) {
            $paidRub = $this->sumInRub($paidByCurrency[$participantId] ?? [], $rates);
            $owesRub = $this->sumInRub($owesByCurrency[$participantId] ?? [], $rates);
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
            'participants' => $participantSummaries,
            'transfers' => $transfers,
            'books_balanced' => abs($balanceSum) < 0.01,
            'unsettled_rub' => abs($balanceSum) >= 0.01 ? round(abs($balanceSum), 2) : 0.0,
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
     * Переводы только в ₽ по balance_rub (все валюты уже сведены через курсы).
     *
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
     * @param  array<int, float>  $balances  положительный = переплата (получает), отрицательный = должен (платит)
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
