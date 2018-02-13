<?php

namespace Tests\integration\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Tests\integration\BaseIntegrationCase;

class CheckRoleTest extends BaseIntegrationCase
{
  protected $fixtures = [];
  
  public function __construct()
  {
    parent::__construct();
    $this->fixtures = [
      'roles' => [
        ['id' => 1, 'name' => 'ROLE_USER'],
        ['id' => 2, 'name' => 'ROLE_ADMIN'],
      ],
      'users' => [
        [
          'id' => 1, 'username' => 'active user', 'email' => 'active@test.com',
          'password' => '$2y$10$B8lF5o194wez1tuhrEKbb.WIp0YxHSBfv2ZC7ebBhXHCiqn5.vD4e',
          'remember_token' => null, 'is_active' => 1, 'role_id' => 1,
          'created_at' => date('Y-m-d H:i:s', time() - 10000),
          'updated_at' => date('Y-m-d H:i:s', time() - 10000),
        ],
      ],
    ];
  }

  public function setUp(): void
  {
    parent::setUp();
    $this->middleware = $this->getApp()->make('App\Http\Middleware\CheckRole');
    $this->request = new Request([], [], [], [], [], [], []);
    $this->request->setUserResolver(function ($guard) {
      return(User::find(1));
    });
  }

  public function testHandleIsLettingTheRequestThroughIfTheAuthenticatedUserHasTheRequestedRole()
  {
    $closureCalled = false;
    $closure = function ($arg) use (&$closureCalled) {
      $closureCalled = true;
      return('closure return value');
    };
    
    $this->assertEquals(
      'closure return value',
      $this->middleware->handle($this->request, $closure, '1')
    );
    $this->assertEquals(true, $closureCalled);
  }

  public function testHandleIsNotLettingTheRequestThroughIfTheAuthenticatedUserDoesNotHaveTheRequestedRole()
  {
    $closureCalled = false;
    $closure = function ($arg) use (&$closureCalled) {
      $closureCalled = true;
      return('closure return value');
    };
    
    $this->assertEquals(
      \Illuminate\Http\RedirectResponse::class,
      get_class($this->middleware->handle($this->request, $closure, '2'))
    );
    $this->assertEquals(false, $closureCalled);
  }
}