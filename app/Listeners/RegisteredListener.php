<?php

namespace App\Listeners;

use App\Jobs\JobDispatcher;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;

class RegisteredListener extends BaseListener implements ShouldQueue
{
	public $queue = 'medium';
	private $jobDispatcher;

	/**
	 * @param  \App\Jobs\JobDispatcher  $jobDispatcher
	 */
	public function __construct(JobDispatcher $jobDispatcher)
	{
		$this->jobDispatcher = $jobDispatcher;
	}

	/**
	 * Handle the event.
	 *
	 * @param  \Illuminate\Auth\Events\Registered  $event
	 * @return void
	 */
	public function handle(Registered $event)
	{
		$this->jobDispatcher->dispatch('ProcessActivationEmail', [$event->user->email]);
	}
}
