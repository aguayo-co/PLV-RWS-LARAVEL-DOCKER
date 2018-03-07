<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Laravel\Passport\Token;
use App\User;

class UserController extends Controller
{
    public $modelClass = User::class;

    protected function alterValidateData($data, Model $user = null)
    {
        # ID needed to validate it is not self-referenced.
        $data['id'] = $user ? $user->id : false;
        if (array_key_exists('email', $data)) {
            $data['exists'] = $data['email'];
        }
        return $data;
    }

    protected function validationRules(?Model $user)
    {
        $required = !$user ? 'required|' : '';
        $ignore = $user ? ',' . $user->id : '';
        return [
            # Por requerimiento de front, el error de correo existente debe ser enviado por aparte.
            'exists' => 'unique:users,email' . $ignore,
            'email' => $required . 'string|email',
            'password' => $required . 'string|min:6',
            'first_name' => $required . 'string',
            'last_name' => $required . 'string',
            'phone' => 'string',
            'about' => 'string',
            'picture' => 'image',
            'cover' => 'image',
            'vacation_mode' => 'boolean',
            'group_ids' => 'array',
            'group_ids.*' => 'integer|exists:groups,id',
            'following_ids' => 'array',
            'following_ids.*' => 'integer|exists:users,id|different:id',
        ];
    }

    protected function validationMessages()
    {
        return [
            'exists.unique' => trans('validation.email.exists'),
            'following_ids.*.different' => trans('validation.different.self'),
        ];
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

        return $this->setVisibility($user);
    }

    public function postStore(Request $request, Model $user)
    {
        event(new Registered($user));
        $user = parent::postStore($request, $user);
        $user->api_token = $user->createToken('PrilovRegister')->accessToken;
        return $this->setVisibility($user);
    }

    public function show(Request $request, Model $user)
    {
        return $this->setVisibility(parent::show($request, $user));
    }

    protected function setVisibility(User $user)
    {
        return $user->load(['followers', 'following'])
            ->makeVisible(['followers_ids', 'following_ids', 'following_count', 'followers_count']);
    }
}
