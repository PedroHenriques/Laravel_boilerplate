<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Queue\SerializesModels;

class ActivateAccount extends BaseMailer implements ShouldQueue
{
	use Queueable, SerializesModels;

	private $token;

	/**
	 * Create a new message instance.
	 *
	 * @return void
	 */
	public function __construct(string $token)
	{
		$this->queue = 'medium';
		$this->token = $token;
	}

	/**
	 * Build the message.
	 *
	 * @param \Illuminate\Routing\UrlGenerator $urlGenerator
	 * @param \Illuminate\Contracts\Config\Repository $config
	 * @return $this
	 */
	public function build(UrlGenerator $urlGenerator, Config $config)
	{
		$activationURL = $urlGenerator->route('activation', [
			'e' => $this->to[0]['address'],
			't' => $this->token,
		]);

		return(
			$this->subject($config->get('app.name').' - Account Activation')
			->view('emails.activation', ['activationURL' => $activationURL])
		);
	}
}
