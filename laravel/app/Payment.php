<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{

    const PENDING = 00;
    const SUCCESS = 10;
    const ERROR = 99;

    protected $fillable = ['order_id', 'status'];

    /**
     * Get the order to which this payment applies.
     */
    public function order()
    {
        return $this->belongsTo('App\Order');
    }
}
