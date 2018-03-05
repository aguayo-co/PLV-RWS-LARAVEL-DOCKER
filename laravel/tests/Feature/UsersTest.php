<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;

class UsersTest extends TestCase
{
    public function testRegisterMustBeGuest()
    {
        $middleware = app()->routes->getByName('api.register')->gatherMiddleware();
        $this->assertContains('guest', $middleware);
    }

    public function testLoginMustBeGuest()
    {
        $middleware = app()->routes->getByName('api.login')->gatherMiddleware();
        $this->assertContains('guest', $middleware);
    }

    public function testPasswordRecoveryEmailMustBeGuest()
    {
        $middleware = app()->routes->getByName('api.password.recovery.email')->gatherMiddleware();
        $this->assertContains('guest', $middleware);
    }

    public function testPasswordRecoveryTokenMustBeGuest()
    {
        $middleware = app()->routes->getByName('api.password.recovery.token')->gatherMiddleware();
        $this->assertContains('guest', $middleware);
    }

    public function testUserUpdateIsProtected()
    {
        $middleware = app()->routes->getByName('api.user.update')->gatherMiddleware();
        $this->assertContains('auth:api', $middleware);
        $this->assertContains('owner_or_admin', $middleware);
    }

    public function testUserAddressesIsProtected()
    {
        $middleware = app()->routes->getByName('api.user.addresses.get')->gatherMiddleware();
        $this->assertContains('auth:api', $middleware);
    }

    public function testCreateUserAddressIsProtected()
    {
        $middleware = app()->routes->getByName('api.user.address.create')->gatherMiddleware();
        $this->assertContains('auth:api', $middleware);
        $this->assertContains('self_or_admin', $middleware);
    }

    public function testTokenIsSentOnCreate()
    {
        $url = route('api.register');
        $response = $this->json('POST', $url, [
            'email' => 'test@domain.com',
            'password' => '123$%ˆ456!@#',
            'first_name' => 'First',
            'last_name' => 'Last',
        ]);
        $response->assertJsonStructure(['api_token']);
    }

    public function testTokenIsSentOnLogin()
    {
        $user = factory(User::class)->create();

        $url = route('api.login');
        $response = $this->json('POST', $url, [
            'email' => $user->email,
            'password' => 'secret',
        ]);
        $response->assertJsonStructure(['api_token']);
    }

    public function testTokenIsNotSentOnUpdate()
    {
        $user = factory(User::class)->create();

        $url = route('api.user.update', $user->id);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $user->api_token
        ])->json('PATCH', $url, [
            'first_name' => 'First Name',
        ]);

        $response->assertStatus(200)->assertJsonMissing(['api_token']);
    }

    public function testTokenIsNotSentOnGet()
    {
        $user = factory(User::class)->create();

        $url = route('api.user.update', $user->id);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $user->api_token
        ])->json('GET', $url);

        $response->assertStatus(200)->assertJsonMissing(['api_token']);
    }
}
