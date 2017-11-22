<?php

namespace App\Exceptions;

class TokenDeleteException extends BaseException
{
  private $email = '';
  private $tokenType = '';

  /**
   * Create a new instance of this exception.
   * 
   * @param string $email
   * @param string $tokenType
   */
  public function __construct(string $email, string $tokenType)
  {
    parent::__construct();

    $this->email = $email;
    $this->tokenType = $tokenType;
  }

  public function __toString()
  {
    return("Failed to delete the {$this->tokenType} token for the email {$this->email}.");
  }
}