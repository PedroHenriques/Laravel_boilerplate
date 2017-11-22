<?php

namespace App\Http\Middleware;

use App\Services\RoleManager;
use Closure;
use Illuminate\Routing\Redirector;

class CheckRole
{
	private $redirector;
	private $roleMng;

	public function __construct(Redirector $redirector, RoleManager $roleMng)
	{
		$this->redirector = $redirector;
		$this->roleMng = $roleMng;
	}

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @param  string  $desiredRoleId
	 * @return mixed
	 */
	public function handle($request, Closure $next, string $desiredRoleId)
	{
		if (!$this->roleMng->has($request->user(), intval($desiredRoleId, 10))) {
			return($this->redirector->back());
		}

		return($next($request));
	}
}
