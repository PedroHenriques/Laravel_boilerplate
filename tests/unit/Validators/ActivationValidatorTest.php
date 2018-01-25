<?php

namespace Tests\unit\Validators;

use App\Validators\ActivationValidator;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use PHPUnit\Framework\TestCase;

class ActivationValidatorTest extends TestCase
{
  public function setUp()
  {
    $this->validatorFactoryMock = $this->createMock(ValidatorFactory::class);
    $this->validator = new ActivationValidator($this->validatorFactoryMock);
  }

  public function testItShouldHaveTheCorrectRules()
  {
    $this->assertEquals(
      [
        'activate' => [
          'e' => ['required', 'email'],
          't' => ['required', 'string', 'size:64'],
        ],
        'resend' => [
          'e' => ['required', 'email', 'exists:account_activations,user_email'],
        ],
      ],
      $this->validator->getRules()
    );
  }

  public function testItShouldHaveTheCorrectMessages()
  {
    $this->assertEquals([], $this->validator->getMessages());
  }
}