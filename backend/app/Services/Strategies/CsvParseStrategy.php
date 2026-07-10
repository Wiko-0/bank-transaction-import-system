<?php

namespace App\Services\Strategies;

class CsvParseStrategy implements FileParseStrategy
{
    public function parse(string $path): array
    {
        $result = [];
        if (($handle = fopen($path, "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ",");
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($header) == count($data)) {
                    $result[] = array_combine($header, $data);
                }
            }
            fclose($handle);
        }
        return $result;
    }
}