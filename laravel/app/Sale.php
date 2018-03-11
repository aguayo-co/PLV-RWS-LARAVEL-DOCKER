<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    // Numbers are used to know when an action can be taken.
    // For instance, an order can not be marked as shipped if it
    // has not been payed: "status < PAYED".
    // But once it passes that stage, can be marked as shipped at any time.
    const SHOPPING_CART = 10;
    const PAYMENT = 20;
    const PAYED = 30;
    const SHIPPED = 40;
    const DELIVERED = 41;
    const RECEIVED = 49;
    const RETURNED = 60;
    const RETURNED_PARTIAL = 61;
    const COMPLETED = 90;
    const COMPLETED_RETURNED = 91;
    const COMPLETED_PARTIAL = 92;
    const CANCELED = 99;

    protected $fillable = ['shipment_details', 'delivered', 'shipped'];
    protected $with = ['products', 'shippingMethod'];

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

    public function shippingMethod()
    {
        return $this->belongsTo('App\ShippingMethod');
    }

    public function setShipmentDetailsAttribute($value)
    {
        $this->attributes['shipment_details'] = json_encode($value);
    }

    public function getShipmentDetailsAttribute($value)
    {
        return json_decode($value);
    }
}
