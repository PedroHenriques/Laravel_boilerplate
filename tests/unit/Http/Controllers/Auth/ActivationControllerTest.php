<?php

namespace Tests\unit\Http\Controllers\Auth;

use App\Exceptions\{AccountActivationException, ExpiredTokenException, InvalidTokenException, TokenDeleteException, ValidationFailedException};
use App\Http\Controllers\Auth\ActivationController;
use App\Services\ActivationService;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Routing\Redirector;
use PHPUnit\Framework\TestCase;

class ActivationControllerTest extends TestCase
{
	public function setUp()
	{
		$this->redirectorMock = $this->createMock(Redirector::class);
		$this->activationServiceMock = $this->createMock(ActivationService::class);
		$this->requestMock = $this->createMock(Request::class);

		$this->controller = new ActivationController($this->redirectorMock, $this->activationServiceMock);
	}

	public function testTheClassRegistersTheGuestMiddleware()
	{
		$this->assertEquals(
			[
				[
					'middleware' => 'guest',
					'options' => [],
				],
			],
			$this->controller->getMiddleware()
		);
	}

	public function testActivateItShouldCallTheActivateMethodOfTheActivationServiceAndRedirectToTheLoginRouteWithASuccessPartial()
	{
		$redirectResponseMock = $this->createMock(RedirectResponse::class);

		$this->activationServiceMock->expects($this->once())
		->method('activate')
		->with($this->requestMock)
		->willReturn(null);

		$this->redirectorMock->expects($this->once())
		->method('route')
		->with('login')
		->willReturn($redirectResponseMock);

		$redirectResponseMock->expects($this->once())
		->method('with')
		->with('includes', ['partials.success.account_activated'])
		->will($this->returnSelf());

		$this->assertEquals($redirectResponseMock, $this->controller->activate($this->requestMock));
	}

	public function testActivateIfTheActivationServiceThrowsAValidationfailedexceptionItShouldRedirectToTheLandingRouteWithAnErrorPartial()
	{
		$redirectResponseMock = $this->createMock(RedirectResponse::class);

		$this->activationServiceMock->expects($this->once())
		->method('activate')
		->with($this->requestMock)
		->will($this->throwException(new ValidationFailedException(['e' => ['test exception msg.']])));

		$this->redirectorMock->expects($this->once())
		->method('route')
		->with('landing')
		->willReturn($redirectResponseMock);

		$redirectResponseMock->expects($this->once())
		->method('with')
		->with('includes', ['partials.error.activation_generic'])
		->will($this->returnSelf());

		$this->assertEquals($redirectResponseMock, $this->controller->activate($this->requestMock));
	}

	public function testActivateIfTheActivationServiceThrowsAnInvalidtokenexceptionItShouldRedirectToTheLandingRouteWithAnErrorPartial()
	{
		$redirectResponseMock = $this->createMock(RedirectResponse::class);

		$this->activationServiceMock->expects($this->once())
		->method('activate')
		->with($this->requestMock)
		->will($this->throwException(new InvalidTokenException('activation')));

		$this->redirectorMock->expects($this->once())
		->method('route')
		->with('landing')
		->willReturn($redirectResponseMock);

		$redirectResponseMock->expects($this->once())
		->method('with')
		->with('includes', ['partials.error.activation_generic'])
		->will($this->returnSelf());

		$this->assertEquals($redirectResponseMock, $this->controller->activate($this->requestMock));
	}
	
	public function testActivateIfTheActivationServiceThrowsAnExpiredtokenexceptionItShouldRedirectToTheLandingRouteWithAnErrorPartial()
	{
		$redirectResponseMock = $this->createMock(RedirectResponse::class);

		$this->activationServiceMock->expects($this->once())
		->method('activate')
		->with($this->requestMock)
		->will($this->throwException(new ExpiredTokenException('activation')));

		$this->redirectorMock->expects($this->once())
		->method('route')
		->with('landing')
		->willReturn($redirectResponseMock);

		$redirectResponseMock->expects($this->once())
		->method('with')
		->with('includes', ['partials.error.activation_resent'])
		->will($this->returnSelf());

		$this->assertEquals($redirectResponseMock, $this->controller->activate($this->requestMock));
	}

	public function testActivateIfTheActivationServiceThrowsAnAccountactivationexceptionItShouldRedirectToTheLandingRouteWithAnErrorPartial()
	{
		$redirectResponseMock = $this->createMock(RedirectResponse::class);

		$this->activationServiceMock->expects($this->once())
		->method('activate')
		->with($this->requestMock)
		->will($this->throwException(new AccountActivationException('test@test.com')));

		$this->redirectorMock->expects($this->once())
		->method('route')
		->with('landing')
		->willReturn($redirectResponseMock);

		$redirectResponseMock->expects($this->once())
		->method('with')
		->with('includes', ['partials.error.activation_generic'])
		->will($this->returnSelf());

		$this->assertEquals($redirectResponseMock, $this->controller->activate($this->requestMock));
	}

	public function testActivateIfTheActivationServiceThrowsATokendeleteexceptionItShouldRedirectToTheLoginRouteWithASuccessPartia()
	{
		$redirectResponseMock = $this->createMock(RedirectResponse::class);

		$this->activationServiceMock->expects($this->once())
		->method('activate')
		->with($this->requestMock)
		->will($this->throwException(new TokenDeleteException('test@test.com', 'activation')));

		$this->redirectorMock->expects($this->once())
		->method('route')
		->with('login')
		->willReturn($redirectResponseMock);

		$redirectResponseMock->expects($this->once())
		->method('with')
		->with('includes', ['partials.success.account_activated'])
		->will($this->returnSelf());

		$this->assertEquals($redirectResponseMock, $this->controller->activate($this->requestMock));
	}

	public function testActivateIfTheActivationServiceThrowsAnExceptionItShouldRedirectToTheLandingRouteWithAnErrorPartial()
	{
		$redirectResponseMock = $this->createMock(RedirectResponse::class);

		$this->activationServiceMock->expects($this->once())
		->method('activate')
		->with($this->requestMock)
		->will($this->throwException(new \Exception('ActivationService->activate() exception msg.')));

		$this->redirectorMock->expects($this->once())
		->method('route')
		->with('landing')
		->willReturn($redirectResponseMock);

		$redirectResponseMock->expects($this->once())
		->method('with')
		->with('includes', ['partials.error.activation_generic'])
		->will($this->returnSelf());

		$this->assertEquals($redirectResponseMock, $this->controller->activate($this->requestMock));
	}

	public function testResendItShouldCallTheResendMethodOfTheActivationServiceAndRedirectToTheLandingRouteWithASuccessPartial()
	{
		$redirectResponseMock = $this->createMock(RedirectResponse::class);

		$this->activationServiceMock->expects($this->once())
		->method('resend')
		->with($this->requestMock)
		->willReturn(null);

		$this->redirectorMock->expects($this->once())
		->method('route')
		->with('landing')
		->willReturn($redirectResponseMock);

		$redirectResponseMock->expects($this->once())
		->method('with')
		->with('includes', ['partials.success.resend_activation'])
		->will($this->returnSelf());

		$this->assertEquals($redirectResponseMock, $this->controller->resend($this->requestMock));
	}

	public function testResendIfTheActivationServiceThrowsAnExceptionItShouldRedirectToTheLandingRouteWithAnErrorPartial()
	{
		$redirectResponseMock = $this->createMock(RedirectResponse::class);

		$this->activationServiceMock->expects($this->once())
		->method('resend')
		->with($this->requestMock)
		->will($this->throwException(new \Exception('ActivationService->resend() exception msg.')));

		$userEmail = 'test@email.com';
		$this->requestMock->expects($this->once())
		->method('query')
		->with('e')
		->willReturn($userEmail);

		$this->redirectorMock->expects($this->once())
		->method('route')
		->with('landing')
		->willReturn($redirectResponseMock);

		$redirectResponseMock->expects($this->once())
		->method('with')
		->with([
			'includes' => ['partials.error.resend_activation'],
			'email' => $userEmail,
		])
		->will($this->returnSelf());

		$this->assertEquals($redirectResponseMock, $this->controller->resend($this->requestMock));
	}

	public function testResendIfTheActivationServiceThrowsAValidationfailedexceptionItShouldRedirectToTheLandingRouteWithAnErrorPartial()
	{
		$redirectResponseMock = $this->createMock(RedirectResponse::class);

		$this->activationServiceMock->expects($this->once())
		->method('resend')
		->with($this->requestMock)
		->will($this->throwException(new ValidationFailedException(['test error msg.'])));

		$userEmail = 'test@email.com';
		$this->requestMock->expects($this->once())
		->method('query')
		->with('e')
		->willReturn($userEmail);

		$this->redirectorMock->expects($this->once())
		->method('route')
		->with('landing')
		->willReturn($redirectResponseMock);

		$redirectResponseMock->expects($this->once())
		->method('with')
		->with([
			'includes' => ['partials.error.resend_activation'],
			'email' => $userEmail,
		])
		->will($this->returnSelf());

		$this->assertEquals($redirectResponseMock, $this->controller->resend($this->requestMock));
	}
}