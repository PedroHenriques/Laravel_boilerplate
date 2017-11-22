<?php

namespace App\Validators;

class ActivationValidator extends BaseValidator
{
  protected $rules = [
    'activate' => [
      'e' => ['required', 'email'],
			't' => ['required', 'string', 'size:64'],
    ],
    'resend' => [
      'e' => ['required', 'email', 'exists:account_activations,user_email'],
    ],
  ];
}