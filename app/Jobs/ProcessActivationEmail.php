<?php

namespace App\Jobs;

use App\Utils\{ClassUtils, SecurityUtils};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};

class ProcessActivationEmail extends BaseJob implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	private $userEmail;

	/**
	 * Create a new job instance.
	 *
	 * @param string $userEmail
	 * @return void
	 */
	public function __construct(string $userEmail)
	{
		$this->queue = 'medium';
		$this->userEmail = $userEmail;
	}

	/**
	 * Execute the job.
	 *
	 * @param \Illuminate\Database\ConnectionInterface $connection
	 * @param \Illuminate\Contracts\Mail\Mailer $mailer
	 * @param \Illuminate\Contracts\Config\Repository $config
	 * @param \Illuminate\Contracts\Hashing\Hasher $hasher
	 * @param \App\Utils\SecurityUtils $securityUtils
	 * @param \App\Utils\ClassUtils $classUtils
	 * @return void
	 */
	public function handle(
		ConnectionInterface $connection,
		Mailer $mailer,
		Config $config,
		Hasher $hasher,
		SecurityUtils $securityUtils,
		ClassUtils $classUtils
	) {
		$token = $securityUtils->createToken($config->get('app.key'));

		$dbModified = $connection->table('account_activations')
			->updateOrInsert(
				[
					'user_email' => $this->userEmail,
				],
				[
					'user_email' => $this->userEmail,
					'token' => $hasher->make($token),
					'created_at' => $classUtils->instantiate('\Carbon\Carbon', ['now']),
				]
			);
		
		if (!$dbModified) {
			throw new \Exception(
				'The ProcessActivationEmail Job failed the insert/update query for '.
				"the User with email '{$this->userEmail}'"
			);
		}

		$mailer->to($this->userEmail)
		->send($classUtils->instantiate('\App\Mail\ActivateAccount', [$token]));
	}
}