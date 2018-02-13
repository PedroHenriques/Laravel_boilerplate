<?php

namespace Tests\integration\Services;

use Illuminate\Http\Request;
use Tests\integration\BaseIntegrationCase;

class ActivationServiceTest extends BaseIntegrationCase
{
  protected $fixtures = [];

  public function __construct()
  {
    parent::__construct();
    $this->fixtures = [
      'roles' => [
        ['id' => 1, 'name' => 'ROLE_USER'],
      ],
      'users' => [
        [
          'id' => 1, 'username' => 'active user', 'email' => 'active@test.com',
          'password' => '$2y$10$B8lF5o194wez1tuhrEKbb.WIp0YxHSBfv2ZC7ebBhXHCiqn5.vD4e',
          'remember_token' => null, 'is_active' => 1, 'role_id' => 1,
          'created_at' => date('Y-m-d H:i:s', time() - 10000),
          'updated_at' => date('Y-m-d H:i:s', time() - 10000),
        ],
        [
          'id' => 2, 'username' => 'inactive user', 'email' => 'inactive@test.com',
          'password' => '$2y$10$JjraPoa3ae2MZNBnkAdOpehzNsI.YLvYsbf12VB/dROHX6.f/fRQS',
          'remember_token' => null, 'is_active' => 0, 'role_id' => 1,
          'created_at' => date('Y-m-d H:i:s', time() - 100),
          'updated_at' => date('Y-m-d H:i:s', time() - 100),
        ],
        [
          'id' => 3, 'username' => 'inactve user with expired token', 'email' => 'expired@test.com',
          'password' => '$2y$10$JjraPoa3ae2MZNBnkAdOpehzNsI.YLvYsbf12VB/dROHX6.f/fRQS',
          'remember_token' => null, 'is_active' => 0, 'role_id' => 1,
          'created_at' => date('Y-m-d H:i:s', time() - 1234),
          'updated_at' => date('Y-m-d H:i:s', time() - 1234),
        ],
      ],
      'account_activations' => [
        // 78182a06c8fe8f23c88cae311f2b46e2d9220b2f98a6a790367d3f935346e39e
        ['user_email' => 'inactive@test.com', 'token' => '$2y$10$O7BcisgK3VZxJ5fmLjyAnOxFCBkHQezzkv46Bf8jRWcfokqhkqCCi', 'created_at' => date('Y-m-d H:i:s')],
        // e0ef3275cf43f102dc28b15d2217847be12beda51e81025c95c8a8001378b830
        ['user_email' => 'expired@test.com', 'token' => '$2y$10$Zguss3tdSMtURRdj3JfcYemY7QAg8JiCrq2/f/uUzc/sftcZf1cU2', 'created_at' => date('Y-m-d H:i:s', time() - 172800)],
      ],
    ];
  }

  public function setUp(): void
  {
    parent::setUp();
    parent::clearEmails();
    $this->pdo = parent::getPdo();
    $this->app = $this->getApp();
    $this->service = $this->app->make('App\Services\ActivationService');
  }

  public function testActivateIsActivatingTheUserAccountAndDeletingTheTokenWhenTheProvidedDataIsValid()
  {
    $getParams = [
      'e' => $this->fixtures['users'][1]['email'],
      't' => '78182a06c8fe8f23c88cae311f2b46e2d9220b2f98a6a790367d3f935346e39e'
    ];
    $request = new Request($getParams, [], [], [], [], [], '');

    $this->assertEquals(null, $this->service->activate($request));

    $query = "SELECT count(user_email) as count FROM account_activations WHERE user_email = '{$this->fixtures['users'][1]['email']}'";
    $this->assertEquals(0, $this->pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC)[0]['count']);
    
    $query = "SELECT count(id) as count FROM users WHERE email = '{$this->fixtures['users'][1]['email']}' AND is_active = 1";
    $this->assertEquals(1, $this->pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC)[0]['count']);
  }

  public function testActivateWillThrowAValidationfailedexceptionWhenTheProvidedDataFailsTheValidation()
  {
    $this->expectException(\App\Exceptions\ValidationFailedException::class);

    $getParams = [
      'e' => 'not an email',
      't' => '78182a06c8fe8f23c88cae311f2b46e2d9220b2f98a6a790367d3f935346e39e'
    ];
    $request = new Request($getParams, [], [], [], [], [], '');

    $this->service->activate($request);
  }

  public function testActivateWillThrowAnInvalidtokenexceptionWhenTheProvidedEmailIsNotValid()
  {
    $this->expectException(\App\Exceptions\InvalidTokenException::class);

    $getParams = [
      'e' => $this->fixtures['users'][0]['email'],
      't' => '78182a06c8fe8f23c88cae311f2b46e2d9220b2f98a6a790367d3f935346e39e'
    ];
    $request = new Request($getParams, [], [], [], [], [], '');

    $this->service->activate($request);
  }

  public function testActivateWillThrowAnInvalidtokenexceptionWhenTheProvidedTokenIsNotValid()
  {
    $this->expectException(\App\Exceptions\InvalidTokenException::class);

    $getParams = [
      'e' => $this->fixtures['users'][1]['email'],
      't' => 'invalid6c8fe8f23c88cae311f2b46e2d9220b2f98a6a790367d3f935346e39e'
    ];
    $request = new Request($getParams, [], [], [], [], [], '');

    $this->service->activate($request);
  }

  public function testActivateIsGeneratingANewTokenAndPersistingItToTheDbAndSendingItOnAnActivationEmailWhenTheProvidedTokenIsValidButExpired()
  {
    $getParams = [
      'e' => $this->fixtures['users'][2]['email'],
      't' => 'e0ef3275cf43f102dc28b15d2217847be12beda51e81025c95c8a8001378b830'
    ];
    $request = new Request($getParams, [], [], [], [], [], '');

    try {
      $exceptionThrown = false;
      $this->service->activate($request);
    } catch (\App\Exceptions\ExpiredTokenException $e) {
      $exceptionThrown = true;
    }
    $this->assertEquals(true, $exceptionThrown);

    $sentEmails = parent::getEmails();
    $this->assertEquals(1, count($sentEmails));

    $query = "SELECT token FROM account_activations WHERE user_email = '{$this->fixtures['users'][2]['email']}'";
    $this->assertEquals(false, $getParams['t'] === $this->pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC)[0]['token']);
    
    $query = "SELECT count(id) as count FROM users WHERE email = '{$this->fixtures['users'][2]['email']}' AND is_active = 0";
    $this->assertEquals(1, $this->pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC)[0]['count']);
  }

  public function testResendIsGeneratingANewTokenAndPersistingItToTheDbAndSendingItOnAnActivationEmail()
  {
    $getParams = [
      'e' => $this->fixtures['users'][2]['email']
    ];
    $request = new Request($getParams, [], [], [], [], [], '');

    $this->assertEquals(null, $this->service->resend($request));

    $configRepo = $this->app->make('Illuminate\Contracts\Config\Repository');

    $sentEmails = parent::getEmails();
    $this->assertEquals(1, count($sentEmails));

    $query = "SELECT token FROM account_activations WHERE user_email = '{$this->fixtures['users'][2]['email']}'";
    $this->assertEquals(false, 'e0ef3275cf43f102dc28b15d2217847be12beda51e81025c95c8a8001378b830' === $this->pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC)[0]['token']);
  }

  public function testResendWillThrowAValidationfailedexceptionWhenTheProvidedDataFailsTheValidation()
  {
    $this->expectException(\App\Exceptions\ValidationFailedException::class);

    $getParams = [
      'e' => 'not an email',
      't' => '78182a06c8fe8f23c88cae311f2b46e2d9220b2f98a6a790367d3f935346e39e'
    ];
    $request = new Request($getParams, [], [], [], [], [], '');

    $this->service->resend($request);
  }

  public function testResendWillIgnoreARequestForAnEmailAddressThatDoesNotHaveATokenAssignedToIt()
  {
    $getParams = [
      'e' => $this->fixtures['users'][0]['email']
    ];
    $request = new Request($getParams, [], [], [], [], [], '');

    try {
      $exceptionThrown = false;
      $this->service->resend($request);
    } catch (\App\Exceptions\ValidationFailedException $e) {
      $exceptionThrown = true;
    }
    $this->assertEquals(true, $exceptionThrown); 

    $query = "SELECT count(user_email) as count FROM account_activations WHERE user_email = '{$this->fixtures['users'][0]['email']}'";
    $this->assertEquals(0, $this->pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC)[0]['count']);
  }
}