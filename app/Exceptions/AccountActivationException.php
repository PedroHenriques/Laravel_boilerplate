<?php

namespace App\Exceptions;

class AccountActivationException extends BaseException
{
  private $email = '';

  /**
   * Create a new instance of this exception.
   * 
   * @param string $email
   */
  public function __construct(string $email)
  {
    parent::__construct();

    $this->email = $email;
  }

  public function __toString()
  {
    return("Failed to activate the account with email {$this->email}.");
  }
}