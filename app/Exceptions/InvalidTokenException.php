<?php

namespace App\Exceptions;

class InvalidTokenException extends BaseException
{
  private $tokenType = '';

  /**
   * Create a new instance of this exception.
   * 
   * @param string $tokenType
   */
  public function __construct(string $tokenType)
  {
    parent::__construct();

    $this->tokenType = $tokenType;
  }

  public function __toString()
  {
    return("The provided {$this->tokenType} token is not valid.");
  }
}