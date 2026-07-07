<?php

namespace App\Models;

use App\Enums\CurrencyType;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = ['import_id', 'transaction_id', 'account_number', 'transaction_date', 'amount', 'currency'];

    protected $casts = [
        'currency' => CurrencyType::class,
    ];
}
