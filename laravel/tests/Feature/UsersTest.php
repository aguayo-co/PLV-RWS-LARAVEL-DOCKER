<?php

namespace Tests\Feature;

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
}
