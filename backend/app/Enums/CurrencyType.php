<?php

namespace App\Enums;

enum CurrencyType: string
{
    case PLN = 'PLN';
    case EUR = 'EUR';
    case USD = 'USD';
    case CAD = 'CAD';
    case JPY = 'JPY';
    case CHF = 'CHF';
}