<?php

namespace Tests\Unit;

use App\Services\TripSplit\TripSplitSettlementCalculator;
use PHPUnit\Framework\TestCase;

class TripSplitSettlementCalculatorTest extends TestCase
{
    private TripSplitSettlementCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new TripSplitSettlementCalculator;
    }

    public function test_calculates_balances_and_rub_transfers(): void
    {
        $result = $this->calculator->calculate([
            'name' => 'Test trip',
            'participants' => [
                ['id' => 1, 'name' => 'Аня'],
                ['id' => 2, 'name' => 'Боря'],
            ],
            'currencies' => [
                ['code' => 'RUB', 'rate_to_rub' => 1],
                ['code' => 'TRY', 'rate_to_rub' => 2.5],
            ],
            'transactions' => [
                [
                    'id' => 10,
                    'amount' => 100,
                    'currency_code' => 'TRY',
                    'description' => 'Ужин',
                    'shares' => [
                        ['participant_id' => 1, 'amount' => 50],
                        ['participant_id' => 2, 'amount' => 50],
                    ],
                    'payer_payments' => [
                        ['participant_id' => 1, 'amount' => 100, 'currency_code' => 'TRY'],
                    ],
                ],
            ],
        ]);

        $this->assertTrue($result['books_balanced']);
        $this->assertEquals(0.0, $result['unsettled_rub']);
        $this->assertEquals(250.0, $result['participants'][0]['paid_rub']);
        $this->assertEquals(125.0, $result['participants'][0]['owes_rub']);
        $this->assertEquals(125.0, $result['participants'][0]['balance_rub']);
        $this->assertEquals(125.0, $result['participants'][1]['owes_rub']);
        $this->assertEquals(-125.0, $result['participants'][1]['balance_rub']);

        $this->assertCount(1, $result['transfers']);
        $this->assertEquals(2, $result['transfers'][0]['from_participant_id']);
        $this->assertEquals(1, $result['transfers'][0]['to_participant_id']);
        $this->assertEquals(125.0, $result['transfers'][0]['amount']);
        $this->assertEquals('RUB', $result['transfers'][0]['currency_code']);
    }

    public function test_cross_currency_payment_scales_owes_to_actual_paid(): void
    {
        // Чек 400 EUR, доли по 200 EUR. Оплаты: 200 EUR + 180 USD (= 162 EUR).
        // Доли масштабируются: 362/400 → каждому по 181 EUR услуг.
        $result = $this->calculator->calculate([
            'name' => 'Cross currency',
            'participants' => [
                ['id' => 1, 'name' => 'Аня'],
                ['id' => 2, 'name' => 'Боря'],
            ],
            'currencies' => [
                ['code' => 'USD', 'rate_to_rub' => 90],
                ['code' => 'EUR', 'rate_to_rub' => 100],
            ],
            'transactions' => [
                [
                    'id' => 1,
                    'amount' => 400,
                    'currency_code' => 'EUR',
                    'shares' => [
                        ['participant_id' => 1, 'amount' => 200],
                        ['participant_id' => 2, 'amount' => 200],
                    ],
                    'payer_payments' => [
                        ['participant_id' => 1, 'amount' => 200, 'currency_code' => 'EUR'],
                        ['participant_id' => 2, 'amount' => 180, 'currency_code' => 'USD'],
                    ],
                ],
            ],
        ]);

        $this->assertTrue($result['books_balanced']);
        $this->assertEquals(0.0, $result['unsettled_rub']);
        $this->assertEquals(20000.0, $result['participants'][0]['paid_rub']);
        $this->assertEquals(18100.0, $result['participants'][0]['owes_rub']);
        $this->assertEquals(1900.0, $result['participants'][0]['balance_rub']);
        $this->assertEquals(16200.0, $result['participants'][1]['paid_rub']);
        $this->assertEquals(18100.0, $result['participants'][1]['owes_rub']);
        $this->assertEquals(-1900.0, $result['participants'][1]['balance_rub']);

        $this->assertCount(1, $result['transfers']);
        $this->assertEquals(2, $result['transfers'][0]['from_participant_id']);
        $this->assertEquals(1, $result['transfers'][0]['to_participant_id']);
        $this->assertEquals(1900.0, $result['transfers'][0]['amount']);
    }

    public function test_cross_currency_with_third_debtor_produces_rub_transfers(): void
    {
        $result = $this->calculator->calculate([
            'name' => 'Three participants',
            'participants' => [
                ['id' => 1, 'name' => 'Аня'],
                ['id' => 2, 'name' => 'Боря'],
                ['id' => 3, 'name' => 'Вера'],
            ],
            'currencies' => [
                ['code' => 'USD', 'rate_to_rub' => 90],
                ['code' => 'EUR', 'rate_to_rub' => 100],
            ],
            'transactions' => [
                [
                    'id' => 1,
                    'amount' => 300,
                    'currency_code' => 'EUR',
                    'shares' => [
                        ['participant_id' => 1, 'amount' => 100],
                        ['participant_id' => 2, 'amount' => 100],
                        ['participant_id' => 3, 'amount' => 100],
                    ],
                    'payer_payments' => [
                        ['participant_id' => 1, 'amount' => 200, 'currency_code' => 'EUR'],
                        ['participant_id' => 2, 'amount' => 180, 'currency_code' => 'USD'],
                    ],
                ],
            ],
        ]);

        $this->assertTrue($result['books_balanced']);
        $this->assertEquals(0.0, $result['unsettled_rub']);
        $this->assertEquals(7933.33, $result['participants'][0]['balance_rub']);
        $this->assertEquals(4133.33, $result['participants'][1]['balance_rub']);
        $this->assertEquals(-12066.67, $result['participants'][2]['balance_rub']);

        $this->assertCount(2, $result['transfers']);
        $this->assertEquals(3, $result['transfers'][0]['from_participant_id']);
        $this->assertEquals(1, $result['transfers'][0]['to_participant_id']);
        $this->assertEquals(7933.33, $result['transfers'][0]['amount']);
        $this->assertEquals(3, $result['transfers'][1]['from_participant_id']);
        $this->assertEquals(2, $result['transfers'][1]['to_participant_id']);
        $this->assertEquals(4133.33, $result['transfers'][1]['amount']);
    }

    public function test_stress_trip_balances_with_ten_percent_gap(): void
    {
        $result = $this->calculator->calculate($this->stressTripPayload());

        $this->assertTrue($result['books_balanced']);
        $this->assertEquals(0.0, $result['unsettled_rub']);
        $this->assertNotEmpty($result['transfers']);

        foreach ($result['transfers'] as $transfer) {
            $this->assertEquals('RUB', $transfer['currency_code']);
            $this->assertEquals($transfer['amount'], $transfer['amount_rub']);
        }

        $balancesAfter = [];
        foreach ($result['participants'] as $p) {
            $balancesAfter[(int) $p['id']] = (float) $p['balance_rub'];
        }
        foreach ($result['transfers'] as $t) {
            $balancesAfter[$t['from_participant_id']] += $t['amount'];
            $balancesAfter[$t['to_participant_id']] -= $t['amount'];
        }
        foreach ($balancesAfter as $balance) {
            $this->assertLessThan(0.05, abs($balance));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function stressTripPayload(): array
    {
        return [
            'name' => 'Stress',
            'participants' => [
                ['id' => 1, 'name' => 'Аня'],
                ['id' => 2, 'name' => 'Боря'],
                ['id' => 3, 'name' => 'Вера'],
                ['id' => 4, 'name' => 'Гена'],
            ],
            'currencies' => [
                ['code' => 'RUB', 'rate_to_rub' => 1],
                ['code' => 'USD', 'rate_to_rub' => 90],
                ['code' => 'EUR', 'rate_to_rub' => 100],
                ['code' => 'TRY', 'rate_to_rub' => 2.5],
            ],
            'transactions' => [
                [
                    'id' => 101,
                    'amount' => 200,
                    'currency_code' => 'TRY',
                    'shares' => [
                        ['participant_id' => 1, 'amount' => 50],
                        ['participant_id' => 2, 'amount' => 50],
                        ['participant_id' => 3, 'amount' => 50],
                        ['participant_id' => 4, 'amount' => 50],
                    ],
                    'payer_payments' => [
                        ['participant_id' => 1, 'amount' => 200, 'currency_code' => 'TRY'],
                    ],
                ],
                [
                    'id' => 102,
                    'amount' => 600,
                    'currency_code' => 'USD',
                    'shares' => [
                        ['participant_id' => 1, 'amount' => 150],
                        ['participant_id' => 2, 'amount' => 150],
                        ['participant_id' => 3, 'amount' => 150],
                        ['participant_id' => 4, 'amount' => 150],
                    ],
                    'payer_payments' => [
                        ['participant_id' => 2, 'amount' => 400, 'currency_code' => 'USD'],
                        ['participant_id' => 3, 'amount' => 200, 'currency_code' => 'USD'],
                    ],
                ],
                [
                    'id' => 103,
                    'amount' => 3000,
                    'currency_code' => 'RUB',
                    'shares' => [
                        ['participant_id' => 1, 'amount' => 1000],
                        ['participant_id' => 2, 'amount' => 1000],
                        ['participant_id' => 3, 'amount' => 500],
                        ['participant_id' => 4, 'amount' => 500],
                    ],
                    'payer_payments' => [
                        ['participant_id' => 4, 'amount' => 3000, 'currency_code' => 'RUB'],
                    ],
                ],
                [
                    'id' => 104,
                    'amount' => 400,
                    'currency_code' => 'EUR',
                    'shares' => [
                        ['participant_id' => 1, 'amount' => 100],
                        ['participant_id' => 2, 'amount' => 100],
                        ['participant_id' => 3, 'amount' => 100],
                        ['participant_id' => 4, 'amount' => 100],
                    ],
                    'payer_payments' => [
                        ['participant_id' => 1, 'amount' => 200, 'currency_code' => 'EUR'],
                        ['participant_id' => 2, 'amount' => 180, 'currency_code' => 'USD'],
                    ],
                ],
                [
                    'id' => 105,
                    'amount' => 5000,
                    'currency_code' => 'RUB',
                    'shares' => [
                        ['participant_id' => 1, 'amount' => 1250],
                        ['participant_id' => 2, 'amount' => 1250],
                        ['participant_id' => 3, 'amount' => 1250],
                        ['participant_id' => 4, 'amount' => 1250],
                    ],
                    'payer_payments' => [
                        ['participant_id' => 3, 'amount' => 2500, 'currency_code' => 'RUB'],
                        ['participant_id' => 4, 'amount' => 2500, 'currency_code' => 'RUB'],
                    ],
                ],
                [
                    'id' => 106,
                    'amount' => 120,
                    'currency_code' => 'USD',
                    'shares' => [
                        ['participant_id' => 1, 'amount' => 30],
                        ['participant_id' => 2, 'amount' => 30],
                        ['participant_id' => 3, 'amount' => 30],
                        ['participant_id' => 4, 'amount' => 30],
                    ],
                    'payer_payments' => [
                        ['participant_id' => 2, 'amount' => 120, 'currency_code' => 'USD'],
                    ],
                ],
                [
                    'id' => 107,
                    'amount' => 1000,
                    'currency_code' => 'TRY',
                    'shares' => [
                        ['participant_id' => 1, 'amount' => 0],
                        ['participant_id' => 2, 'amount' => 400],
                        ['participant_id' => 3, 'amount' => 300],
                        ['participant_id' => 4, 'amount' => 300],
                    ],
                    'payer_payments' => [
                        ['participant_id' => 3, 'amount' => 600, 'currency_code' => 'TRY'],
                        ['participant_id' => 4, 'amount' => 400, 'currency_code' => 'TRY'],
                    ],
                ],
                [
                    'id' => 108,
                    'amount' => 250,
                    'currency_code' => 'EUR',
                    'shares' => [
                        ['participant_id' => 1, 'amount' => 125],
                        ['participant_id' => 2, 'amount' => 125],
                        ['participant_id' => 3, 'amount' => 0],
                        ['participant_id' => 4, 'amount' => 0],
                    ],
                    'payer_payments' => [
                        ['participant_id' => 1, 'amount' => 250, 'currency_code' => 'EUR'],
                    ],
                ],
            ],
        ];
    }
}
