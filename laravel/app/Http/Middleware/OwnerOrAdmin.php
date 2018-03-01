<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class OwnerOrAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        if (!$user) {
            abort(Response::HTTP_FORBIDDEN, "Must be someone.");
        }

        $object = array_values($request->route()->parameters)[0];

        if (!$user->hasRole('admin') && $request->user_id && $user->id != $request->user_id) {
            abort(Response::HTTP_FORBIDDEN, "Only admin can change the owner.");
        }

        switch (true) {
            case $user->is($object->user):
            case $user->hasRole('admin'):
            case $user->is($object):
                return $next($request);

            default:
                abort(Response::HTTP_FORBIDDEN, "User must be owner or admin.");
        }
    }
}
