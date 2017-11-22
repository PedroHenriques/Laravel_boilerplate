<?php

namespace Tests\e2e\Http\Controllers\Auth;

use Tests\e2e\MinkTestCase;

class ResetPasswordControllerTest extends MinkTestCase
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
          'id' => 2, 'username' => 'another user', 'email' => 'another@test.com',
          'password' => '$2y$10$JjraPoa3ae2MZNBnkAdOpehzNsI.YLvYsbf12VB/dROHX6.f/fRQS',
          'remember_token' => null, 'is_active' => 1, 'role_id' => 1,
          'created_at' => date('Y-m-d H:i:s', time() - 100),
          'updated_at' => date('Y-m-d H:i:s', time() - 100),
        ],
      ],
      'password_resets' => [
        [
          'email' => 'active@test.com', 'created_at' => date('Y-m-d H:i:s', time()),
          // c3ba9c60fceca94a4c5a1ed1eec9ba1ac240696dcccfa5566b9ec6d6176bec59
          'token' => '$2y$10$6DyQzJR3UeOBpx13rd2y6uopR3j0NwudeIMYHCNvjeuqHO8PKnLlC',
        ],
        [
          'email' => 'another@test.com', 'created_at' => date('Y-m-d H:i:s', time() - 100000),
          // 5235d1634b2fb99da9954cc1d559a56aae8ae21d029d81d61cca7042f837e210
          'token' => '$2y$10$V6Dy9iubXjT0vnsJFO4/j.Vi3uEUPhSS4g50D0P68MyC2/Rbu5/Ia',
        ],
      ],
    ];
  }

  public function testAUserCanResetItsPasswordByVisitingTheCorrectLink()
  {
    $session = parent::getSession();
    parent::visitUri($session, '/password/reset/c3ba9c60fceca94a4c5a1ed1eec9ba1ac240696dcccfa5566b9ec6d6176bec59');
    $page = $session->getPage();
    $form = $page->find('named', ['id_or_name', 'reset-form']);
    $form->fillField('email', $this->fixtures['users'][0]['email']);
    $form->fillField('password', 'new password');
    $form->fillField('password-confirm', 'new password');
    $form->submit();

    $this->assertEquals(parent::getUrlFromUri('/home'), $session->getCurrentUrl());

    $query = "SELECT password FROM users WHERE id = {$this->fixtures['users'][0]['id']}";
    $newPwHash = parent::getPdo()->query($query)->fetchAll()[0]['password'];
    $this->assertEquals(
      true,
      $this->fixtures['users'][0]['password'] !== $newPwHash
    );
    $this->assertEquals(true, password_verify('new password', $newPwHash));
  }

  public function testTheRequestIsIgnoredIfTheTokenIsNotValid()
  {
    $session = parent::getSession();
    parent::visitUri($session, '/password/reset/invalidtoken');
    $page = $session->getPage();
    $form = $page->find('named', ['id_or_name', 'reset-form']);
    $form->fillField('email', $this->fixtures['users'][0]['email']);
    $form->fillField('password', 'new password');
    $form->fillField('password-confirm', 'new password');
    $form->submit();

    $this->assertEquals(parent::getUrlFromUri('/password/reset/invalidtoken'), $session->getCurrentUrl());

    $query = "SELECT count(id) as count FROM users WHERE id = {$this->fixtures['users'][0]['id']} AND password = '{$this->fixtures['users'][0]['password']}'";
    $this->assertEquals(1, parent::getPdo()->query($query)->fetchAll()[0]['count']);
  }

  public function testTheRequestIsIgnoredIfTheTokenIsExpired()
  {
    $session = parent::getSession();
    parent::visitUri($session, '/password/reset/5235d1634b2fb99da9954cc1d559a56aae8ae21d029d81d61cca7042f837e210');
    $page = $session->getPage();
    $form = $page->find('named', ['id_or_name', 'reset-form']);
    $form->fillField('email', $this->fixtures['users'][1]['email']);
    $form->fillField('password', 'new password');
    $form->fillField('password-confirm', 'new password');
    $form->submit();

    $this->assertEquals(parent::getUrlFromUri('/password/reset/5235d1634b2fb99da9954cc1d559a56aae8ae21d029d81d61cca7042f837e210'), $session->getCurrentUrl());

    $query = "SELECT count(id) as count FROM users WHERE id = {$this->fixtures['users'][1]['id']} AND password = '{$this->fixtures['users'][1]['password']}'";
    $this->assertEquals(1, parent::getPdo()->query($query)->fetchAll()[0]['count']);
  }
}