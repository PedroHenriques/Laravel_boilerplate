<?php

namespace Tests\unit\Mail;

use App\Mail\ActivateAccount;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Routing\UrlGenerator;
use PHPUnit\Framework\TestCase;

class ActivateAccountTest extends TestCase
{
  private $token = '8ce796494165757fdbe1430c38febddc2460864e6c5ac0c0a18d612f72bd3fa2';
  private $email = 'test@test.com';

  public function setUp()
  {
    $this->urlGeneratorMock = $this->createMock(UrlGenerator::class);
    $this->configMock = $this->createMock(Config::class);

    $this->activateAccount = $this->getMockBuilder(ActivateAccount::class)
      ->setMethods(['subject', 'view'])
      ->setConstructorArgs([$this->token])
      ->getMock();
    $this->activateAccount->to = [['address' => $this->email]];
  }

  public function testTheClassImplementsTheShouldQueueInterface()
  {
    $this->assertEquals($this->activateAccount instanceof \Illuminate\Contracts\Queue\ShouldQueue, true);
  }

  public function testTheClassIsQueueingInTheMediumQueue()
  {
    $this->assertEquals($this->activateAccount->queue, 'medium');
  }

  public function testBuildItShouldGetTheUrlForTheActivationRouteThenSetTheMailableSubjectThenAttachAViewToTheMailableAndReturnItself()
  {
    $routeUrl = 'test route url';
    $this->urlGeneratorMock->expects($this->once())
    ->method('route')
    ->with(
      'activation',
      [
        'e' => $this->email,
        't' => $this->token,
      ]
    )
    ->willReturn($routeUrl);

    $appName = 'test app name';
    $this->configMock->expects($this->once())
    ->method('get')
    ->with('app.name')
    ->willReturn($appName);

    $this->activateAccount->expects($this->once())
    ->method('subject')
    ->with("${appName} - Account Activation")
    ->will($this->returnSelf());

    $this->activateAccount->expects($this->once())
    ->method('view')
    ->with('emails.activation', ['activationURL' => $routeUrl])
    ->will($this->returnSelf());

    $returnValue = $this->activateAccount->build($this->urlGeneratorMock, $this->configMock);

    $this->assertEquals($returnValue, $this->activateAccount);
  }
}