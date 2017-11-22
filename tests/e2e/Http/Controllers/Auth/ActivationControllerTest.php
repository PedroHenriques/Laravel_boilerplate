<?php

namespace Tests\e2e\Http\Controllers\Auth;

use Tests\e2e\MinkTestCase;

class ActivationControllerTest extends MinkTestCase
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
          'id' => 2, 'username' => 'inactive user', 'email' => 'inactive@test.com',
          'password' => '$2y$10$JjraPoa3ae2MZNBnkAdOpehzNsI.YLvYsbf12VB/dROHX6.f/fRQS',
          'remember_token' => null, 'is_active' => 0, 'role_id' => 1,
          'created_at' => date('Y-m-d H:i:s', time() - 100),
          'updated_at' => date('Y-m-d H:i:s', time() - 100),
        ],
      ],
      'account_activations' => [
        // 78182a06c8fe8f23c88cae311f2b46e2d9220b2f98a6a790367d3f935346e39e
        ['user_email' => 'inactive@test.com', 'token' => '$2y$10$Wpxk9eOg7.DrSyzEUL3gIuVgNGcFWwPcxpHbyRYEh3vT0iqojXHx2', 'created_at' => date('Y-m-d H:i:s')],
      ],
    ];
  }

  protected function setUp()
  {
    parent::setUp();
    parent::clearEmails();
  }

  public function testAUserCanActivateItsAccount()
  {
    $session = parent::getSession();
    parent::visitUri(
      $session,
      '/activate?e='.urlencode($this->fixtures['account_activations'][0]['user_email']).'&t='.urlencode('78182a06c8fe8f23c88cae311f2b46e2d9220b2f98a6a790367d3f935346e39e')
    );
    $this->assertEquals(parent::getUrlFromUri('/login'), $session->getCurrentUrl());
    parent::authenticateUser(
      $session, $this->fixtures['users'][0]['email'], 'password'
    );
    $this->assertEquals(parent::getUrlFromUri('/home'), $session->getCurrentUrl());
  }

  public function testAUserCanRequestANewActivationToken()
  {
    $session = parent::getSession();
    parent::visitUri(
      $session,
      '/resend-activation?e='.urlencode($this->fixtures['account_activations'][0]['user_email'])
    );
    $this->assertEquals(parent::getUrlFromUri(''), $session->getCurrentUrl());

    $query = "SELECT token FROM account_activations WHERE user_email='{$this->fixtures['account_activations'][0]['user_email']}'";
    $this->assertEquals(
      false,
      '78182a06c8fe8f23c88cae311f2b46e2d9220b2f98a6a790367d3f935346e39e' === parent::getPdo()->query($query)->fetchAll(\PDO::FETCH_ASSOC)[0]['token']
    );

    $this->assertEquals(1, count(parent::getEmails()));
  }
}