<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable;
    use HasRoles;

    public const COVERS_BASE_PATH = 'public/users/covers/';
    public const PICTURES_BASE_PATH = 'public/users/pictures/';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 'password', 'api_token', 'first_name', 'last_name', 'phone', 'about', 'vacation_mode'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'email', 'password', 'remember_token', 'api_token',
    ];

    protected $appends = ['cover', 'picture'];

    /**
     * Generate a random api_token string.
     *
     * @return string
     */
    public static function generateApiToken()
    {
        return uniqid() . str_random(47);
    }

    /**
     * Get the addresses for the user.
     */
    public function addresses()
    {
        return $this->hasMany('App\Address');
    }

    protected function getCoverPathAttribute()
    {
        return $this::COVERS_BASE_PATH . $this->id . '/';
    }

    protected function getPicturePathAttribute()
    {
        return $this::PICTURES_BASE_PATH . $this->id . '/';
    }

    protected function getCoverAttribute()
    {
        return $this->getImage($this->cover_path);
    }

    protected function getPictureAttribute()
    {
        return $this->getImage($this->picture_path);
    }

    protected function setCoverAttribute(?UploadedFile $cover)
    {
        $this->clearImages($this->cover_path);
        if ($cover) {
            $this->setImage($cover, $this->cover_path);
        }
    }

    protected function setPictureAttribute(?UploadedFile $picture)
    {
        $this->clearImages($this->picture_path);
        if ($picture) {
            $this->setImage($picture, $this->picture_path);
        }
    }

    protected function setImage(UploadedFile $image, $path)
    {
        $image->storeAs($path, uniqid());
    }

    protected function getImage($path)
    {
        if ($files = Storage::files($path)) {
            return asset($files[0]);
        }
        return;
    }

    protected function clearImages($path)
    {
        foreach (Storage::files($path) as $image) {
            Storage::delete($image);
        }
    }
}
