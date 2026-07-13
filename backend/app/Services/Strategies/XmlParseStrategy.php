<?php

namespace App\Services\Strategies;

use XMLReader;

class XmlParseStrategy implements FileParseStrategy
{
    public function parse(string $filePath): iterable
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [];
        }

        $reader = new XMLReader();
        if (!$reader->open($filePath)) {
            return [];
        }

        try {
            // Przesuwamy kursor do pierwszego tagu <transaction>
            while ($reader->read() && $reader->localName !== 'transaction');

            while ($reader->localName === 'transaction') {
                // Pobieramy całe wnętrze tagu jako ciąg XML
                $node = $reader->readOuterXml();
                
                $xmlElement = simplexml_load_string($node);
                if ($xmlElement !== false) {
                    // Konwertujemy obiekt SimpleXMLElement na czystą tablicę PHP
                    yield json_decode(json_encode($xmlElement), true);
                }

                // Przeskakujemy do następnego węzła na tym samym poziomie (rodzeństwa)
                $reader->next('transaction');
            }
        } finally {
            $reader->close();
        }
    }
}