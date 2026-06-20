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

    public function test_cross_currency_payment_settles_in_rub(): void
    {
        // Чек 400 EUR, доли по 100 EUR. Аня платит 200 EUR, Боря — 180 USD (не EUR).
        // Курсы: EUR=100, USD=90 → оплаты 20 000 + 16 200 = 36 200 ₽, доли 40 000 ₽.
        $result = $this->calculator->calculate([
            'name' => 'Cross currency',
            'participants' => [
                ['id' => 1, 'name' => 'Аня'],
                ['id' => 2, 'name' => 'Боря'],
            ],
            'currencies' => [
                ['code' => 'RUB', 'rate_to_rub' => 1],
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

        // Аня: 200 EUR (20 000 ₽), услуги 200 EUR (20 000 ₽) → 0
        $this->assertEquals(20000.0, $result['participants'][0]['paid_rub']);
        $this->assertEquals(20000.0, $result['participants'][0]['owes_rub']);
        $this->assertEquals(0.0, $result['participants'][0]['balance_rub']);

        // Боря: 180 USD (16 200 ₽), услуги 200 EUR (20 000 ₽) → −3 800 ₽
        $this->assertEquals(16200.0, $result['participants'][1]['paid_rub']);
        $this->assertEquals(20000.0, $result['participants'][1]['owes_rub']);
        $this->assertEquals(-3800.0, $result['participants'][1]['balance_rub']);

        $this->assertFalse($result['books_balanced']);
        $this->assertEquals(3800.0, $result['unsettled_rub']);
        $this->assertEmpty($result['transfers']);
    }

    public function test_cross_currency_with_third_debtor_produces_rub_transfers(): void
    {
        // 300 EUR на троих, Аня 200 EUR, Боря 180 USD, Вера ничего не платила.
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

        $this->assertEquals(10000.0, $result['participants'][0]['balance_rub']);
        $this->assertEquals(6200.0, $result['participants'][1]['balance_rub']);
        $this->assertEquals(-10000.0, $result['participants'][2]['balance_rub']);

        $this->assertCount(1, $result['transfers']);
        $this->assertEquals(3, $result['transfers'][0]['from_participant_id']);
        $this->assertEquals(1, $result['transfers'][0]['to_participant_id']);
        $this->assertEquals(10000.0, $result['transfers'][0]['amount']);
        $this->assertEquals('RUB', $result['transfers'][0]['currency_code']);
    }

    public function test_stress_trip_rub_transfers(): void
    {
        $result = $this->calculator->calculate($this->stressTripPayload());

        $byId = [];
        foreach ($result['participants'] as $participant) {
            $byId[$participant['id']] = $participant;
        }

        $this->assertEquals(4425.0, $byId[1]['balance_rub']);
        $this->assertEquals(20925.0, $byId[2]['balance_rub']);
        $this->assertEquals(-6825.0, $byId[3]['balance_rub']);
        $this->assertEquals(-22325.0, $byId[4]['balance_rub']);
        $this->assertFalse($result['books_balanced']);
        $this->assertEquals(3800.0, $result['unsettled_rub']);

        $this->assertCount(2, $result['transfers']);
        $this->assertEquals(
            [
                ['from' => 4, 'to' => 2, 'amount' => 20925.0],
                ['from' => 3, 'to' => 1, 'amount' => 4425.0],
            ],
            array_map(
                fn (array $t) => [
                    'from' => $t['from_participant_id'],
                    'to' => $t['to_participant_id'],
                    'amount' => $t['amount'],
                ],
                $result['transfers']
            )
        );

        foreach ($result['transfers'] as $transfer) {
            $this->assertEquals('RUB', $transfer['currency_code']);
            $this->assertEquals($transfer['amount'], $transfer['amount_rub']);
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
