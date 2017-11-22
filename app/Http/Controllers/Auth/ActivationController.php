<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\{ExpiredTokenException, TokenDeleteException};
use App\Http\Controllers\Controller;
use App\Services\ActivationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

class ActivationController extends Controller
{
	private $redirector;
	private $activationService;

	/**
	 * Create a new controller instance.
	 *
	 * @param \Illuminate\Routing\Redirector $redirector
	 * @param \App\Services\ActivationService $activationService
	 * @return void
	 */
	public function __construct(
		Redirector $redirector,
		ActivationService $activationService
	) {
		$this->redirector = $redirector;
		$this->activationService = $activationService;

		$this->middleware('guest');
	}

	public function activate(Request $request)
	{
		try {
			$this->activationService->activate($request);
		} catch (ExpiredTokenException $e) {
			return(
				$this->redirector->route('landing')
				->with('includes', ['partials.error.activation_resent'])
			);
		} catch (TokenDeleteException $e) {
		} catch (\Exception $e) {
			return(
				$this->redirector->route('landing')
				->with('includes', ['partials.error.activation_generic'])
			);
		}

		return(
			$this->redirector->route('login')
			->with('includes', ['partials.success.account_activated'])
		);
	}

	public function resend(Request $request)
	{
		try {
			$this->activationService->resend($request);
		} catch (\Exception $e) {
			return(
				$this->redirector->route('landing')
				->with([
					'includes' => ['partials.error.resend_activation'],
					'email' => $request->query('e'),
				])
			);
		}

		return(
			$this->redirector->route('landing')
			->with('includes', ['partials.success.resend_activation'])
		);
	}
}
