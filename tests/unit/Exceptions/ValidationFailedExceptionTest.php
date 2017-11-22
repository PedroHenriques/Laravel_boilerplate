<?php

namespace Tests\unit\Exceptions;

use App\Exceptions\ValidationFailedException;
use PHPUnit\Framework\TestCase;

class ValidationFailedExceptionTest extends TestCase
{
  public function testTostringItShouldConvertTheErrorsArrayIntoAString()
  {
    $exception = new ValidationFailedException([
      'email' => [
        'The email field is required.',
        'The email field must be an email.'
      ],
      'password' => [
        'The password field must match the confirm password field.'
      ],
    ]);

    $expectedMsg = '<p>email:<ul><li>The email field is required.</li>'.
      '<li>The email field must be an email.</li></ul></p><p>password:'.
      '<ul><li>The password field must match the confirm password field.</li></ul></p>';

    $this->assertEquals($expectedMsg, $exception->__toString());
  }
}