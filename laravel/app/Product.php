<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Traits\SaveLater;

class Product extends Model
{
    use SaveLater;

    const UNPUBLISHED = 0;
    const AVAILABLE = 10;
    const UNAVAILABLE = 20;

    protected const IMAGES_BASE_PATH = 'public/product/images/';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'dimensions',
        'original_price',
        'price',
        'commission',
        'brand_id',
        'category_id',
        'condition_id',
        'status_id',
        'images',
        'delete_images',
        'color_ids',
        'campaign_ids',
    ];
    protected $with = ['brand', 'campaigns', 'colors', 'category.parent', 'condition', 'status', 'user'];
    protected $appends = ['images', 'color_ids', 'campaign_ids'];

    /**
     * Get the user that owns the address.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    protected function getColorIdsAttribute()
    {
        return $this->colors->pluck('id')->all();
    }

    protected function setColorIdsAttribute(array $colorIds)
    {
        if ($this->saveLater('color_ids', $colorIds)) {
            return;
        }
        $this->colors()->sync($colorIds);
    }

    protected function getCampaignIdsAttribute()
    {
        return $this->campaigns->pluck('id')->all();
    }

    protected function setCampaignIdsAttribute(array $campaignIds)
    {
        if ($this->saveLater('campaign_ids', $campaignIds)) {
            return;
        }
        $this->campaigns()->sync($campaignIds);
    }

    protected function getImagePathAttribute()
    {
        return $this::IMAGES_BASE_PATH . $this->id . '/';
    }

    protected function getImagesAttribute()
    {
        $images = [];
        foreach (Storage::files($this->image_path) as $image) {
            $images[] = asset(Storage::url($image));
        }
        return $images;
    }

    protected function setImagesAttribute(array $images)
    {
        if ($this->saveLater('images', $images)) {
            return;
        }

        foreach ($images as $image) {
            $image->storeAs($this->image_path, uniqid());
        }
        # Timestamps might not get updated if this was the only attribute that
        # changed in the model. Force timestamp update.
        $this->updateTimestamps();
    }

    protected function setDeleteImagesAttribute(array $images)
    {
        foreach ($images as $image) {
            if ($image && Storage::exists($this->image_path . $image)) {
                Storage::delete($this->image_path . $image);
            }
        }
    }

    public function brand()
    {
        return $this->belongsTo('App\Brand');
    }

    public function campaigns()
    {
        return $this->belongsToMany('App\Campaign');
    }

    public function category()
    {
        return $this->belongsTo('App\Category');
    }

    public function colors()
    {
        return $this->belongsToMany('App\Color');
    }

    public function condition()
    {
        return $this->belongsTo('App\Condition');
    }

    public function status()
    {
        return $this->belongsTo('App\Status');
    }

    public function sales()
    {
        return $this->belongsToMany('App\Sale');
    }

    public function setTitleAttribute($title)
    {
        $this->attributes['title'] = $title;
        $this->attributes['slug'] = str_slug($title);
    }
}
