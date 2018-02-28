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
     * @param  string  $model
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        if (!$user) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $object = array_values($request->route()->parameters)[0];

        if (!$object || !is_a($object, 'Illuminate\Database\Eloquent\Model')) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        switch (true) {
            case $user->is($object->owner):
            case $user->hasRole('admin'):
            case $user->is($object):
                return $next($request);

            default:
                abort(Response::HTTP_FORBIDDEN);
        }
    }
}
