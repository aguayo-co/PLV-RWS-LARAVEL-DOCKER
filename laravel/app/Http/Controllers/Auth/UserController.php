<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;

class UserController extends Controller
{

    protected function validationRules(?Model $user)
    {
        return [
            # Por requerimiento de front, el error de correo existente debe ser enviado por aparte.
            'exists' => 'unique:users,email,' . $user->id,
            'email' => 'string|email|max:255',
            'password' => 'string|min:6',
            'first_name' => 'string|max:255',
            'last_name' => 'string|max:255',
            'phone' => 'nullable|string',
            'about' => 'nullable|string',
            'picture' => 'nullable|image',
            'cover' => 'nullable|image',
            'vacation_mode' => 'boolean',
        ];
    }
    protected function validationMessages()
    {
        return [
            'exists.unique' => trans('validation.email.exists'),
        ];
    }

    /**
     * Alter data before validation.
     *
     * @param  array  $data
     * @return array
     */
    public function alterValidateData($data)
    {
        if (array_key_exists('email', $data)) {
            $data['exists'] = $data['email'];
        }
        return $data;
    }

    /**
     * Alter data to be passed to fill method.
     *
     * @param  array  $data
     * @return array
     */
    public function alterFillData($data)
    {
        if (array_key_exists('password', $data)) {
            $data['password'] = Hash::make($data['password']);
            $data['api_token'] = User::generateApiToken();
        }
        return $data;
    }

    /**
     * Handle an update request for a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Model $user)
    {
        $user = parent::update($request, $user);
        $data = $request->all();
        if (array_key_exists('cover', $data)) {
            $user->cover = $data['cover'];
        }
        if (array_key_exists('picture', $data)) {
            $user->picture = $data['picture'];
        }
        return $user;
    }
}
