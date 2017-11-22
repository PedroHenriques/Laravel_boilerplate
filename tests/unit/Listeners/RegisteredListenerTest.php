<?php

namespace Tests\unit\Listeners;

use App\Jobs\JobDispatcher;
use App\Listeners\RegisteredListener;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Bus\PendingDispatch;
use PHPUnit\Framework\TestCase;

class RegisteredListenerTest extends TestCase
{
  public function setUp()
  {
    $this->jobDispatcherMock = $this->createMock(JobDispatcher::class);
    $this->listener = new RegisteredListener($this->jobDispatcherMock);
  }

  public function testTheClassImplementsTheShouldQueueInterface()
  {
    $this->assertEquals($this->listener instanceof \Illuminate\Contracts\Queue\ShouldQueue, true);
  }

  public function testTheClassIsQueueingInTheMediumQueue()
  {
    $this->assertEquals($this->listener->queue, 'medium');
  }

  public function testHandleItShouldDispatchAProcessActivationEmailJobAndReturnVoid()
  {
    $pendingDispatchMock = $this->createMock(PendingDispatch::class);
    $userEmail = 'test@test.com';
    $registeredMock = $this->getMockBuilder(Registered::class)
      ->setMethods(null)
      ->setConstructorArgs([new User(['email' => $userEmail])])
      ->getMock();

    $this->jobDispatcherMock->expects($this->once())
    ->method('dispatch')
    ->with('ProcessActivationEmail', [$userEmail])
    ->willReturn($pendingDispatchMock);

    $this->assertNull($this->listener->handle($registeredMock));
  }
}