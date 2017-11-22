<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
	/*
	|--------------------------------------------------------------------------
	| Login Controller
	|--------------------------------------------------------------------------
	|
	| This controller handles authenticating users for the application and
	| redirecting them to your home screen. The controller uses a trait
	| to conveniently provide its functionality to your applications.
	|
	*/

	use AuthenticatesUsers;

	/**
	 * Where to redirect users after login.
	 *
	 * @var string
	 */
	protected $redirectTo = '/home';

	private $validatorFactory;

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct(ValidatorFactory $validatorFactory)
	{
		$this->validatorFactory = $validatorFactory;

		$this->middleware('guest')->except('logout');
	}

	/**
	 * Get the needed authorization credentials from the request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return array
	 */
	protected function credentials(Request $request)
	{
		$credentials = $request->only('identifier', 'password');
		if (
			preg_match('/^[^@]+@[^@]+\.[^@\.]+$/i', $credentials['identifier']) === 1
		) {
			$identifierCol = 'email';
		} else {
			$identifierCol = 'username';
		}

		return([
			$identifierCol => $credentials['identifier'],
			'password' => $credentials['password'],
		]);
	}

	/**
	 * Validate the user login request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return void
	 * @throws \Illuminate\Validation\ValidationException
	 */
	protected function validateLogin(Request $request)
	{
		$this->validatorFactory->validate($request->all(), [
			'identifier' => 'required|string',
			'password' => 'required|string',
		]);
	}
}
