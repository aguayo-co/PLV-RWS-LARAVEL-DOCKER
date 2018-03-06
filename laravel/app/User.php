<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;
use App\Traits\SaveLater;

class User extends Authenticatable
{
    use Notifiable;
    use HasRoles;
    use HasApiTokens;
    use SaveLater;

    protected const COVERS_BASE_PATH = 'public/users/covers/';
    protected const PICTURES_BASE_PATH = 'public/users/pictures/';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'about',
        'cover',
        'email',
        'first_name',
        'last_name',
        'password',
        'phone',
        'picture',
        'vacation_mode',
        'group_ids',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'email', 'password', 'remember_token',
    ];

    protected $appends = ['cover', 'picture'];
    protected $with = ['roles', 'groups'];

    public static function boot()
    {
        parent::boot();
        self::registerSavesNow();
        self::saved(function ($user) {
            $user->validateSeller();
        });
    }

    /**
     * To be have "seller" role must have values in all the listed fields.
     * With one field empty the role is lost.
     */
    protected function validateSeller()
    {
        $role = Role::where(['name' => 'seller'])->first();

        if (!$role) {
            return;
        }

        switch (false) {
            case $this->about:
            case $this->cover:
            case $this->phone:
            case $this->picture:
                $this->removeRole($role);
                $this->load('roles');
                return;
            default:
                $this->ensureRole($role);
        }
    }

    /**
     * Ensures that role is set.
     */
    protected function ensureRole($role)
    {
        if ($this->hasRole($role)) {
            return;
        }
        $this->assignRole($role);
        $this->load('roles');
    }

    /**
     * Hash password.
     */
    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = Hash::make($password);
    }

    /**
     * Get the addresses for the user.
     */
    public function addresses()
    {
        return $this->hasMany('App\Address');
    }

    public function groups()
    {
        return $this->belongsToMany('App\Group');
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
        if ($this->saveLater('cover', $cover)) {
            return;
        }
        $this->clearImages($this->cover_path);
        if ($cover) {
            $this->setImage($cover, $this->cover_path);
        }
    }

    protected function setPictureAttribute(?UploadedFile $picture)
    {
        if ($this->saveLater('picture', $picture)) {
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
        # Timestamps might not get updated if this was the only attribute that
        # changed in the model. Force timestamp update.
        $this->updateTimestamps();
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
