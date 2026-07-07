<?php

namespace App\Services\Strategies;

class JsonParseStrategy implements FileParseStrategy
{
    public function parse(string $content): array
    {
        return json_decode($content, true) ?? [];
    }
}