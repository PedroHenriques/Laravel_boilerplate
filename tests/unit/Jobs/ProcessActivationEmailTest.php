<?php

namespace Tests\unit\Jobs;

use App\Jobs\ProcessActivationEmail;
use App\Mail\ActivateAccount;
use App\Utils\{ClassUtils, SecurityUtils};
use Carbon\Carbon;
use Illuminate\Contracts\Config\Repository as config;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Mail\PendingMail;
use PHPUnit\Framework\TestCase;

class ProcessActivationEmailTest extends TestCase
{
  public function setUp()
  {
    $this->connectionInterfaceMock = $this->createMock(ConnectionInterface::class);
    $this->mailerMock = $this->createMock(Mailer::class);
    $this->configMock = $this->createMock(Config::class);
    $this->hasherMock = $this->createMock(Hasher::class);
    $this->securityUtilsMock = $this->createMock(SecurityUtils::class);
    $this->classUtilsMock = $this->createMock(ClassUtils::class);
  }

  public function testTheClassImplementsTheShouldQueueInterface()
  {
    $job = new ProcessActivationEmail('test@test.com');

    $this->assertEquals($job instanceof \Illuminate\Contracts\Queue\ShouldQueue, true);
  }

  public function testTheClassIsQueueingInTheMediumQueue()
  {
    $job = new ProcessActivationEmail('test@test.com');

    $this->assertEquals($job->queue, 'medium');
  }

  public function testHandleItShouldGetTheAppKeyFromTheConfigFileThenGenerateATokenThenInsertOrUpdateTheAccountActivationsTableThenSendTheActivateAccountMailableAndReturnVoid()
  {
    $builderMock = $this->createMock(Builder::class);
    $activateAccountMock = $this->createMock(ActivateAccount::class);
    $pendingMailMock = $this->createMock(PendingMail::class);
    $carbonMock = $this->createMock(Carbon::class);

    $key = 'Sv+0mt+Nvao8/vmsYaUT+wNCrgzWaccKEs6MdPFlM9o=';
    $this->configMock->expects($this->once())
    ->method('get')
    ->with('app.key')
    ->willReturn($key);

    $token = 'this is a test token';
    $this->securityUtilsMock->expects($this->once())
    ->method('createToken')
    ->with($key)
    ->willReturn($token);

    $tokenHash = 'token hash';
    $this->hasherMock->expects($this->once())
    ->method('make')
    ->with($token)
    ->willReturn($tokenHash);

    $this->connectionInterfaceMock->expects($this->once())
    ->method('table')
    ->with('account_activations')
    ->willReturn($builderMock);

    $userEmail = 'test@test.com';
    $builderMock->expects($this->once())
    ->method('updateOrInsert')
    ->with(
      [
        'user_email' => $userEmail,
      ],
      [
        'user_email' => $userEmail,
        'token' => $tokenHash,
        'created_at' => $carbonMock,
      ]
    )
    ->willReturn(true);

    $this->mailerMock->expects($this->once())
    ->method('to')
    ->with($userEmail)
    ->willReturn($pendingMailMock);

    $this->classUtilsMock->expects($this->exactly(2))
    ->method('instantiate')
    ->withConsecutive(
      ['\Carbon\Carbon', ['now']],
      ['\App\Mail\ActivateAccount', [$token]]
    )
    ->will($this->onConsecutiveCalls($carbonMock, $activateAccountMock));

    $pendingMailMock->expects($this->once())
    ->method('send')
    ->with($activateAccountMock)
    ->willReturn(null);

    $job = new ProcessActivationEmail($userEmail);
    $returnValue = $job->handle(
      $this->connectionInterfaceMock, $this->mailerMock, $this->configMock,
      $this->hasherMock, $this->securityUtilsMock, $this->classUtilsMock
    );

    $this->assertNull($returnValue);
  }

  public function testHandleIfTheDbQueryFailsItShouldThrowExceptionWithAnErrorMsg()
  {
    $userEmail = 'test@test.com';

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage(
      "The ProcessActivationEmail Job failed the insert/update query for the User with email '{$userEmail}'"
    );

    $builderMock = $this->createMock(Builder::class);
    $carbonMock = $this->createMock(Carbon::class);

    $key = 'Sv+0mt+Nvao8/vmsYaUT+wNCrgzWaccKEs6MdPFlM9o=';
    $this->configMock->expects($this->once())
    ->method('get')
    ->with('app.key')
    ->willReturn($key);

    $token = 'this is a test token';
    $this->securityUtilsMock->expects($this->once())
    ->method('createToken')
    ->with($key)
    ->willReturn($token);

    $tokenHash = 'token hash';
    $this->hasherMock->expects($this->once())
    ->method('make')
    ->with($token)
    ->willReturn($tokenHash);

    $this->connectionInterfaceMock->expects($this->once())
    ->method('table')
    ->with('account_activations')
    ->willReturn($builderMock);

    $this->classUtilsMock->expects($this->once())
    ->method('instantiate')
    ->with('\Carbon\Carbon', ['now'])
    ->willReturn($carbonMock);

    $builderMock->expects($this->once())
    ->method('updateOrInsert')
    ->with(
      [
        'user_email' => $userEmail,
      ],
      [
        'user_email' => $userEmail,
        'token' => $tokenHash,
        'created_at' => $carbonMock,
      ]
    )
    ->willReturn(false);

    $job = new ProcessActivationEmail($userEmail);
    $job->handle(
      $this->connectionInterfaceMock, $this->mailerMock, $this->configMock,
      $this->hasherMock, $this->securityUtilsMock, $this->classUtilsMock
    );
  }
}