<?php

namespace Tests\unit\Validators;

use App\Validators\BaseValidator;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class BaseValidatorTest extends TestCase
{
  public function setUp()
  {
    $this->validatorFactoryMock = $this->createMock(ValidatorFactory::class);
  }

  public function testValidateItShouldCreateAValidatorWithTheRulesAndMessagedForTheProvidedRulesetAndReturnTheResultOfPassesOnTheValidator()
  {
    $validatorMock = $this->createMock(Validator::class);

    $data = [
      'email' => 'test@test.com',
      'password' => 'password',
      'password_confirm' => 'password',
    ];
    $ruleSet = 'create';

    $this->validatorFactoryMock->expects($this->once())
    ->method('make')
    ->with(
      $data,
      [
        'email' => 'required|email',
        'password' => 'required|string|min:6|confirmed',
      ],
      []
    )
    ->willReturn($validatorMock);

    $validatorMock->expects($this->once())
    ->method('fails')
    ->willReturn(false);

    $validator = new TestValidator($this->validatorFactoryMock);
    $this->assertEquals(true, $validator->validate($data, $ruleSet));
  }

  public function testValidateIfTheValidationDoesNotPassItShouldStoreTheValidatorErrorsAndReturnFalse()
  {
    $validatorMock = $this->createMock(Validator::class);
    $messageBagMock = $this->createMock(MessageBag::class);

    $data = [
      'email' => 'test@test.com',
      'password' => 'password',
      'password_confirm' => 'password',
    ];
    $ruleSet = 'create';

    $this->validatorFactoryMock->expects($this->once())
    ->method('make')
    ->with(
      $data,
      [
        'email' => 'required|email',
        'password' => 'required|string|min:6|confirmed',
      ],
      []
    )
    ->willReturn($validatorMock);

    $validatorMock->expects($this->once())
    ->method('fails')
    ->willReturn(true);

    $validatorMock->expects($this->once())
    ->method('messages')
    ->willReturn($messageBagMock);

    $validator = new TestValidator($this->validatorFactoryMock);
    $this->assertEquals(false, $validator->validate($data, $ruleSet));

    $messageBagMock->method('toArray')
    ->willReturn(['error msg 1', 'error msg 2']);
    $this->assertEquals(['error msg 1', 'error msg 2'], $validator->getErrors());
  }

  public function testValidateIfItIsCalledMoreThanOnceItShouldClearAnyErrorsFromPreviousCalls()
  {
    $validatorMock = $this->createMock(Validator::class);
    $messageBagMock = $this->createMock(MessageBag::class);

    $data = [
      'email' => 'test@test.com',
      'password' => 'password',
      'password_confirm' => 'password',
    ];
    $ruleSet = 'create';

    $this->validatorFactoryMock->method('make')
    ->with(
      $data,
      [
        'email' => 'required|email',
        'password' => 'required|string|min:6|confirmed',
      ],
      []
    )
    ->willReturn($validatorMock);

    $validatorMock->method('fails')
    ->will($this->onConsecutiveCalls(true, false));

    $validatorMock->expects($this->once())
    ->method('messages')
    ->willReturn($messageBagMock);

    $validator = new TestValidator($this->validatorFactoryMock);
    
    $messageBagMock->method('toArray')
    ->willReturn(['error msg 1', 'error msg 2']);
    $this->assertEquals(false, $validator->validate($data, $ruleSet));
    $this->assertEquals(['error msg 1', 'error msg 2'], $validator->getErrors());
    $this->assertEquals(true, $validator->validate($data, $ruleSet));
    $this->assertEquals([], $validator->getErrors());
  }

  public function testGeterrorsIfNoErrorsAreStoredItShouldReturnAnEmptyArray()
  {
    $validator = new TestValidator($this->validatorFactoryMock);
    $this->assertEquals([], $validator->getErrors());
  }
}

class TestValidator extends BaseValidator
{
  protected $rules = [
    'create' => [
      'email' => ['required', 'email'],
      'password' => ['required', 'string', 'min:6', 'confirmed'],
    ],
    'update' => [
      'username' => ['required', 'string']
    ],
  ];
  protected $messages = [
    'update' => [
      'username.required' => 'The Username field must be filled in.',
    ],
  ];
}