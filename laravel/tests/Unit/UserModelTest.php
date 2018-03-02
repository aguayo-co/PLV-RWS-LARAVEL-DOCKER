<?php

namespace Tests\Unit;

use App\User;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserModelTest extends TestCase
{
    public function testPasswordIsHidden()
    {
        $user = factory(User::class)->create();

        $this->assertNotNull($user->password);
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function testApiTokenIsHidden()
    {
        $user = factory(User::class)->create();

        $this->assertNotNull($user->api_token);
        $this->assertArrayNotHasKey('api_token', $user->toArray());
    }

    public function testRememberTokenIsHidden()
    {
        $user = factory(User::class)->create(['remember_token'=> 'abc']);

        $this->assertNotNull($user->remember_token);
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
    }

    public function testEmailTokenIsHidden()
    {
        $user = factory(User::class)->create();


        $this->assertNotNull($user->email);
        $this->assertArrayNotHasKey('email', $user->toArray());
    }

    public function testApiTokenLength()
    {
        $apiToken = User::generateApiToken();
        $this->assertSame(strlen($apiToken), 60);
    }

    public function testApiTokensAreDifferent()
    {
        $apiTokenA = User::generateApiToken();
        $apiTokenB = User::generateApiToken();
        $this->assertNotSame($apiTokenA, $apiTokenB);
    }
}
