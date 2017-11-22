<?php

namespace Tests\unit\Services;

use App\Events\AccountActivated;
use App\Exceptions\{
  AccountActivationException, ExpiredTokenException, InvalidTokenException,
  TokenDeleteException, ValidationFailedException
};
use App\Jobs\JobDispatcher;
use App\Models\User;
use App\Services\ActivationService;
use App\Utils\ClassUtils;
use App\Validators\ActivationValidator;
use Carbon\Carbon;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Logging\Log;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class ActivationServiceTest extends TestCase
{
  public function setUp()
  {
    $this->activationValidatorMock = $this->createMock(ActivationValidator::class);
    $this->connectionInterfaceMock = $this->createMock(ConnectionInterface::class);
    $this->classUtilsMock = $this->createMock(ClassUtils::class);
    $this->configMock = $this->createMock(Config::class);
    $this->hasherMock = $this->createMock(Hasher::class);
    $this->eventDispatcherMock = $this->createMock(EventDispatcher::class);
    $this->userMock = $this->createMock(User::class);
    $this->jobDispatcherMock = $this->createMock(JobDispatcher::class);
    $this->logMock = $this->createMock(Log::class);
    $this->requestMock = $this->createMock(Request::class);

    $this->service = new ActivationService(
      $this->activationValidatorMock, $this->connectionInterfaceMock, $this->classUtilsMock,
      $this->configMock, $this->hasherMock, $this->eventDispatcherMock, $this->userMock,
      $this->jobDispatcherMock, $this->logMock
    );
  }

  public function testActivateItShouldGetAllTheInputDataFromTheRequestThenValidateItAgainstTheActivateRulesetThenGetTheTokenInfoFromTheDbThenCheckIfItIsNotExpiredThenActivateTheUserAccountThenDispatchAnEventThenDeleteTheTokenAndReturnVoid()
  {
    $builderMock = $this->createMock(Builder::class);
    $userModel = $this->createMock(User::class);
    $tokenExpireCarbonMock = $this->createMock(Carbon::class);
    $eventMock = $this->createMock(AccountActivated::class);

    $inputData = [
      'e' => 'test@test.com',
      't' => 'test activation token',
    ];

    $this->requestMock->expects($this->once())
    ->method('all')
    ->willReturn($inputData);

    $this->activationValidatorMock->expects($this->once())
    ->method('validate')
    ->with($inputData, 'activate')
    ->willReturn(true);

    $this->connectionInterfaceMock->expects($this->exactly(2))
    ->method('table')
    ->with('account_activations')
    ->willReturn($builderMock);

    $builderMock->expects($this->exactly(3))
    ->method('where')
    ->withConsecutive(
      [
        'user_email', '=', $inputData['e']
      ],
      [
        'email', '=', $inputData['e']
      ],
      [
        'user_email', '=', $inputData['e']
      ]
    )
    ->will($this->returnSelf());

    $builderMock->expects($this->once())
    ->method('select')
    ->with(['token', 'created_at'])
    ->will($this->returnSelf());

    $tokenCreatedAt = 123456789;
    $tokenHash = 'token hash';
    $tokenObj = new class ($tokenHash, $tokenCreatedAt) {
      public $token;
      public $created_at;
      public function __construct($token, $tokenCreatedAt) {
        $this->token = $token;
        $this->created_at = $tokenCreatedAt;
      }
    };
    
    $builderMock->expects($this->exactly(2))
    ->method('first')
    ->will($this->onConsecutiveCalls($tokenObj, $this->userMock));

    $this->hasherMock->expects($this->once())
    ->method('check')
    ->with($inputData['t'], $tokenHash)
    ->willReturn(true);

    $this->classUtilsMock->expects($this->exactly(2))
    ->method('instantiate')
    ->withConsecutive(
      ['\Carbon\Carbon', [$tokenCreatedAt]],
      ['\App\Events\AccountActivated', [$userModel]]
    )
    ->will($this->onConsecutiveCalls($tokenExpireCarbonMock, $eventMock));

    $tokenExpire = 30;
    $this->configMock->expects($this->once())
    ->method('get')
    ->with('auth.activation.token.expire', 60)
    ->willReturn($tokenExpire);

    $tokenExpireCarbonMock->expects($this->once())
    ->method('addMinutes')
    ->with($tokenExpire)
    ->will($this->returnSelf());
    
    $tokenExpireCarbonMock->expects($this->once())
    ->method('isPast')
    ->willReturn(false);

    $this->userMock->expects($this->once())
    ->method('newQuery')
    ->willReturn($builderMock);

    $this->userMock->expects($this->once())
    ->method('update')
    ->with(['is_active' => 1])
    ->willReturn(true);

    $this->eventDispatcherMock->expects($this->once())
    ->method('dispatch')
    ->with($eventMock)
    ->willReturn([]);
    
    $builderMock->expects($this->once())
    ->method('delete')
    ->willReturn(1);

    $this->assertEquals(null, $this->service->activate($this->requestMock));
  }

  public function testActivateIfTheValidationFailsItShouldThrowAValidationfailedexception()
  {
    $this->expectException(ValidationFailedException::class);

    $inputData = [
      'e' => 'test@test.com',
      't' => 'test activation token',
    ];

    $this->requestMock->expects($this->once())
    ->method('all')
    ->willReturn($inputData);

    $this->activationValidatorMock->expects($this->once())
    ->method('validate')
    ->with($inputData, 'activate')
    ->willReturn(false);

    $this->activationValidatorMock->expects($this->once())
    ->method('getErrors')
    ->willReturn(['test error msg']);

    $this->service->activate($this->requestMock);
  }

  public function testActivateIfNoTokenIsFoundInTheDbItShouldThrowAnInvalidtokenexception()
  {
    $this->expectException(InvalidTokenException::class);

    $builderMock = $this->createMock(Builder::class);

    $inputData = [
      'e' => 'test@test.com',
      't' => 'test activation token',
    ];

    $this->requestMock->expects($this->once())
    ->method('all')
    ->willReturn($inputData);

    $this->activationValidatorMock->expects($this->once())
    ->method('validate')
    ->with($inputData, 'activate')
    ->willReturn(true);

    $this->connectionInterfaceMock->expects($this->once())
    ->method('table')
    ->with('account_activations')
    ->willReturn($builderMock);

    $builderMock->expects($this->once())
    ->method('where')
    ->with('user_email', '=', $inputData['e'])
    ->will($this->returnSelf());

    $builderMock->expects($this->once())
    ->method('select')
    ->with(['token', 'created_at'])
    ->will($this->returnSelf());
    
    $builderMock->expects($this->once())
    ->method('first')
    ->willReturn(null);

    $this->service->activate($this->requestMock);
  }

  public function testActivateIfTheProvidedTokenDoesNotMatchTheTokenInTheDbItShouldThrowAnInvalidtokenexception()
  {
    $this->expectException(InvalidTokenException::class);

    $builderMock = $this->createMock(Builder::class);

    $inputData = [
      'e' => 'test@test.com',
      't' => 'test activation token',
    ];

    $this->requestMock->expects($this->once())
    ->method('all')
    ->willReturn($inputData);

    $this->activationValidatorMock->expects($this->once())
    ->method('validate')
    ->with($inputData, 'activate')
    ->willReturn(true);

    $this->connectionInterfaceMock->expects($this->once())
    ->method('table')
    ->with('account_activations')
    ->willReturn($builderMock);

    $builderMock->expects($this->once())
    ->method('where')
    ->with('user_email', '=', $inputData['e'])
    ->will($this->returnSelf());

    $builderMock->expects($this->once())
    ->method('select')
    ->with(['token', 'created_at'])
    ->will($this->returnSelf());

    $tokenCreatedAt = 123456789;
    $tokenHash = 'token hash';
    $tokenObj = new class ($tokenHash, $tokenCreatedAt) {
      public $token;
      public $created_at;
      public function __construct($token, $tokenCreatedAt) {
        $this->token = $token;
        $this->created_at = $tokenCreatedAt;
      }
    };
    
    $builderMock->expects($this->once())
    ->method('first')
    ->willReturn($tokenObj);

    $this->hasherMock->expects($this->once())
    ->method('check')
    ->with($inputData['t'], $tokenHash)
    ->willReturn(false);

    $this->service->activate($this->requestMock);
  }

  public function testActivateIfTheTokenHasExpiredItShouldDispatchAProcessactivationemailJobAndThrowAnExpiredtokenexception()
  {
    $this->expectException(ExpiredTokenException::class);

    $builderMock = $this->createMock(Builder::class);
    $tokenExpireCarbonMock = $this->createMock(Carbon::class);
    $pendingDispatchMock = $this->createMock(PendingDispatch::class);

    $inputData = [
      'e' => 'test@test.com',
      't' => 'test activation token',
    ];

    $this->requestMock->expects($this->once())
    ->method('all')
    ->willReturn($inputData);

    $this->activationValidatorMock->expects($this->once())
    ->method('validate')
    ->with($inputData, 'activate')
    ->willReturn(true);

    $this->connectionInterfaceMock->expects($this->once())
    ->method('table')
    ->with('account_activations')
    ->willReturn($builderMock);

    $builderMock->expects($this->once())
    ->method('where')
    ->with('user_email', '=', $inputData['e'])
    ->will($this->returnSelf());

    $builderMock->expects($this->once())
    ->method('select')
    ->with(['token', 'created_at'])
    ->will($this->returnSelf());

    $tokenCreatedAt = 123456789;
    $tokenHash = 'token hash';
    $tokenObj = new class ($tokenHash, $tokenCreatedAt) {
      public $token;
      public $created_at;
      public function __construct($token, $tokenCreatedAt) {
        $this->token = $token;
        $this->created_at = $tokenCreatedAt;
      }
    };
    
    $builderMock->expects($this->once())
    ->method('first')
    ->willReturn($tokenObj);

    $this->hasherMock->expects($this->once())
    ->method('check')
    ->with($inputData['t'], $tokenHash)
    ->willReturn(true);

    $this->classUtilsMock->expects($this->once())
    ->method('instantiate')
    ->with('\Carbon\Carbon', [$tokenCreatedAt])
    ->willReturn($tokenExpireCarbonMock);

    $tokenExpire = 30;
    $this->configMock->expects($this->once())
    ->method('get')
    ->with('auth.activation.token.expire', 60)
    ->willReturn($tokenExpire);

    $tokenExpireCarbonMock->expects($this->once())
    ->method('addMinutes')
    ->with($tokenExpire)
    ->will($this->returnSelf());
    
    $tokenExpireCarbonMock->expects($this->once())
    ->method('isPast')
    ->willReturn(true);

    $this->jobDispatcherMock->expects($this->once())
    ->method('dispatch')
    ->with('ProcessActivationEmail', [$inputData['e']])
    ->willReturn($pendingDispatchMock);

    $this->service->activate($this->requestMock);
  }

  public function testActivateIfTheUserDataFailsToBeQueriedItShouldThrowAnAccountactivationexception()
  {
    $this->expectException(AccountActivationException::class);

    $builderMock = $this->createMock(Builder::class);
    $userModel = $this->createMock(User::class);
    $tokenExpireCarbonMock = $this->createMock(Carbon::class);

    $inputData = [
      'e' => 'test@test.com',
      't' => 'test activation token',
    ];

    $this->requestMock->expects($this->once())
    ->method('all')
    ->willReturn($inputData);

    $this->activationValidatorMock->expects($this->once())
    ->method('validate')
    ->with($inputData, 'activate')
    ->willReturn(true);

    $this->connectionInterfaceMock->expects($this->once())
    ->method('table')
    ->with('account_activations')
    ->willReturn($builderMock);

    $builderMock->expects($this->exactly(2))
    ->method('where')
    ->withConsecutive(
      [
        'user_email', '=', $inputData['e']
      ],
      [
        'email', '=', $inputData['e']
      ]
    )
    ->will($this->returnSelf());

    $builderMock->expects($this->once())
    ->method('select')
    ->with(['token', 'created_at'])
    ->will($this->returnSelf());

    $tokenCreatedAt = 123456789;
    $tokenHash = 'token hash';
    $tokenObj = new class ($tokenHash, $tokenCreatedAt) {
      public $token;
      public $created_at;
      public function __construct($token, $tokenCreatedAt) {
        $this->token = $token;
        $this->created_at = $tokenCreatedAt;
      }
    };
    
    $builderMock->expects($this->exactly(2))
    ->method('first')
    ->will($this->onConsecutiveCalls($tokenObj, null));

    $this->hasherMock->expects($this->once())
    ->method('check')
    ->with($inputData['t'], $tokenHash)
    ->willReturn(true);

    $this->classUtilsMock->expects($this->once())
    ->method('instantiate')
    ->with('\Carbon\Carbon', [$tokenCreatedAt])
    ->willReturn($tokenExpireCarbonMock);

    $tokenExpire = 30;
    $this->configMock->expects($this->once())
    ->method('get')
    ->with('auth.activation.token.expire', 60)
    ->willReturn($tokenExpire);

    $tokenExpireCarbonMock->expects($this->once())
    ->method('addMinutes')
    ->with($tokenExpire)
    ->will($this->returnSelf());
    
    $tokenExpireCarbonMock->expects($this->once())
    ->method('isPast')
    ->willReturn(false);

    $this->userMock->expects($this->once())
    ->method('newQuery')
    ->willReturn($builderMock);

    $this->service->activate($this->requestMock);
  }

  public function testActivateIfTheUpdateOnTheUserModelFailsItShouldThrowAnAccountactivationexception()
  {
    $this->expectException(AccountActivationException::class);

    $builderMock = $this->createMock(Builder::class);
    $userModel = $this->createMock(User::class);
    $tokenExpireCarbonMock = $this->createMock(Carbon::class);

    $inputData = [
      'e' => 'test@test.com',
      't' => 'test activation token',
    ];

    $this->requestMock->expects($this->once())
    ->method('all')
    ->willReturn($inputData);

    $this->activationValidatorMock->expects($this->once())
    ->method('validate')
    ->with($inputData, 'activate')
    ->willReturn(true);

    $this->connectionInterfaceMock->expects($this->once())
    ->method('table')
    ->with('account_activations')
    ->willReturn($builderMock);

    $builderMock->expects($this->exactly(2))
    ->method('where')
    ->withConsecutive(
      [
        'user_email', '=', $inputData['e']
      ],
      [
        'email', '=', $inputData['e']
      ]
    )
    ->will($this->returnSelf());

    $builderMock->expects($this->once())
    ->method('select')
    ->with(['token', 'created_at'])
    ->will($this->returnSelf());

    $tokenCreatedAt = 123456789;
    $tokenHash = 'token hash';
    $tokenObj = new class ($tokenHash, $tokenCreatedAt) {
      public $token;
      public $created_at;
      public function __construct($token, $tokenCreatedAt) {
        $this->token = $token;
        $this->created_at = $tokenCreatedAt;
      }
    };
    
    $builderMock->expects($this->exactly(2))
    ->method('first')
    ->will($this->onConsecutiveCalls($tokenObj, $this->userMock));

    $this->hasherMock->expects($this->once())
    ->method('check')
    ->with($inputData['t'], $tokenHash)
    ->willReturn(true);

    $this->classUtilsMock->expects($this->once())
    ->method('instantiate')
    ->with('\Carbon\Carbon', [$tokenCreatedAt])
    ->willReturn($tokenExpireCarbonMock);

    $tokenExpire = 30;
    $this->configMock->expects($this->once())
    ->method('get')
    ->with('auth.activation.token.expire', 60)
    ->willReturn($tokenExpire);

    $tokenExpireCarbonMock->expects($this->once())
    ->method('addMinutes')
    ->with($tokenExpire)
    ->will($this->returnSelf());
    
    $tokenExpireCarbonMock->expects($this->once())
    ->method('isPast')
    ->willReturn(false);

    $this->userMock->expects($this->once())
    ->method('newQuery')
    ->willReturn($builderMock);

    $this->userMock->expects($this->once())
    ->method('update')
    ->with(['is_active' => 1])
    ->willReturn(false);

    $this->service->activate($this->requestMock);
  }

  public function testActivateIfTheTokenFailsToBeDeletedItShouldLogAnErrorAndThrowATokendeleteexception()
  {
    $this->expectException(TokenDeleteException::class);

    $builderMock = $this->createMock(Builder::class);
    $userModel = $this->createMock(User::class);
    $tokenExpireCarbonMock = $this->createMock(Carbon::class);
    $eventMock = $this->createMock(AccountActivated::class);

    $inputData = [
      'e' => 'test@test.com',
      't' => 'test activation token',
    ];

    $this->requestMock->expects($this->once())
    ->method('all')
    ->willReturn($inputData);

    $this->activationValidatorMock->expects($this->once())
    ->method('validate')
    ->with($inputData, 'activate')
    ->willReturn(true);

    $this->connectionInterfaceMock->expects($this->exactly(2))
    ->method('table')
    ->with('account_activations')
    ->willReturn($builderMock);

    $builderMock->expects($this->exactly(3))
    ->method('where')
    ->withConsecutive(
      [
        'user_email', '=', $inputData['e']
      ],
      [
        'email', '=', $inputData['e']
      ],
      [
        'user_email', '=', $inputData['e']
      ]
    )
    ->will($this->returnSelf());

    $builderMock->expects($this->once())
    ->method('select')
    ->with(['token', 'created_at'])
    ->will($this->returnSelf());

    $tokenCreatedAt = 123456789;
    $tokenHash = 'token hash';
    $tokenObj = new class ($tokenHash, $tokenCreatedAt) {
      public $token;
      public $created_at;
      public function __construct($token, $tokenCreatedAt) {
        $this->token = $token;
        $this->created_at = $tokenCreatedAt;
      }
    };
    
    $builderMock->expects($this->exactly(2))
    ->method('first')
    ->will($this->onConsecutiveCalls($tokenObj, $this->userMock));

    $this->hasherMock->expects($this->once())
    ->method('check')
    ->with($inputData['t'], $tokenHash)
    ->willReturn(true);

    $this->classUtilsMock->expects($this->exactly(2))
    ->method('instantiate')
    ->withConsecutive(
      ['\Carbon\Carbon', [$tokenCreatedAt]],
      ['\App\Events\AccountActivated', [$userModel]]
    )
    ->will($this->onConsecutiveCalls($tokenExpireCarbonMock, $eventMock));

    $tokenExpire = 30;
    $this->configMock->expects($this->once())
    ->method('get')
    ->with('auth.activation.token.expire', 60)
    ->willReturn($tokenExpire);

    $tokenExpireCarbonMock->expects($this->once())
    ->method('addMinutes')
    ->with($tokenExpire)
    ->will($this->returnSelf());
    
    $tokenExpireCarbonMock->expects($this->once())
    ->method('isPast')
    ->willReturn(false);

    $this->userMock->expects($this->once())
    ->method('newQuery')
    ->willReturn($builderMock);

    $this->userMock->expects($this->once())
    ->method('update')
    ->with(['is_active' => 1])
    ->willReturn(true);

    $this->eventDispatcherMock->expects($this->once())
    ->method('dispatch')
    ->with($eventMock)
    ->willReturn([]);
    
    $builderMock->expects($this->once())
    ->method('delete')
    ->willReturn(0);

    $this->logMock->expects($this->once())
    ->method('error')
    ->with($this->callback(function ($subject) {
      return(get_class($subject) === TokenDeleteException::class);
    }))
    ->willReturn(null);

    $this->service->activate($this->requestMock);
  }

  public function testResendItShouldGetTheUserInputThenValidateItThenDispatchAProcessactivationemailJobAndReturnVoid()
  {
    $pendingDispatchMock = $this->createMock(PendingDispatch::class);

    $inputData = [
      'e' => 'test@test.com',
    ];

    $this->requestMock->expects($this->once())
    ->method('all')
    ->willReturn($inputData);

    $this->activationValidatorMock->expects($this->once())
    ->method('validate')
    ->with($inputData, 'resend')
    ->willReturn(true);

    $this->jobDispatcherMock->expects($this->once())
    ->method('dispatch')
    ->with('ProcessActivationEmail', [$inputData['e']])
    ->willReturn($pendingDispatchMock);

    $this->assertEquals(null, $this->service->resend($this->requestMock));
  }

  public function testResendIfTheValidationFailsItShouldThrowAValidationfailedexception()
  {
    $this->expectException(ValidationFailedException::class);

    $inputData = [
      'e' => 'test@test.com',
    ];

    $this->requestMock->expects($this->once())
    ->method('all')
    ->willReturn($inputData);

    $this->activationValidatorMock->expects($this->once())
    ->method('validate')
    ->with($inputData, 'resend')
    ->willReturn(false);

    $this->activationValidatorMock->expects($this->once())
    ->method('getErrors')
    ->willReturn(['test error msg']);

    $this->service->resend($this->requestMock);
  }
}