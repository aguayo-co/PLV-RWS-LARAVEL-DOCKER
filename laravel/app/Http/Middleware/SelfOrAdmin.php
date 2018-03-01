<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class SelfOrAdmin
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
            abort(Response::HTTP_FORBIDDEN, 'Must be someone.');
        }

        if ($request->user_id && !$user->hasRole('admin') && $user->id != $request->user_id) {
            abort(Response::HTTP_FORBIDDEN, 'Only admin can set a different user_id.');
        }

        return $next($request);
    }
}
