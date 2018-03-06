<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class Slider extends Model
{
    public const IMAGES_BASE_PATH = 'public/sliders/images/';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'large_text', 'small_text', 'button_text', 'url', 'image', 'orientation', 'font_color'
    ];

    protected $appends = ['image'];

    /**
     * Store file temporarily while creating a slider.
     */
    protected $temp_image;


    public function getRouteKeyName()
    {
        return 'slug';
    }

    public static function boot()
    {
        parent::boot();
        self::created(function ($slider) {
            if ($slider->temp_image) {
                $slider->image = $slider->temp_image;
                $slider->temp_image = null;
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
        # Timestamps might not get updated if this was the only attribute that
        # changed in the model. Force timestamp update.
        $this->updateTimestamps();
    }

    public function setNameAttribute($name)
    {
        $this->attributes['name'] = $name;
        $this->attributes['slug'] = str_slug($name);
    }
}
