<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

class RedirectIfInactive
{
	private $redirector;
	private $authFactory;

	/**
	 * @param \Illuminate\Routing\Redirector $redirector
	 * @param \Illuminate\Contracts\Auth\Factory $authFactory
	 */
	public function __construct(Redirector $redirector, AuthFactory $authFactory)
	{
		$this->redirector = $redirector;
		$this->authFactory = $authFactory;
	}
	
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		$user = $request->user();
		if (!$user->is_active) {
			$this->logout($request);

			return(
				$this->redirector->route('login')
				->with([
					'includes' => ['partials.error.inactive_account'],
					'email' => $user->email,
				])
			);
		}

		return($next($request));
	}

	/**
	 * Logout the current User.
	 * 
	 * @param \Illuminate\Http\Request $request
	 * @return void
	 */
	private function logout(Request $request)
	{
		$this->authFactory->guard()->logout();
		$request->session()->flush();
	}
}
