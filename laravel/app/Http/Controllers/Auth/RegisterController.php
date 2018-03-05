<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation.
    |
    */


    public $modelClass = User::class;

    protected function validationRules(?Model $user)
    {
        return [
            # Por requerimiento de front, el error de correo existente debe ser enviado por aparte.
            'exists' => 'unique:users,email',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'string',
            'about' => 'string',
            'picture' => 'image',
            'cover' => 'image',
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

    public function postStore(Request $request, Model $user)
    {
        event(new Registered($user));
        return parent::postStore($request, $user)->makeVisible('api_token');
    }
}
