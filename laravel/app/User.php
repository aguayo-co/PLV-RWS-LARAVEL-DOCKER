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
        'following_ids',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'email',
        'password',
        'remember_token',
        # By default, hide everything that is sel referenced.
        # If not, this might lead to recursion.
        # Important to ->load(['followers', 'following']) on collections
        # if need to show its _ids or _count attributes.
        'following',
        'followers',
        'following_ids',
        'followers_ids',
        'following_count',
        'followers_count',
    ];

    protected $appends = ['cover', 'picture', 'following_ids', 'followers_ids', 'following_count', 'followers_count'];
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

    protected function getFollowersCountAttribute()
    {
        return $this->followers->count();
    }

    protected function getFollowingCountAttribute()
    {
        return $this->following->count();
    }


    protected function getFollowersIdsAttribute()
    {
        return $this->followers->pluck('id')->all();
    }

    protected function getFollowingIdsAttribute()
    {
        return $this->following->pluck('id')->all();
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'follower_followee', 'followee_id', 'follower_id');
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'follower_followee', 'follower_id', 'followee_id');
    }

    protected function setFollowingIdsAttribute(array $followingIds)
    {
        if ($this->saveLater('following_ids', $followingIds)) {
            return;
        }
        $this->following()->sync($followingIds);
    }

    protected function setGroupIdsAttribute(array $groupIds)
    {
        if ($this->saveLater('group_ids', $groupIds)) {
            return;
        }
        $this->groups()->sync($groupIds);
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
