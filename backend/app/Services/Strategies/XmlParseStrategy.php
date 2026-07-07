<?php

namespace App\Services\Strategies;

class XmlParseStrategy implements FileParseStrategy
{
    public function parse(string $content): array
    {
        $xml = simplexml_load_string($content);
        if (!$xml) return [];
        
        $result = [];
        foreach ($xml->transaction as $tx) {
            $result[] = [
                'transaction_id' => (string)$tx->transaction_id,
                'account_number' => (string)$tx->account_number,
                'transaction_date' => (string)$tx->transaction_date,
                'amount' => (float)$tx->amount,
                'currency' => (string)$tx->currency,
            ];
        }
        return $result;
    }
}