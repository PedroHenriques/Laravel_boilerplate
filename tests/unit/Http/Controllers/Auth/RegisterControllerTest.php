<?php

namespace Tests\unit\Http\Controllers\Auth;

use App\Http\Controllers\Auth\RegisterController;
use App\Models\User;
use App\Utils\ClassUtils;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class RegisterControllerTest extends TestCase
{
	public function setUp()
	{
		$this->validatorFactoryMock = $this->createMock(ValidatorFactory::class);
		$this->userMock = $this->createMock(User::class);
		$this->redirectorMock = $this->createMock(Redirector::class);
		$this->hasherMock = $this->createMock(Hasher::class);
		$this->dispatcherMock = $this->createMock(Dispatcher::class);
		$this->classUtilsMock = $this->createMock(ClassUtils::class);
		$this->configMock = $this->createMock(Config::class);

		$this->registerController = new RegisterController(
			$this->validatorFactoryMock, $this->userMock, $this->redirectorMock,
			$this->hasherMock, $this->dispatcherMock, $this->classUtilsMock,
			$this->configMock
		);
	}

	public function testRegisterItShouldGetAllInputDataThenValidateItThenStoreANewUserOnTheDbThenDispatchARegisteredEventAndRedirectToTheLoginRouteWithASuccessMsg()
	{
		$requestMock = $this->createMock(Request::class);
		$validatorMock = $this->createMock(Validator::class);
		$redirectMock = $this->createMock(RedirectResponse::class);
		$registeredMock = $this->createMock(Registered::class);
		$belongsToManyMock = $this->createMock(BelongsToMany::class);

		$inputData = [
			'username' => 'pedro',
			'email' => 'pedro@pedro.com',
			'password' => 'password',
			'password-confirm' => 'password',
		];

		$requestMock->expects($this->once())
		->method('all')
		->willReturn($inputData);

		$this->validatorFactoryMock->expects($this->once())
		->method('make')
		->with($inputData, [
			'username' => 'required|string|max:255|unique:users,username',
			'email' => 'required|string|email|max:255|unique:users,email',
			'password' => 'required|string|min:6|confirmed',
		])
		->willReturn($validatorMock);

		$validatorMock->expects($this->once())
		->method('validate')
		->willReturn(null);

		$hashedPw = 'pw hash';
		$this->hasherMock->expects($this->once())
		->method('make')
		->with($inputData['password'])
		->willReturn($hashedPw);

		$roleId = 1;
		$this->configMock->expects($this->once())
		->method('get')
		->with('roles.newUserRoleId')
		->willReturn($roleId);

		$this->userMock->expects($this->once())
		->method('newInstance')
		->with([
			'username' => $inputData['username'],
			'email' => $inputData['email'],
			'password' => $hashedPw,
			'role_id' => $roleId,
		])
		->will($this->returnSelf());

		$this->userMock->expects($this->once())
		->method('save')
		->willReturn(true);

		$this->classUtilsMock->expects($this->once())
		->method('instantiate')
		->with('\Illuminate\Auth\Events\Registered', [$this->userMock])
		->willReturn($registeredMock);

		$this->dispatcherMock->expects($this->once())
		->method('dispatch')
		->with($registeredMock)
		->willReturn(null);

		$this->redirectorMock->expects($this->once())
		->method('to')
		->with('/login')
		->willReturn($redirectMock);

		$requestMock->expects($this->once())
		->method('input')
		->with('email')
		->willReturn($inputData['email']);

		$redirectMock->expects($this->once())
		->method('with')
		->with([
			'includes' => ['partials.success.registered'],
			'email' => $inputData['email'],
		])
		->will($this->returnSelf());

		$returnValue = $this->registerController->register($requestMock);

		$this->assertEquals($returnValue, $redirectMock);
	}

	public function testRegisterIfValidatingTheInputDataThrowsAValidationexceptionItShouldLetItBubbleUp()
	{
		$this->expectException(ValidationException::class);

		$requestMock = $this->createMock(Request::class);
		$validatorMock = $this->createMock(Validator::class);

		$inputData = [
			'username' => 'pedro',
			'email' => 'pedro@pedro.com',
			'password' => 'password',
			'password-confirm' => 'password',
		];

		$requestMock->expects($this->once())
		->method('all')
		->willReturn($inputData);

		$this->validatorFactoryMock->expects($this->once())
		->method('make')
		->with($inputData, [
			'username' => 'required|string|max:255|unique:users,username',
			'email' => 'required|string|email|max:255|unique:users,email',
			'password' => 'required|string|min:6|confirmed',
		])
		->willReturn($validatorMock);
		
		$validatorMock->expects($this->once())
		->method('validate')
		->will($this->throwException(new ValidationException($validatorMock)));

		$this->registerController->register($requestMock);
	}

	public function testRegisterIfStoringTheUserInTheDbFailsItShouldThrowAnExceptionWithAnErrorMsg()
	{
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage("The new User, with email 'pedro@pedro.com', failed to be saved on the DB.");

		$requestMock = $this->createMock(Request::class);
		$validatorMock = $this->createMock(Validator::class);

		$inputData = [
			'username' => 'pedro',
			'email' => 'pedro@pedro.com',
			'password' => 'password',
			'password-confirm' => 'password',
		];

		$requestMock->expects($this->once())
		->method('all')
		->willReturn($inputData);

		$this->validatorFactoryMock->expects($this->once())
		->method('make')
		->with($inputData, [
			'username' => 'required|string|max:255|unique:users,username',
			'email' => 'required|string|email|max:255|unique:users,email',
			'password' => 'required|string|min:6|confirmed',
		])
		->willReturn($validatorMock);
		
		$validatorMock->expects($this->once())
		->method('validate')
		->willReturn(null);

		$hashedPw = 'pw hash';
		$this->hasherMock->expects($this->once())
		->method('make')
		->with($inputData['password'])
		->willReturn($hashedPw);

		$roleId = 1;
		$this->configMock->expects($this->once())
		->method('get')
		->with('roles.newUserRoleId')
		->willReturn($roleId);

		$this->userMock->expects($this->once())
		->method('newInstance')
		->with([
			'username' => $inputData['username'],
			'email' => $inputData['email'],
			'password' => $hashedPw,
			'role_id' => $roleId,
		])
		->will($this->returnSelf());

		$this->userMock->expects($this->once())
		->method('save')
		->willReturn(false);

		$this->registerController->register($requestMock);
	}
}