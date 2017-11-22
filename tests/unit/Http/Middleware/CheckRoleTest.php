<?php

namespace Tests\unit\Http\Middleware;

use App\Http\Middleware\CheckRole;
use App\Models\User;
use App\Services\RoleManager;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Routing\Redirector;
use PHPUnit\Framework\TestCase;

class CheckRoleTest extends TestCase
{
  public function setUp()
  {
    $this->redirectorMock = $this->createMock(Redirector::class);
    $this->roleManagerMock = $this->createMock(RoleManager::class);
    $this->requestMock = $this->createMock(Request::class);

    $this->checkRole = new CheckRole($this->redirectorMock, $this->roleManagerMock);
  }

  public function testHandleItShouldAskTheRoleManagerIfTheAuthenticatedUserHasTheRequestedRoleAndCallTheClosureWithTheRequestObject()
  {
    $userModel = new User();

    $this->requestMock->expects($this->once())
    ->method('user')
    ->willReturn($userModel);

    $this->roleManagerMock->expects($this->once())
    ->method('has')
    ->with($userModel, 2)
    ->willReturn(true);

    $closureCalled = false;
    $closureArg = null;
    $closure = function ($arg) use (&$closureCalled, &$closureArg) {
      $closureCalled = true;
      $closureArg = $arg;
      return('closure return value');
    };

    $this->assertEquals(
      'closure return value', $this->checkRole->handle($this->requestMock, $closure, '2')
    );
    $this->assertEquals(true, $closureCalled);
    $this->assertEquals($this->requestMock, $closureArg);
  }

  public function testHandleIfTheAuthenticatedUserDoesNotHaveTheRequestedRoleItShouldRedirectTheUserBack()
  {
    $redirectResponseMock = $this->createMock(RedirectResponse::class);
    $userModel = new User();

    $this->requestMock->expects($this->once())
    ->method('user')
    ->willReturn($userModel);

    $this->roleManagerMock->expects($this->once())
    ->method('has')
    ->with($userModel, 2)
    ->willReturn(false);

    $this->redirectorMock->expects($this->once())
    ->method('back')
    ->willReturn($redirectResponseMock);

    $closureCalled = false;
    $closureArg = null;
    $closure = function ($arg) use (&$closureCalled, &$closureArg) {
      $closureCalled = true;
      $closureArg = $arg;
      return('closure return value');
    };

    $this->assertEquals(
      $redirectResponseMock, $this->checkRole->handle($this->requestMock, $closure, '2')
    );
    $this->assertEquals(false, $closureCalled);
    $this->assertEquals(null, $closureArg);
  }
}