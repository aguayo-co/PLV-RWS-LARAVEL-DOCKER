<?php

namespace App;

use App\Traits\HasStatuses;
use App\Traits\SaveLater;
use Cmgmyr\Messenger\Models\Thread as BaseThread;
use Illuminate\Support\Facades\Storage;

class Thread extends BaseThread
{
    protected $with = ['messages', 'participants', 'users'];

    protected function getOwnersAttribute()
    {
        return $this->users;
    }

    public function product()
    {
        return $this->belongsTo('App\Product');
    }
}
