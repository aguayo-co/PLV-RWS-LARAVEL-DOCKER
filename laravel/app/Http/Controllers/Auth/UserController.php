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
    protected $modelClass = User::class;

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
            'shipping_method_ids' => 'array',
            'shipping_method_ids.*' => 'integer|exists:shipping_methods,id',
            'following_add' => 'array',
            'following_add.*' => 'integer|exists:users,id|different:id',
            'following_remove' => 'array',
            'following_remove.*' => 'integer|exists:users,id',
            'favorites_add' => 'array',
            'favorites_add.*' => 'integer|exists:products,id',
            'favorites_remove' => 'array',
            'favorites_remove.*' => 'integer|exists:products,id',
        ];
    }

    protected function validationMessages()
    {
        return [
            'exists.unique' => __('validation.email.exists'),
            'following_add.*.different' => __('validation.different.self'),
        ];
    }

    /**
     * Reset all tokens after password change.
     */
    public function postUpdate(Request $request, Model $user)
    {
        $apiToken = null;
        if ($request->password) {
            Token::destroy($user->tokens->pluck('id')->all());
            $apiToken = $user->createToken('PrilovChangePassword')->accessToken;
        }

        if ($request->following_add) {
            $user->following()->syncWithoutDetaching($request->following_add);
        }
        if ($request->following_remove) {
            $user->following()->detach($request->following_remove);
        }

        if ($request->favorites_add) {
            $user->favorites()->syncWithoutDetaching($request->favorites_add);
        }
        if ($request->favorites_remove) {
            $user->favorites()->detach($request->favorites_remove);
        }
        $user = parent::postUpdate($request, $this->setVisibility($user));

        // Last, set api_token so it gets sent with the response.
        // DO NOT do this before parent call, as it refreshes the model
        // and it gets lost.
        if ($apiToken) {
            $user->api_token = $apiToken;
        }

        return $user;
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
        $loggedUser = auth()->user();
        if ($user->is($loggedUser) || ($loggedUser && $loggedUser->hasRole('admin'))) {
            $user = $user->makeVisible('email');
        }
        return $user->load(['followers:id', 'following:id'])
            ->makeVisible(['followers_ids', 'following_ids', 'following_count', 'followers_count']);
    }
}
