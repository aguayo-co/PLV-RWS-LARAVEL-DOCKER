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
        'name', 'title', 'subtitle', 'button_text', 'url',
    ];

    protected $appends = ['image'];

    protected function getImageAttribute()
    {
        $path = $this->image_path;
        if (Storage::exists($path)) {
            return asset(Storage::url($path));
        }
    }

    protected function getImagePathAttribute()
    {
        return $this::IMAGES_BASE_PATH . $this->id;
    }

    protected function setImageAttribute(UploadedFile $image)
    {
        $image->storeAs($this::IMAGES_BASE_PATH, $this->id);
    }
}
