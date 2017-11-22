<?php

namespace Tests\e2e\Http\Controllers\Auth;

use Tests\CreatesApplication;
use Tests\e2e\MinkTestCase;

class ForgotPasswordControllerTest extends MinkTestCase
{
  use CreatesApplication;

  protected $fixtures = [];
  private $app;
  private $emailRegExPattern = '/<a href="http:\/\/[^\/]+\/password\/reset\/([^"]+)"/i';
  
  public function __construct()
  {
    parent::__construct();
    $this->app = $this->createApplication();
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
      ],
      'password_resets' => [
        [
          'email' => 'active@test.com', 'created_at' => date('Y-m-d H:i:s', time() - 100),
          // c3ba9c60fceca94a4c5a1ed1eec9ba1ac240696dcccfa5566b9ec6d6176bec59
          'token' => '$2y$10$6DyQzJR3UeOBpx13rd2y6uopR3j0NwudeIMYHCNvjeuqHO8PKnLlC',
        ]
      ],
    ];
  }

  protected function setUp()
  {
    parent::setUp();
    parent::clearEmails();
  }

  public function testAUserCanRequestAPasswordResetLink()
  {
    $session = parent::getSession();
    parent::visitUri($session, '/password/reset');
    $page = $session->getPage();
    $form = $page->find('named', ['id_or_name', 'pw-email-form']);
    $form->fillField('email', $this->fixtures['users'][0]['email']);
    $form->submit();

    $this->assertEquals(parent::getUrlFromUri('/password/reset'), $session->getCurrentUrl());

    $query = "SELECT token FROM password_resets WHERE email = '{$this->fixtures['users'][0]['email']}'";
    $resultSet = parent::getPdo()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
    $this->assertEquals(1, count($resultSet));
    $token = $resultSet[0]['token'];

    $configRepo = $this->app->make('Illuminate\Contracts\Config\Repository');

    $sentEmails = parent::getEmails();
    $this->assertEquals(1, count($sentEmails));
    $this->assertEquals('<'.$configRepo->get('mail.from.address').'>', $sentEmails[0]['sender']);
    $this->assertEquals('<'.$this->fixtures['users'][0]['email'].'>', $sentEmails[0]['recipients'][0]);
    $this->assertEquals('Reset Password', $sentEmails[0]['subject']);

    $emailContents = html_entity_decode(urldecode($this->getEmailBody($sentEmails[0]['id'])));
    $reMatches = null;
    $this->assertEquals(
      true,
      preg_match($this->emailRegExPattern, $emailContents, $reMatches) === 1
    );
    $this->assertEquals(true, password_verify($reMatches[1], $token));
  }

  public function testTheRequestIsIgnoredIfTheEmailDoesNotExist()
  {
    $session = parent::getSession();
    parent::visitUri($session, '/password/reset');
    $page = $session->getPage();
    $form = $page->find('named', ['id_or_name', 'pw-email-form']);
    $form->fillField('email', 'invalid email');
    $form->submit();

    $this->assertEquals(parent::getUrlFromUri('/password/reset'), $session->getCurrentUrl());

    $query = 'SELECT count(email) as count FROM password_resets';
    $this->assertEquals(
      count($this->fixtures['password_resets']),
      parent::getPdo()->query($query)->fetchAll(\PDO::FETCH_ASSOC)[0]['count']
    );

    $this->assertEquals(0, count(parent::getEmails()));
  }

  public function testANewPasswordResetLinkCanBeRequestedIfATokenAlreadyExists()
  {
    $session = parent::getSession();
    parent::visitUri($session, '/password/reset');
    $page = $session->getPage();
    $form = $page->find('named', ['id_or_name', 'pw-email-form']);
    $form->fillField('email', $this->fixtures['users'][0]['email']);
    $form->submit();

    $this->assertEquals(parent::getUrlFromUri('/password/reset'), $session->getCurrentUrl());

    $query = "SELECT token FROM password_resets WHERE email = '{$this->fixtures['users'][0]['email']}'";
    $resultSet = parent::getPdo()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
    $this->assertEquals(1, count($resultSet));
    $newToken = $resultSet[0]['token'];
    $this->assertEquals(true, $newToken !== $this->fixtures['password_resets'][0]['token']);

    $configRepo = $this->app->make('Illuminate\Contracts\Config\Repository');

    $sentEmails = parent::getEmails();
    $this->assertEquals(1, count($sentEmails));
    $this->assertEquals('<'.$configRepo->get('mail.from.address').'>', $sentEmails[0]['sender']);
    $this->assertEquals('<'.$this->fixtures['users'][0]['email'].'>', $sentEmails[0]['recipients'][0]);
    $this->assertEquals('Reset Password', $sentEmails[0]['subject']);

    $emailContents = html_entity_decode(urldecode($this->getEmailBody($sentEmails[0]['id'])));
    $reMatches = null;
    $this->assertEquals(
      true,
      preg_match($this->emailRegExPattern, $emailContents, $reMatches) === 1
    );
    $this->assertEquals(true, password_verify($reMatches[1], $newToken));
  }
}