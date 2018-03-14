<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $primaryKey = 'sale_id';
    public $incrementing = false;

    public $fillable = ['seller_rating', 'seller_comment', 'buyer_rating', 'buyer_comment'];

    public function sale()
    {
        return $this->belongsTo('App\Sale');
    }
}
