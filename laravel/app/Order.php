<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    const SHOPPING_CART = 1;

    protected $fillable = ['buyer_id', 'status'];
    protected $with = ['purchases.products'];
    protected $appends = ['total'];

    /**
     * Get the user that buys this.
     */
    public function buyer()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Get the order payments.
     */
    public function payments()
    {
        return $this->hasMany('App\Payment');
    }

    /**
     * Get the order payments.
     */
    public function purchases()
    {
        return $this->hasMany('App\Purchase');
    }

    /**
     * Get the order payments.
     */
    public function getProductsAttribute()
    {
        return $this->purchases->load('products')->pluck('products')->flatten();
    }

    /**
     * Get the order payments.
     */
    public function getTotalAttribute()
    {
        return $this->products->sum('price');
    }
}
