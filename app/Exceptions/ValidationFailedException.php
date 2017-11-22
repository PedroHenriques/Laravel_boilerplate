<?php

namespace App\Exceptions;

class ValidationFailedException extends BaseException
{
  private $errors = [];

  /**
   * Create a new instance of this exception.
   * 
   * @param array $errors
   */
  public function __construct(array $errors)
  {
    parent::__construct();

    $this->errors = $errors;
  }

  public function __toString()
  {
    $message = '';
    foreach ($this->errors as $field => $messages) {
      $message .= "<p>${field}:<ul>";
      $message .= array_reduce($messages, function ($carry, $item) {
        return($carry."<li>${item}</li>");
      }, '');
      $message .= '</ul></p>';
    }

    return($message);
  }
}