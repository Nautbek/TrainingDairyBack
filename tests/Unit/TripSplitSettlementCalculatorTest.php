<?php

namespace Tests\Unit;

use App\Services\TripSplit\TripSplitSettlementCalculator;
use PHPUnit\Framework\TestCase;

class TripSplitSettlementCalculatorTest extends TestCase
{
    public function test_calculates_balances_and_transfers(): void
    {
        $calculator = new TripSplitSettlementCalculator;

        $result = $calculator->calculate([
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

        $this->assertCount(2, $result['participants']);
        $this->assertEquals(250.0, $result['participants'][0]['paid_rub']);
        $this->assertEquals(125.0, $result['participants'][0]['owes_rub']);
        $this->assertEquals(125.0, $result['participants'][0]['balance_rub']);
        $this->assertEquals(125.0, $result['participants'][1]['owes_rub']);
        $this->assertNotEmpty($result['transfers']);
    }
}
