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
            // Przesuwa kursor do pierwszego tagu <transaction>
            while ($reader->read() && $reader->localName !== 'transaction');

            while ($reader->localName === 'transaction') {
                // Pobiera całe wnętrze tagu jako ciąg XML
                $node = $reader->readOuterXml();
                
                $xmlElement = simplexml_load_string($node);
                if ($xmlElement !== false) {
                    // Konwertujemy obiekt SimpleXMLElement na czystą tablicę PHP
                    yield json_decode(json_encode($xmlElement), true);
                }

                // Przeskakuje do następnego węzła na tym samym poziomie
                $reader->next('transaction');
            }
        } finally {
            $reader->close();
        }
    }
}