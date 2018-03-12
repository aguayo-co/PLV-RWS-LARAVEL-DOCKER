<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasStatuses;

class Order extends Model
{
    use HasStatuses;

    const STATUS_SHOPPING_CART = 10;
    const STATUS_PAYMENT = 20;
    const STATUS_PAYED = 30;
    const STATUS_CANCELED = 99;

    protected $fillable = [];
    protected $with = ['sales'];
    protected $appends = ['total'];

    /**
     * Get the user that buys this.
     */
    public function user()
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
    public function sales()
    {
        return $this->hasMany('App\Sale');
    }

    /**
     * Get the order products.
     */
    public function getProductsAttribute()
    {
        return $this->sales->pluck('products')->flatten();
    }

    /**
     * Get the order payments.
     */
    public function getTotalAttribute()
    {
        return $this->products->where('status', Product::STATUS_AVAILABLE)->sum('price');
    }

    public function setShippingAddressAttribute($value)
    {
        $this->attributes['shipping_address'] = json_encode($value);
    }

    public function getShippingAddressAttribute($value)
    {
        return json_decode($value);
    }
}
