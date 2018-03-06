<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Laravel\Passport\Token;

class UserController extends Controller
{
    protected function validationRules(?Model $user)
    {
        return [
            # Por requerimiento de front, el error de correo existente debe ser enviado por aparte.
            'exists' => 'unique:users,email,' . $user->id,
            'email' => 'string|email',
            'password' => 'string|min:6',
            'first_name' => 'string',
            'last_name' => 'string',
            'phone' => 'string',
            'about' => 'string',
            'picture' => 'image',
            'cover' => 'image',
            'vacation_mode' => 'boolean',
            'group_ids' => 'array',
            'group_ids.*' => 'integer|exists:groups,id',
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
    protected function alterValidateData($data, Model $user = null)
    {
        if (array_key_exists('email', $data)) {
            $data['exists'] = $data['email'];
        }
        return $data;
    }

    /**
     * Reset all tokens after password change.
     */
    public function postUpdate(Request $request, Model $user)
    {
        $user = parent::postUpdate($request, $user);
        if ($request->password) {
            Token::destroy($user->tokens->pluck('id')->all());
            $user->api_token = $user->createToken('PrilovChangePassword')->accessToken;
        }
        return $user;
    }
}
