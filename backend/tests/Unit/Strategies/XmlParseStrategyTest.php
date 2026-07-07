<?php

namespace Tests\Unit\Strategies;

use PHPUnit\Framework\TestCase;
use App\Services\Strategies\XmlParseStrategy;

class XmlParseStrategyTest extends TestCase
{
    /**
     * Test if XmlParseStrategy correctly parses a valid XML string into an array.
     */
    public function test_it_can_parse_valid_xml_content(): void
    {
        // 1. GIVEN XML
        $strategy = new XmlParseStrategy();
        $xmlPayload = '<?xml version="1.0" encoding="UTF-8"?>
        <transactions>
            <transaction>
                <transaction_id>TXN-XML-777</transaction_id>
                <account_number>PL55555555555555555555555555</account_number>
                <transaction_date>2026-07-06</transaction_date>
                <amount>1230.85</amount>
                <currency>USD</currency>
            </transaction>
        </transactions>';

        // 2. WHEN
        $result = $strategy->parse($xmlPayload);

        // 3. THEN
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('TXN-XML-777', $result[0]['transaction_id']);
        $this->assertEquals(1230.85, $result[0]['amount']);
        $this->assertEquals('USD', $result[0]['currency']);
    }
}