<?php

namespace App;

use App\Events\SaleReturnSaved;
use Illuminate\Database\Eloquent\Model;
use App\Traits\SaveLater;
use App\Traits\HasStatuses;
use App\Traits\HasStatusHistory;

class SaleReturn extends Model
{
    use SaveLater;
    use HasStatuses;
    use HasStatusHistory;

    const STATUS_PENDING = 00;
    const STATUS_SHIPPED = 40;
    const STATUS_DELIVERED = 41;
    const STATUS_RECEIVED = 49;
    const STATUS_COMPLETED = 90;
    const STATUS_CANCELED = 99;

    protected $fillable = ['status', 'product_ids'];
    protected $appends = ['product_ids'];

    protected $dispatchesEvents = [
        'saved' => SaleReturnSaved::class,
    ];

    public function products()
    {
        return $this->belongsToMany('App\Product', 'product_sale');
    }

    public function sales()
    {
        return $this->belongsToMany('App\Sale', 'product_sale');
    }

    protected function getSaleAttribute()
    {
        return $this->sales->first();
    }

    protected function getOwnersAttribute()
    {
        return collect([$this->sale->user, $this->sale->order->user]);
    }

    protected function getProductIdsAttribute()
    {
        return $this->sale->returned_product_ids;
    }

    protected function setProductIdsAttribute(array $productIds)
    {
        if ($this->saveLater('product_ids', $productIds)) {
            return;
        }
        $this->colors()->sync($productIds);
        $this->load('products');
        $this->touch();
    }
}
