<?php

namespace Tests\Unit;

use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

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

    public function testCoverImagePersisted()
    {
        Storage::fake();
        $user = factory(User::class)->create();

        $user->cover = UploadedFile::fake()->image('cover');
        $this->assertTrue(Storage::exists($user->cover_path));
    }

    public function testPictureImagePersisted()
    {
        Storage::fake();
        $user = factory(User::class)->create();

        $user->picture = UploadedFile::fake()->image('picture');
        $this->assertTrue(Storage::exists($user->picture_path));
    }

    public function testPictureDeleted()
    {
        Storage::fake();
        $user = factory(User::class)->create();

        $user->picture = UploadedFile::fake()->image('picture');
        $user->picture = null;
        $this->assertFalse(Storage::exists($user->picture_path));
    }

    public function testCoverDeleted()
    {
        Storage::fake();
        $user = factory(User::class)->create();

        $user->cover = UploadedFile::fake()->image('cover');
        $user->cover = null;
        $this->assertFalse(Storage::exists($user->picture_path));
    }
}
