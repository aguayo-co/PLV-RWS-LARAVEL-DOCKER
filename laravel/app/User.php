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
    protected $with = ['roles:id,name'];

    /**
     * Store files temporarily while creating a user.
     */
    protected $temp_cover;
    protected $temp_picture;

    /**
     * Generate a random api_token string.
     *
     * @return string
     */
    protected static function generateApiToken()
    {
        return uniqid() . str_random(47);
    }

    public static function boot()
    {
        parent::boot();
        self::created(function ($user) {
            if ($user->temp_cover) {
                $user->cover = $user->temp_cover;
            }
            if ($user->temp_picture) {
                $user->picture = $user->temp_picture;
            }
        });
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
        if (!$this->id) {
            return;
        }
        return $this::COVERS_BASE_PATH . $this->id . '/';
    }

    protected function getPicturePathAttribute()
    {
        if (!$this->id) {
            return;
        }
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
        if (!$this->id) {
            $this->temp_cover = $cover;
            return;
        }
        $this->clearImages($this->cover_path);
        if ($cover) {
            $this->setImage($cover, $this->cover_path);
        }
    }

    protected function setPictureAttribute(?UploadedFile $picture)
    {
        if (!$this->id) {
            $this->temp_picture = $picture;
            return;
        }
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
            return asset(Storage::url($files[0]));
        }
        return;
    }

    protected function clearImages($path)
    {
        Storage::deleteDirectory($path);
    }
}
