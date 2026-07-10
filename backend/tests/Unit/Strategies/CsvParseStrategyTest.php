<?php

namespace Tests\Unit\Strategies;

use PHPUnit\Framework\TestCase;
use App\Services\Strategies\CsvParseStrategy;

class CsvParseStrategyTest extends TestCase
{
    /**
     * Test if CsvParseStrategy correctly parses a temporary CSV file into an array.
     */
    public function test_it_can_parse_valid_csv_content(): void
    {
        // 1. GIVEN
        $strategy = new CsvParseStrategy();
        
        // Tworzymy fizyczny plik tymczasowy na dysku kontenera
        $tempFile = tmpfile();
        
        // dane testowe
        fputcsv($tempFile, ['transaction_id', 'account_number', 'transaction_date', 'amount', 'currency']);
        fputcsv($tempFile, ['TXN-CSV-999', 'PL98765432109876543210987654', '2026-07-06', '250.00', 'EUR']);
        
        // Pobieramy prawdziwą, bezwzględną ścieżkę do tego pliku na dysku (np. /tmp/phpXYZ)
        $metaData = stream_get_meta_data($tempFile);
        $filePath = $metaData['uri'];

        // 2. WHEN
        // Przekazujemy realną ścieżkę pliku do naszej metody parsującej
        $result = $strategy->parse($filePath);
        
        // Zamykamy plik (w tym momencie PHP automatycznie usuwa go z dysku)
        fclose($tempFile);

        // 3. THEN
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('TXN-CSV-999', $result[0]['transaction_id']);
        $this->assertEquals(250.00, $result[0]['amount']);
        $this->assertEquals('EUR', $result[0]['currency']);
    }
}