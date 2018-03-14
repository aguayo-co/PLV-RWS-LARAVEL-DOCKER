<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasStatuses;

class Rating extends Model
{
    use HasStatuses;

    const STATUS_UNPUBLISHED = 0;
    const STATUS_PUBLISHED = 10;

    protected $primaryKey = 'sale_id';
    public $incrementing = false;

    public $fillable = ['seller_rating', 'seller_comment', 'buyer_rating', 'buyer_comment'];

    public function sale()
    {
        return $this->belongsTo('App\Sale');
    }
}
