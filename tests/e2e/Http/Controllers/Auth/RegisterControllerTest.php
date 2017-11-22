<?php

namespace Tests\e2e\Http\Controllers\Auth;

use Tests\e2e\MinkTestCase;

class RegisterControllerTest extends MinkTestCase
{
  protected $fixtures = [];
  
  public function __construct()
  {
    parent::__construct();
    $this->fixtures = [
      'roles' => [
        ['id' => 1, 'name' => 'ROLE_USER'],
      ],
      'users' => [],
      'account_activations' => [],
    ];
  }

  protected function setUp()
  {
    parent::setUp();
    parent::clearEmails();
  }

  public function testAUserCanRegisterAnAccount()
  {
    $session = parent::getSession();
    parent::visitUri($session, '/register');
    $page = $session->getPage();
    $form = $page->find('named', ['id_or_name', 'register-form']);
    $form->fillField('username', 'new username');
    $form->fillField('email', 'new@account.com');
    $form->fillField('password', 'password');
    $form->fillField('password-confirm', 'password');
    $form->submit();

    $this->assertEquals(parent::getUrlFromUri('/login'), $session->getCurrentUrl());

    $query = 'SELECT count(id) as count FROM users';
    $this->assertEquals(
      count($this->fixtures['users']) + 1,
      parent::getPdo()->query($query)->fetchAll(\PDO::FETCH_ASSOC)[0]['count']
    );
    $query = 'SELECT count(user_email) as count FROM account_activations';
    $this->assertEquals(
      count($this->fixtures['account_activations']) + 1,
      parent::getPdo()->query($query)->fetchAll(\PDO::FETCH_ASSOC)[0]['count']
    );

    $this->assertEquals(1, count(parent::getEmails()));
  }
}