<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Queue\SerializesModels;

class AccountActivated extends BaseEvent
{
  use SerializesModels;
  
  /**
   * @var \App\Models\User
   */
  public $user;
  
  /**
   * Create a new event instance.
   *
   * @param \App\Models\User $user
   * @return void
   */
  public function __construct(User $user)
  {
    $this->user = $user;
  }
}
