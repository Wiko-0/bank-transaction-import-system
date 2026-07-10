<?php

namespace Tests\Unit\Strategies;

use PHPUnit\Framework\TestCase;
use App\Services\Strategies\JsonParseStrategy;

class JsonParseStrategyTest extends TestCase
{
    /**
     * Test if JsonParseStrategy correctly parses a valid JSON string into an array.
     */
    public function test_it_can_parse_valid_json_content(): void
    {
        // 1. GIVEN
        $strategy = new JsonParseStrategy();
        $jsonPayload = json_encode([
            [
                'transaction_id' => 'TXN123',
                'account_number' => 'PL12345678901234567890123456',
                'transaction_date' => '2026-07-06',
                'amount' => 150.50,
                'currency' => 'PLN'
            ]
        ]);

        // 2. WHEN 
        $result = $strategy->parse($jsonPayload);

        // 3. THEN
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('TXN123', $result[0]['transaction_id']);
        $this->assertEquals(150.50, $result[0]['amount']);
        $this->assertEquals('PLN', $result[0]['currency']);
    }
}