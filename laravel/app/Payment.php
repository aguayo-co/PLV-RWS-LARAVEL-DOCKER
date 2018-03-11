<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    const PENDING = 00;
    const SUCCESS = 10;
    const ERROR = 99;

    protected $fillable = ['order_id', 'status'];
    protected $hidden = ['request'];
    protected $appends = ['request_data'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($payment) {
            // This field does not really needs to be unique absolutely.
            // Will be used with the `id` field to make a unique combination.
            // This is needed to reduce the possibility of duplicate transaction
            // ids for the payment gateways.
            // Duplicate transaction ids might be common in test mode.
            $payment->uniqid = uniqid();
        });
    }

    /**
     * Get the order to which this payment applies.
     */
    public function order()
    {
        return $this->belongsTo('App\Order');
    }

    public function getTotalAttribute()
    {
        return $this->order->total;
    }

    public function setRequestAttribute($value)
    {
        $this->attributes['request'] = json_encode($value);
    }

    public function getRequestAttribute($value)
    {
        return json_decode($value);
    }

    public function getRequestDataAttribute($value)
    {
        return $this->request->public_data;
    }

    public function setAttemptsAttribute($value)
    {
        $this->attributes['attempts'] = json_encode($value);
    }

    public function getAttemptsAttribute($value)
    {
        return json_decode($value);
    }
}
