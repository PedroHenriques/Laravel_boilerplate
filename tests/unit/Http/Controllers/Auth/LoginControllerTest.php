<?php

namespace Tests\unit\Http\Controllers\Auth;

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Validation\Factory as ValidatorFactory;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

/*
	This test suite only covers the methods that were overwritten from the default
	\Illuminate\Foundation\Auth\AuthenticatesUsers
*/
class LoginControllerTest extends TestCase
{
	public function setUp()
	{
		$this->validatorFactoryMock = $this->createMock(ValidatorFactory::class);
	}
	
	public function testValidateloginItShouldGetAllTheInputDataThenValidateItAndReturnVoid()
	{
		$requestMock = $this->createMock(Request::class);
		$loginControllerMock = $this->getMockBuilder(LoginController::class)
			->setMethods([
				'hasTooManyLoginAttempts', 'fireLockoutEvent', 'sendLockoutResponse',
				'attemptLogin', 'sendLoginResponse', 'incrementLoginAttempts',
				'sendFailedLoginResponse',
			])
			->setConstructorArgs([$this->validatorFactoryMock])
			->getMock();

		$inputData = [
			'identifier' => 'test@test.com',
			'password' => 'password',
		];

		$requestMock->expects($this->once())
		->method('all')
		->willReturn($inputData);

		$this->validatorFactoryMock->expects($this->once())
		->method('validate')
		->with($inputData, [
			'identifier' => 'required|string',
			'password' => 'required|string',
		])
		->willReturn(null);

		$loginControllerMock->login($requestMock);
	}

	public function testValidateloginIfTheValidationThrowsAnValidationexceptionItShouldLetItBubbleUp()
	{
		$this->expectException(ValidationException::class);

		$requestMock = $this->createMock(Request::class);
		$loginControllerMock = $this->getMockBuilder(LoginController::class)
			->setMethods([
				'hasTooManyLoginAttempts', 'fireLockoutEvent', 'sendLockoutResponse',
				'attemptLogin', 'sendLoginResponse', 'incrementLoginAttempts',
				'sendFailedLoginResponse',
			])
			->setConstructorArgs([$this->validatorFactoryMock])
			->getMock();

		$inputData = [
			'identifier' => 'test@test.com',
			'password' => 'password',
		];

		$requestMock->expects($this->once())
		->method('all')
		->willReturn($inputData);

		$this->validatorFactoryMock->expects($this->once())
		->method('validate')
		->with($inputData, [
			'identifier' => 'required|string',
			'password' => 'required|string',
		])
		->will($this->throwException(new ValidationException($this->validatorFactoryMock)));

		$loginControllerMock->login($requestMock);
	}

	public function testCredentialsIfProvidedAnEmailItShouldGetTheRelevantInputDataThenFindTheIdentifierColumnNameAndReturnAnArrayWithTheDbReadyCredentials()
	{
		$requestMock = $this->createMock(Request::class);
		$guardMock = $this->createMock(StatefulGuard::class);
		$loginControllerMock = $this->getMockBuilder(LoginController::class)
			->setMethods([
				'validateLogin', 'hasTooManyLoginAttempts', 'fireLockoutEvent',
				'sendLockoutResponse', 'guard', 'sendLoginResponse',
				'incrementLoginAttempts', 'sendFailedLoginResponse',
			])
			->setConstructorArgs([$this->validatorFactoryMock])
			->getMock();

		$loginControllerMock->expects($this->once())
		->method('guard')
		->willReturn($guardMock);

		$inputData = [
			'identifier' => 'test@test.com',
			'password' => 'password',
		];

		$requestMock->expects($this->once())
		->method('only')
		->with('identifier', 'password')
		->willReturn($inputData);

		$guardMock->expects($this->once())
		->method('attempt')
		->with([
			'email' => 'test@test.com',
			'password' => 'password',
		])
		->willReturn(null);

		$loginControllerMock->login($requestMock);
	}

	public function testCredentialsIfProvidedAUsernameItShouldGetTheRelevantInputDataThenFindTheIdentifierColumnNameAndReturnAnArrayWithTheDbReadyCredentials()
	{
		$requestMock = $this->createMock(Request::class);
		$guardMock = $this->createMock(StatefulGuard::class);
		$loginControllerMock = $this->getMockBuilder(LoginController::class)
			->setMethods([
				'validateLogin', 'hasTooManyLoginAttempts', 'fireLockoutEvent',
				'sendLockoutResponse', 'guard', 'sendLoginResponse',
				'incrementLoginAttempts', 'sendFailedLoginResponse',
			])
			->setConstructorArgs([$this->validatorFactoryMock])
			->getMock();

		$loginControllerMock->expects($this->once())
		->method('guard')
		->willReturn($guardMock);

		$inputData = [
			'identifier' => 'test username',
			'password' => 'password',
		];

		$requestMock->expects($this->once())
		->method('only')
		->with('identifier', 'password')
		->willReturn($inputData);

		$guardMock->expects($this->once())
		->method('attempt')
		->with([
			'username' => 'test username',
			'password' => 'password',
		])
		->willReturn(null);

		$loginControllerMock->login($requestMock);
	}
}