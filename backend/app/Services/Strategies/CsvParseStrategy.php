<?php

namespace App\Services\Strategies;

class CsvParseStrategy implements FileParseStrategy
{
    public function parse(string $filePath): iterable
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [];
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [];
        }

        // Pobiera nagłówki pliku CSV
        $headers = fgetcsv($handle, 0, ',');
        if (!$headers) {
            fclose($handle);
            return [];
        }

        // Czyści ewentualne ukryte znaki BOM z nagłówków
        $headers = array_map(function($header) {
            return trim($header, "\xEF\xBB\xBF ");
        }, $headers);

        try {
            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                if (count($row) !== count($headers)) {
                    continue; // Pomijamy uszkodzone wiersze
                }

                // Łączy nagłówki z wartościami wiersza
                yield array_combine($headers, $row);
            }
        } finally {
            fclose($handle);
        }
    }
}