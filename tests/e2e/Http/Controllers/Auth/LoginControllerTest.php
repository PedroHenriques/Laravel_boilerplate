<?php

namespace Tests\e2e\Http\Controllers\Auth;

use Tests\e2e\MinkTestCase;

class LoginControllerTest extends MinkTestCase
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
          'password' => '$2y$10$B8lF5o194wez1tuhrEKbb.WIp0YxHSBfv2ZC7ebBhXHCiqn5.vD4e',
          'remember_token' => null, 'is_active' => 0, 'role_id' => 1,
          'created_at' => date('Y-m-d H:i:s', time() - 10000),
          'updated_at' => date('Y-m-d H:i:s', time() - 10000),
        ],
      ],
    ];
  }

  public function testAUserCanLoginUsingTheEmailAsTheUniqueIdentifier()
  {
    $session = parent::getSession();
    parent::authenticateUser(
      $session, $this->fixtures['users'][0]['email'], 'password'
    );
    $this->assertEquals(parent::getUrlFromUri('/home'), $session->getCurrentUrl());
  }

  public function testAUserCanLoginUsingTheUsernameAsTheUniqueIdentifier()
  {
    $session = parent::getSession();
    parent::authenticateUser(
      $session, $this->fixtures['users'][0]['username'], 'password'
    );
    $this->assertEquals(parent::getUrlFromUri('/home'), $session->getCurrentUrl());
  }

  public function testAUserCanNotLoginIfTheAccountIsInactive()
  {
    $session = parent::getSession();
    parent::authenticateUser(
      $session, $this->fixtures['users'][1]['username'], 'password'
    );
    $this->assertEquals(parent::getUrlFromUri('/login'), $session->getCurrentUrl());
  }
}