<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    public const IMAGES_BASE_PATH = 'public/banners/images/';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'title', 'subtitle', 'button_text', 'url', 'image',
    ];

    protected $appends = ['image'];

    /**
     * Store file temporarily while creating a banner.
     */
    protected $temp_image;


    public function getRouteKeyName()
    {
        return 'slug';
    }

    public static function boot()
    {
        parent::boot();
        self::created(function ($banner) {
            if ($banner->temp_image) {
                $banner->image = $banner->temp_image;
                $banner->temp_image = null;
            }
        });
    }

    protected function getImageAttribute()
    {
        $path = $this->image_path;
        if ($files = Storage::files($path)) {
            return asset(Storage::url($files[0]));
        }
        return;
    }

    protected function getImagePathAttribute()
    {
        return $this::IMAGES_BASE_PATH . $this->id . '/';
    }

    protected function setImageAttribute(UploadedFile $image)
    {
        if (!$this->id) {
            $this->temp_image = $image;
            return;
        }
        $path = $this->image_path;
        Storage::deleteDirectory($path);
        $image->storeAs($path, uniqid());
    }

    public function setNameAttribute($name)
    {
        $this->attributes['name'] = $name;
        $this->attributes['slug'] = str_slug($name);
    }
}
