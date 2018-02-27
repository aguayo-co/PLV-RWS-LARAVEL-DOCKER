<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    public function index()
    {
        return Auth::user();
    }

    public function user(User $user)
    {
        return $user->makeHidden('email');
    }

    /**
     * Get a validator for an incoming patch request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data, User $user)
    {
        if (array_key_exists('email', $data)) {
            $data['exists'] = $data['email'];
        }
        return Validator::make(
            $data,
            [
                'exists' => 'unique:users,email,' . $user->id,
                'email' => 'string|email|max:255',
                'password' => 'string|min:6',
                'first_name' => 'string|max:255',
                'last_name' => 'string|max:255',
                'phone' => 'string',
                'about' => 'nullable|string',
                'picture' => 'nullable|image',
                'cover' => 'nullable|image',
                'vacation_mode' => 'boolean',
            ],
            [
                'exists.unique' => trans('validation.email.exists'),
            ]
        );
    }

    /**
     * Handle an update request for a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function patch(Request $request, User $user)
    {
        $this->validator($request->all(), $user)->validate();

        $data = $request->all();
        if (array_key_exists('password', $data)) {
            $data['password'] = Hash::make($data['password']);
            $data['api_token'] = User::generateApiToken();
        }

        $user->fill($data)->save();

        if (array_key_exists('cover', $data) && !$data['cover'] && File::exists($user->cover_path)) {
            File::delete($user->cover_path);
        }
        if (array_key_exists('picture', $data) && !$data['picture'] && File::exists($user->picture_path)) {
            File::delete($user->picture_path);
        }

        return $user;
    }
}
