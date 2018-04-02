<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasStatuses;
use App\Traits\HasStatusHistory;

class Order extends Model
{
    use HasStatuses;
    use HasStatusHistory;

    const STATUS_SHOPPING_CART = 10;
    const STATUS_PAYMENT = 20;
    const STATUS_PAYED = 30;
    const STATUS_CANCELED = 99;

    protected $fillable = ['shipping_address', 'coupon_id'];
    protected $with = ['sales', 'creditsTransactions', 'payments', 'coupon'];
    protected $hidden = ['coupon'];
    protected $appends = ['total', 'due', 'coupon_code'];

    /**
     * Get the user that buys this.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function coupon()
    {
        return $this->belongsTo('App\Coupon');
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

    public function creditsTransactions()
    {
        return $this->hasMany('App\CreditsTransaction');
    }

    /**
     * Get the order products.
     */
    public function getProductsAttribute()
    {
        return $this->sales->pluck('products')->flatten();
    }

    /**
     * Get the order products that were marked for return.
     */
    public function getReturnedProductsAttribute()
    {
        return $this->sales->pluck('returned_products')->flatten();
    }

    /**
     * The total value of the order.
     */
    public function getTotalAttribute()
    {
        return $this->products->sum('price');
    }

    /**
     * The value the user needs to pay after applying the credits
     * and the coupons the user used.
     */
    public function getDueAttribute()
    {
        $total = $this->products->sum('price');
        $credited = $this->creditsTransactions->sum('amount');
        return $total + $credited;
    }

    public function getCouponCodeAttribute()
    {
        return $this->coupon ? $this->coupon->code : null;
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
