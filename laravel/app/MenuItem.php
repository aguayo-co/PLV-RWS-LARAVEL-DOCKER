<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'url', 'icon', 'parent_id', 'menu_id',
    ];

    protected $appends = ['children'];

    /**
     * Get the menu this item belongs to.
     */
    public function menu()
    {
        return $this->belongsTo('App\Menu');
    }

    /**
     * Get the parent this item belongs to.
     */
    public function parent()
    {
        return $this->belongsTo('App\MenuItem');
    }

    /**
     * Get the children for the item.
     */
    public function children()
    {
        return $this->hasMany('App\MenuItem', 'parent_id');
    }

    /**
     * Get the children for the item.
     */
    public function getChildrenAttribute()
    {
        return $this->children()->get();
    }

    public function setMenuIdAttribute($menuId)
    {
        $this->attributes['menu_id'] = $menuId;
        $this->attributes['parent_id'] = null;
    }

    public function setParentIdAttribute($parentId)
    {
        $this->attributes['parent_id'] = $parentId;
        $this->attributes['menu_id'] = null;
    }
}
