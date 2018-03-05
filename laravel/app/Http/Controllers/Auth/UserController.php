<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
    public function alterValidateData($data, Model $user = null)
    {
        if (array_key_exists('email', $data)) {
            $data['exists'] = $data['email'];
        }
        return $data;
    }
}
