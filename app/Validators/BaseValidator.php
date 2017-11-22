<?php

namespace App\Validators;

use Illuminate\Contracts\Validation\Factory as ValidatorFactory;

abstract class BaseValidator
{
  private $validatorFactory;
  /**
   * @var null|\Illuminate\Support\MessageBag
   */
  protected $errors;
  /**
   * @var array
   */
  protected $rules = [];
  /**
   * @var array
   */
  protected $messages = [];

  /**
   * @param \Illuminate\Contracts\Validation\Factory $validatorFactory
   */
  public function __construct(ValidatorFactory $validatorFactory)
  {
    $this->validatorFactory = $validatorFactory;
  }

  /**
   * Validated the provided data against the provided rule set.
   * 
   * @param array $data
   * @param string $ruleSet
   * @return bool
   */
  public function validate(array $data, string $ruleSet): bool
  {
    $this->errors = null;

    $rules = array_map(function ($ruleData) {
      return(implode('|', $ruleData));
    }, $this->rules[$ruleSet]);
    $messages = $this->messages[$ruleSet] ?? [];
    $validator = $this->validatorFactory->make($data, $rules, $messages);

    if ($validator->fails()) {
      $this->errors = $validator->messages();
      return(false);
    }

    return(true);
  }

  /**
   * Returns the error messages generated by the last call to validate().
   * 
   * @return array
   */
  public function getErrors()
  {
    return($this->errors === null ? [] : $this->errors->toArray());
  }
}