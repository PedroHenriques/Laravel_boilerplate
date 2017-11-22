<?php

namespace App\Services;

use App\Exceptions\{
  AccountActivationException, ExpiredTokenException, InvalidTokenException,
  TokenDeleteException, ValidationFailedException
};
use App\Jobs\JobDispatcher;
use App\Models\User;
use App\Utils\ClassUtils;
use App\Validators\ActivationValidator;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Contracts\Logging\Log;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Request;

class ActivationService extends BaseService
{
  private $tokenType = 'activation';
  private $validator;
  private $connection;
  private $classUtils;
  private $config;
  private $hasher;
  private $eventDispatcher;
  private $userEloquent;
  private $jobDispatcher;
  private $logger;

  /**
   * @param \App\Validators\ActivationValidator $validator
   * @param \Illuminate\Database\ConnectionInterface $connection
   * @param \App\Utils\ClassUtils $classUtils
   * @param \Illuminate\Contracts\Config\Repository $config
   * @param \Illuminate\Contracts\Hashing\Hasher $hasher
   * @param \Illuminate\Contracts\Events\Dispatcher $eventDispatcher
   * @param \App\Models\User $userEloquent
   * @param \App\Jobs\JobDispatcher $jobDispatcher
   * @param \Illuminate\Contracts\Logging\Log $logger
   */
  public function __construct(
    ActivationValidator $validator,
    ConnectionInterface $connection,
    ClassUtils $classUtils,
    Config $config,
    Hasher $hasher,
    EventDispatcher $eventDispatcher,
    User $userEloquent,
    JobDispatcher $jobDispatcher,
    Log $logger
  ) {
    $this->validator = $validator;
    $this->connection = $connection;
    $this->classUtils = $classUtils;
    $this->config = $config;
    $this->hasher = $hasher;
    $this->eventDispatcher = $eventDispatcher;
    $this->userEloquent = $userEloquent;
    $this->jobDispatcher = $jobDispatcher;
    $this->logger = $logger;
  }

  /**
   * Handles activating a user's account.
   * 
   * @param \Illuminate\Http\Request $request
   * @return void
   * @throws \App\Exceptions\ValidationFailedException
   * @throws \App\Exceptions\InvalidTokenException
   * @throws \App\Exceptions\ExpiredTokenException
   * @throws \App\Exceptions\AccountActivationException
   * @throws \App\Exceptions\TokenDeleteException
   */
  public function activate(Request $request): void
  {
    $inputData = $request->all();
    if (!$this->validator->validate($inputData, 'activate')) {
      throw new ValidationFailedException($this->validator->getErrors());
    }

    $tokenObj = $this->connection->table('account_activations')
      ->select(['token', 'created_at'])
      ->where('user_email', '=', $inputData['e'])
      ->first();
    if (
      $tokenObj === null ||
      !$this->hasher->check($inputData['t'], $tokenObj->token)
    ) {
      throw new InvalidTokenException($this->tokenType);
    }

    if (
      $this->classUtils->instantiate('\Carbon\Carbon', [$tokenObj->created_at])
      ->addMinutes($this->config->get('auth.activation.token.expire', 60))
      ->isPast()
    ) {
      $this->dispatchJob($inputData['e']);
      throw new ExpiredTokenException($this->tokenType);
    }

    $user = $this->userEloquent->newQuery()
      ->where('email', '=', $inputData['e'])
      ->first();
    if ($user === null || !$user->update(['is_active' => 1])) {
      throw new AccountActivationException($inputData['e']);
    }

    $this->eventDispatcher->dispatch(
      $this->classUtils->instantiate('\App\Events\AccountActivated', [$user])
    );

    if ($this->connection->table('account_activations')
      ->where('user_email', '=', $inputData['e'])
      ->delete() === 0
    ) {
      $exception = new TokenDeleteException($inputData['e'], $this->tokenType);
      $this->logger->error($exception);
      throw $exception;
    }
  }

  /**
   * Handles generating a new activation token and sending a new activation
   * email.
   * 
   * @param \Illuminate\Http\Request $request
   * @return void
   * @throws \App\Exceptions\ValidationFailedException
   */
  public function resend(Request $request): void
  {
    $inputData = $request->all();
    if (!$this->validator->validate($inputData, 'resend')) {
      throw new ValidationFailedException($this->validator->getErrors());
    }
    $this->dispatchJob($inputData['e']);
  }

  /**
   * Calls the JobDispatcher to dispatch a ProcessActivationEmail job.
   * 
   * @param string $email
   * @return void
   */
  private function dispatchJob(string $email): void
  {
    $this->jobDispatcher->dispatch(
      'ProcessActivationEmail', [$email]
    );
  }
}