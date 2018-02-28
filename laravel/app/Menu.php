<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected $appends = ['items'];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'name';
    }

    /**
     * Get the children for the item.
     */
    public function items()
    {
        return $this->hasMany('App\MenuItem');
    }

    /**
     * Get the children for the item.
     */
    public function getItemsAttribute()
    {
        return $this->items()->get();
    }
}
