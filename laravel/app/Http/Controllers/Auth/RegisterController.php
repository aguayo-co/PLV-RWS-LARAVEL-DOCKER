<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
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

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        if (array_key_exists('email', $data)) {
            $data['exists'] = $data['email'];
        }
        return Validator::make(
            $data,
            [
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
            ],
            [
                'exists.unique' => trans('validation.email.exists'),
            ]
        );
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        $fillData = $data;
        $fillData['password'] = Hash::make($data['password']);
        $fillData['api_token'] = str_random(60);
        return User::create($fillData);
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        event(new Registered($user = $this->create($request->all())));
        if ($cover = $request->file('cover')) {
            $cover->storeAs('public/users/covers', $user->id);
        }
        if ($picture = $request->file('picture')) {
            $picture->storeAs('public/users/pictures', $user->id);
        }

        return $user;
    }
}
