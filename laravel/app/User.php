<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
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

    protected $appends = ['cover', 'picture'];

    public function getCoverAttribute()
    {
        $path = 'public/users/covers/' . $this->id;
        if (Storage::exists($path)) {
            return asset($path);
        }
    }

    public function getPictureAttribute()
    {
        $path = 'public/users/pictures/' . $this->id;
        if (Storage::exists($path)) {
            return asset($path);
        }
    }
}
