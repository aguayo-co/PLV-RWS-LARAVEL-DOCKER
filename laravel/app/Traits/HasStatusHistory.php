<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait HasStatusHistory
{
    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = max($this->status, $value);
        $statusHistory = $this->status_history ?: [];
        if (!array_get($statusHistory, $value)) {
            $user = Auth::user();
            $statusHistory[$value] = [
                'date' => now(),
                'user_id' => $user ? $user->id : null,
            ];
        }
        $this->attributes['status_history'] = json_encode($statusHistory);
    }

    public function getStatusHistoryAttribute($value)
    {
        return json_decode($value, true);
    }
}
