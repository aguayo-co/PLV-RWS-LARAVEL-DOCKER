<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    const SHOPPING_CART = 10;
    const PAYMENT = 20;
    const PAYED = 30;
    const SHIPPED = 40;
    const DELIVERED = 41;
    const DELIVERED_UNCONFIRMED = 42;
    const RECEIVED = 43;
    const RETURNED = 60;
    const RETURNED_PARTIAL = 61;
    const COMPLETED = 90;
    const COMPLETED_RETURNED = 91;
    const COMPLETED_PARTIAL = 92;
    const CANCELED = 99;

    protected $fillable = [];
    protected $with = ['products'];

    /**
     * Get the user that sells this.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function products()
    {
        return $this->belongsToMany('App\Product');
    }
}
