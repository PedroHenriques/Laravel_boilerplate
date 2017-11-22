<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Utils\ClassUtils;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

class RegisterController extends Controller
{
	/*
	|--------------------------------------------------------------------------
	| Register Controller
	|--------------------------------------------------------------------------
	|
	| This controller handles the registration of new users as well as their
	| validation and creation. By default this controller uses a trait to
	| provide this functionality without requiring any additional code.
	|
	*/

	use RegistersUsers;

	/**
	 * Where to redirect users after registration.
	 *
	 * @var string
	 */
	protected $redirectTo = '/login';

	private $validatorFactory;
	private $userModel;
	private $redirector;
	private $hasher;
	private $dispatcher;
	private $classUtils;
	private $config;

	/**
	 * Create a new controller instance.
	 *
	 * @param \Illuminate\Contracts\Validation\Factory $validatorFactory
	 * @param \App\Models\User $userModel
	 * @param \Illuminate\Routing\Redirector $redirector
	 * @param \Illuminate\Contracts\Hashing\Hasher $hasher
	 * @param \Illuminate\Contracts\Events\Dispatcher $dispatcher
	 * @param \App\Utils\ClassUtils $classUtils
	 * @param \Illuminate\Contracts\Config\Repository $config
	 * @return void
	 */
	public function __construct(
		ValidatorFactory $validatorFactory,
		User $userModel,
		Redirector $redirector,
		Hasher $hasher,
		Dispatcher $dispatcher,
		ClassUtils $classUtils,
		Config $config
	) {
		$this->validatorFactory = $validatorFactory;
		$this->userModel = $userModel;
		$this->redirector = $redirector;
		$this->hasher = $hasher;
		$this->dispatcher = $dispatcher;
		$this->classUtils = $classUtils;
		$this->config = $config;

		$this->middleware('guest');
	}

	/**
	 * Handle a registration request for the application.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 * @throws \Illuminate\Validation\ValidationException
	 */
	public function register(Request $request)
	{
		$inputData = $request->all();
		$this->validator($inputData)->validate();

		$user = $this->create($inputData);
		$this->dispatcher->dispatch(
			$this->classUtils->instantiate(
				'\Illuminate\Auth\Events\Registered', [$user]
			)
		);

		return(
			$this->registered($request, $user) ?: $this->redirector
				->to($this->redirectPath())
				->with([
					'includes' => ['partials.success.registered'],
					'email' => $request->input('email'),
				])
		);
	}

	/**
	 * Get a validator for an incoming registration request.
	 *
	 * @param  array  $data
	 * @return \Illuminate\Contracts\Validation\Validator
	 */
	protected function validator(array $data)
	{
		return($this->validatorFactory->make($data, [
			'username' => 'required|string|max:255|unique:users,username',
			'email' => 'required|string|email|max:255|unique:users,email',
			'password' => 'required|string|min:6|confirmed',
		]));
	}

	/**
	 * Create a new user instance after a valid registration.
	 *
	 * @param  array  $data
	 * @return \App\Models\User
	 */
	protected function create(array $data)
	{
		$user = $this->userModel->newInstance([
			'username' => $data['username'],
			'email' => $data['email'],
			'password' => $this->hasher->make($data['password']),
			'role_id' => $this->config->get('roles.newUserRoleId'),
		]);

		if (!$user->save()) {
			throw new \Exception(
				"The new User, with email '{$data['email']}', failed to be saved on ".
				"the DB."
			);
		}

		return($user);
	}
}
