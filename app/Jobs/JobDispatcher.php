<?php

namespace App\Jobs;

use App\Utils\ClassUtils;
use Illuminate\Foundation\Bus\PendingDispatch;

class JobDispatcher
{
  private $classUtils;

  /**
   * @param \App\Utils\ClassUtils $classUtils
   */
  public function __construct(ClassUtils $classUtils)
  {
    $this->classUtils = $classUtils;
  }

  /**
   * Creates and dispatches an instance of the requested job class.
   * 
   * @param string $jobClass if not a FQN it will be relative to App\Jobs.
   * @param array $args
   * @return \Illuminate\Foundation\Bus\PendingDispatch
   */
  public function dispatch(string $jobClass, array $args): PendingDispatch
  {
    return(
      new PendingDispatch(
        $this->classUtils->instantiate($jobClass, $args, '\App\Jobs')
      )
    );
  }
}