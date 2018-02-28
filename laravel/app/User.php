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
        'password', 'remember_token', 'api_token',
    ];

    /**
     * Generate a random api_token string.
     *
     * @return string
     */
    public static function generateApiToken()
    {
        return uniqid() . str_random(47);
    }

    public const COVERS_BASE_PATH = 'public/users/covers/';
    public const PICTURES_BASE_PATH = 'public/users/pictures/';

    protected $appends = ['cover', 'picture'];

    protected function getCoverAttribute()
    {
        $path = $this->cover_path;
        if (Storage::exists($path)) {
            return asset($path);
        }
    }

    protected function getPictureAttribute()
    {
        $path = $this->picture_path;
        if (Storage::exists($path)) {
            return asset($path);
        }
    }

    protected function getCoverPathAttribute()
    {
        return $this::COVERS_BASE_PATH . $this->id;
    }

    protected function getPicturePathAttribute()
    {
        return $this::PICTURES_BASE_PATH . $this->id;
    }

    protected function setCoverAttribute(?UploadedFile $cover)
    {
        if ($cover === null) {
            if (Storage::exists($this->cover_path)) {
                Storage::delete($this->cover_path);
            }
            return;
        }

        $cover->storeAs($this::COVERS_BASE_PATH, $this->id);
    }

    protected function setPictureAttribute(?UploadedFile $picture)
    {
        if ($picture === null) {
            if (Storage::exists($this->picture_path)) {
                Storage::delete($this->picture_path);
            }
            return;
        }

        $picture->storeAs($this::PICTURES_BASE_PATH, $this->id);
    }
}
