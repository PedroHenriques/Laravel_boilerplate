<?php

namespace Tests\unit\Http\Middleware;

use App\Http\Middleware\RedirectIfInactive;
use App\Models\User;
use Illuminate\Auth\SessionGuard as Guard;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Routing\Redirector;
use Illuminate\Session\Store as Session;
use PHPUnit\Framework\TestCase;

class RedirectIfInactiveTest extends TestCase
{
  public function setUp()
  {
    $this->redirectorMock = $this->createMock(Redirector::class);
    $this->authFactoryMock = $this->createMock(AuthFactory::class);
    $this->requestMock = $this->createMock(Request::class);

    $this->middleware = new RedirectIfInactive($this->redirectorMock, $this->authFactoryMock);
  }

  public function testHandleItShouldCheckIfThatTheAuthenticatedUserAccountIsActiveThenCallTheProvidedCallbackAndReturnTheCallbackResult()
  {
    $user = new User();
    $user->is_active = true;

    $this->requestMock->expects($this->once())
    ->method('user')
    ->willReturn($user);

    $closureArg = null;
    $closure = function ($arg) use (&$closureArg) {
      $closureArg = $arg;
      return('closure return value.');
    };

    $this->assertEquals('closure return value.', $this->middleware->handle($this->requestMock, $closure));
    $this->assertEquals($this->requestMock, $closureArg);
  }

  public function testHandleIfTheUserAccountIsNotActiveItShouldLogoutTheAuthenticatedUserThenInvalidateItsSessionAndRedirectToTheLoginRouteWithAnErrorPartial()
  {
    $redirectResponseMock = $this->createMock(RedirectResponse::class);
    $guardMock = $this->createMock(Guard::class);
    $sessionMock = $this->createMock(Session::class);
    $user = new User();
    $user->is_active = false;
    $user->email = 'test@email.com';

    $this->requestMock->expects($this->once())
    ->method('user')
    ->willReturn($user);

    $this->authFactoryMock->expects($this->once())
    ->method('guard')
    ->willReturn($guardMock);

    $guardMock->expects($this->once())
    ->method('logout')
    ->willReturn(null);

    $this->requestMock->expects($this->once())
    ->method('session')
    ->willReturn($sessionMock);

    $sessionMock->expects($this->once())
    ->method('flush')
    ->willReturn(null);

    $this->redirectorMock->expects($this->once())
    ->method('route')
    ->with('login')
    ->willReturn($redirectResponseMock);

    $redirectResponseMock->expects($this->once())
    ->method('with')
    ->with([
      'includes' => ['partials.error.inactive_account'],
      'email' => $user->email,
    ])
    ->will($this->returnSelf());

    $closureArg = null;
    $closure = function ($arg) use (&$closureArg) {
      $closureArg = $arg;
      return('closure return value.');
    };

    $this->assertEquals($redirectResponseMock, $this->middleware->handle($this->requestMock, $closure));
    $this->assertEquals(null, $closureArg);
  }
}