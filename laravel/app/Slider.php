<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Traits\SaveLater;

class Slider extends Model
{
    use SaveLater;

    protected const IMAGES_BASE_PATH = 'public/sliders/images/';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'large_text', 'small_text', 'button_text', 'url', 'image', 'orientation', 'font_color'
    ];

    protected $appends = ['image'];

    public function getRouteKeyName()
    {
        return 'slug';
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
        if ($this->saveLater('image', $image)) {
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
