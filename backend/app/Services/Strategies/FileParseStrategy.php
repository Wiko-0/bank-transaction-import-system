<?php

namespace App\Services\Strategies;

interface FileParseStrategy
{
    /**
     * Every strategy must implement this method to turn raw content into a standard array.
     */
    public function parse(string $contentOrPath): array;
}