<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = ['import_id', 'transaction_id', 'account_number', 'transaction_date', 'amount', 'currency'];
}
