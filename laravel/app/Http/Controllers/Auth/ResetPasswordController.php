<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    protected function validationRules(?Model $model)
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6',
        ];
    }


    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  $email
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(Request $request, $email)
    {
        $user = $this->getUser($request, $email);

        $this->setPassword($user, $request->password);

        return $user->makeVisible('api_token');
    }

    protected function getUser(Request $request, $email)
    {
        $request->merge(['email' => $email]);
        $this->validate($request->all());

        $user = $this->broker()->getUser(['email' => $email]);
        if (is_null($user)) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, trans(Password::INVALID_USER));
        }

        if (!$this->broker()->tokenExists($user, $request->token)) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, trans(Password::INVALID_TOKEN));
        }

        return $user;
    }

    protected function setPassword($user, $password)
    {
        $user->password = $password;
        $user->save();
        $this->broker()->deleteToken($user);
        event(new PasswordReset($user));
    }

    /**
     * Get the broker to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker()
    {
        return Password::broker();
    }
}
