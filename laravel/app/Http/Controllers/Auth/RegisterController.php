<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

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
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = parent::store($request);
        event(new Registered($user));
        if ($cover = $request->file('cover')) {
            $user->cover = $cover;
        }
        if ($picture = $request->file('picture')) {
            $user->picture = $picture;
        }
        return $user;
    }
}
