<?php

namespace App\Services\Strategies;

class JsonParseStrategy implements FileParseStrategy
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

        $buffer = '';
        $depth = 0;
        $inString = false;
        $escaped = false;

        try {
            while (($chunk = fread($handle, 4096)) !== false && $chunk !== '') {
                $len = strlen($chunk);
                
                for ($i = 0; $i < $len; $i++) {
                    $char = $chunk[$i];
                    $buffer .= $char;

                    if ($char === '"' && !$escaped) {
                        $inString = !$inString;
                    }

                    if ($inString) {
                        $escaped = ($char === '\\' && !$escaped);
                        continue;
                    }

                    $escaped = false;

                    if ($char === '{') {
                        $depth++;
                    } elseif ($char === '}') {
                        $depth--;

                        if ($depth === 0) {
                            $cleanBuffer = trim($buffer);
                            $cleanBuffer = rtrim(ltrim($cleanBuffer, ','), ',');
                            
                            $data = json_decode($cleanBuffer, true);
                            if (is_array($data)) {
                                yield $data;
                            }
                            
                            $buffer = '';
                        }
                    }

                    if ($depth === 0 && ($char === '[' || $char === ']' || $char === ',')) {
                        $buffer = '';
                    }
                }
            }
        } finally {
            fclose($handle);
        }
    }
}