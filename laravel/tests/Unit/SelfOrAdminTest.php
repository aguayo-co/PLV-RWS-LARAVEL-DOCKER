<?php

namespace Tests\Feature;

use App\Address;
use App\Http\Middleware\SelfOrAdmin;
use App\User;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SelfOrAdminTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->middleware = new SelfOrAdmin();
        $this->closure = function ($return) {
            return $return;
        };
        $this->route = Mockery::mock();
        $this->user = factory(User::class)->create();
        $this->request = Mockery::mock();
    }

    public function testAccessAllowedIfNoUserId()
    {
        $this->request->user_id = null;

        $response = $this->middleware->handle($this->request, $this->closure);

        $this->assertEquals($response, $this->request);
    }


    public function testAccessDeniedForGuests()
    {
        $this->request->user_id = $this->user->id;

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Must be someone.');
        $this->middleware->handle($this->request, $this->closure);
    }

    public function testAccessDeniedForOtherUser()
    {
        Auth::setUser($this->user);
        $this->request->user_id = $this->user->id + 1;

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Only admin can set a different user_id.');
        $this->middleware->handle($this->request, $this->closure);
    }

    public function testAccessAllowedForSameUser()
    {
        Auth::setUser($this->user);
        $this->request->user_id = $this->user->id;

        $response = $this->middleware->handle($this->request, $this->closure);

        $this->assertEquals($response, $this->request);
    }

    public function testAccessAllowedForAdmin()
    {
        Auth::setUser($this->user);
        Role::create(['name' => 'admin']);

        $this->user->assignRole('admin');
        $this->request->user_id = $this->user->id + 1;

        $response = $this->middleware->handle($this->request, $this->closure);
        $this->assertEquals($response, $this->request);
    }
}
