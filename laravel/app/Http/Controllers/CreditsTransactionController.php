<?php

namespace App\Http\Controllers;

use App\CreditsTransaction;
use Illuminate\Http\Request;

class CreditsTransactionController extends Controller
{
    protected $modelClass = CreditsTransaction::class;

    protected function validationRules(?Model $transaction)
    {
        $required = !$transaction ? 'required|' : '';
        return [
            'user_id' => $required . 'integer|exists:users,id',
            'amount' => $required . 'integer|between:-9999999,9999999',
            'sale_id' => 'nullable|integer|exists:sales,id',
            'order_id' => 'nullable|integer|exists:orders,id',
            'extra' => 'array',
        ];
    }
}
