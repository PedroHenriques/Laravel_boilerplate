<?php

namespace Tests\integration\Jobs;

use App\Jobs\ProcessActivationEmail;
use Tests\integration\BaseIntegrationCase;

class ProcessActivationEmailTest extends BaseIntegrationCase
{
  protected $fixtures = [];
  private $emailRegExPattern = '/<a href="http:\/\/[^\/]+\/activate\?e=([^&]+)&t=([^"]+)"/i';

  public function __construct()
  {
    parent::__construct();
    $this->fixtures = [
      'roles' => [
        ['id' => 1, 'name' => 'ROLE_USER'],
      ],
      'users' => [
        [
          'id' => 1, 'username' => 'inactive user without token', 'email' => 'inactive@test.com',
          'password' => '$2y$10$B8lF5o194wez1tuhrEKbb.WIp0YxHSBfv2ZC7ebBhXHCiqn5.vD4e',
          'remember_token' => null, 'is_active' => 0, 'role_id' => 1,
          'created_at' => date('Y-m-d H:i:s'),
          'updated_at' => date('Y-m-d H:i:s'),
        ],
        [
          'id' => 2, 'username' => 'inactive user with token', 'email' => 'withtoken@test.com',
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
        ['user_email' => 'withtoken@test.com', 'token' => '$2y$10$SjCg666KerfV..eAUkjtOOsMZK08Vj/VlhXbF4cKXp8Fijef9EiQS', 'created_at' => date('Y-m-d H:i:s')],
        // e0ef3275cf43f102dc28b15d2217847be12beda51e81025c95c8a8001378b830
        ['user_email' => 'expired@test.com', 'token' => '$2y$10$3a.DSxsLGVG0wFrMu7fsvuBDxOpFTS6ldAO6/R2QVEpbjAFbloMXS', 'created_at' => date('Y-m-d H:i:s', time() - 172800)],
      ],
    ];
  }

  public function setUp()
  {
    parent::setUp();
    parent::clearEmails();
    $this->pdo = parent::getPdo();
    $this->app = $this->getApp();
    $this->connection = $this->app->make('Illuminate\Database\ConnectionInterface');
    $this->mailer = $this->app->make('Illuminate\Contracts\Mail\Mailer');
    $this->configRepo = $this->app->make('Illuminate\Contracts\Config\Repository');
    $this->hasher = $this->app->make('Illuminate\Contracts\Hashing\Hasher');
    $this->securityUtils = $this->app->make('App\Utils\SecurityUtils');
    $this->classUtils = $this->app->make('App\Utils\ClassUtils');
  }

  public function testHandleIsGeneratingATokenAndPersistingItToTheDbAndSendItOnAnActivationEmailForAnEmailAddressThatDoesNotHaveATokenAssignedToIt()
  {
    $job = new ProcessActivationEmail($this->fixtures['users'][0]['email']);
    $this->assertNull($job->handle(
      $this->connection, $this->mailer, $this->configRepo, $this->hasher, $this->securityUtils, $this->classUtils
    ));

    $sentEmails = parent::getEmails();
    $this->assertEquals(1, count($sentEmails));
    $this->assertEquals('<'.$this->configRepo->get('mail.from.address').'>', $sentEmails[0]['sender']);
    $this->assertEquals('<'.$this->fixtures['users'][0]['email'].'>', $sentEmails[0]['recipients'][0]);
    $this->assertEquals($this->configRepo->get('app.name').' - Account Activation', $sentEmails[0]['subject']);

    $emailContents = html_entity_decode(urldecode($this->getEmailBody($sentEmails[0]['id'])));
    $reMatches = null;
    $this->assertEquals(
      true,
      preg_match($this->emailRegExPattern, $emailContents, $reMatches) === 1
    );
    $this->assertEquals($this->fixtures['users'][0]['email'], $reMatches[1]);

    $query = "SELECT token FROM account_activations WHERE user_email = '{$this->fixtures['users'][0]['email']}'";
    $resultSet = $this->pdo->query($query)->fetchALL(\PDO::FETCH_ASSOC);
    $this->assertEquals(1, count($resultSet));
    $this->assertEquals(true, $this->hasher->check($reMatches[2], $resultSet[0]['token']));
  }

  public function testHandleIsGeneratingANewTokenAndPersistingItToTheDbAndSendItOnAnActivationEmailForAnEmailAddressThatAlreadyHasAValidTokenAssignedToIt()
  {
    $job = new ProcessActivationEmail($this->fixtures['users'][1]['email']);
    $this->assertNull($job->handle(
      $this->connection, $this->mailer, $this->configRepo, $this->hasher, $this->securityUtils, $this->classUtils
    ));

    $sentEmails = parent::getEmails();
    $this->assertEquals(1, count($sentEmails));
    $this->assertEquals('<'.$this->configRepo->get('mail.from.address').'>', $sentEmails[0]['sender']);
    $this->assertEquals('<'.$this->fixtures['users'][1]['email'].'>', $sentEmails[0]['recipients'][0]);
    $this->assertEquals($this->configRepo->get('app.name').' - Account Activation', $sentEmails[0]['subject']);

    $emailContents = html_entity_decode(urldecode($this->getEmailBody($sentEmails[0]['id'])));
    $reMatches = null;
    $this->assertEquals(
      true,
      preg_match($this->emailRegExPattern, $emailContents, $reMatches) === 1
    );
    $this->assertEquals($this->fixtures['users'][1]['email'], $reMatches[1]);

    $query = "SELECT token FROM account_activations WHERE user_email = '{$this->fixtures['users'][1]['email']}'";
    $resultSet = $this->pdo->query($query)->fetchALL(\PDO::FETCH_ASSOC);
    $this->assertEquals(1, count($resultSet));
    $this->assertEquals(true, $this->hasher->check($reMatches[2], $resultSet[0]['token']));
  }

  public function testHandleIsGeneratingANewTokenAndPersistingItToTheDbAndSendItOnAnActivationEmailForAnEmailAddressThatHasAnExpiredTokenAssignedToIt()
  {
    $job = new ProcessActivationEmail($this->fixtures['users'][2]['email']);
    $this->assertNull($job->handle(
      $this->connection, $this->mailer, $this->configRepo, $this->hasher, $this->securityUtils, $this->classUtils
    ));

    $sentEmails = parent::getEmails();
    $this->assertEquals(1, count($sentEmails));
    $this->assertEquals('<'.$this->configRepo->get('mail.from.address').'>', $sentEmails[0]['sender']);
    $this->assertEquals('<'.$this->fixtures['users'][2]['email'].'>', $sentEmails[0]['recipients'][0]);
    $this->assertEquals($this->configRepo->get('app.name').' - Account Activation', $sentEmails[0]['subject']);

    $emailContents = html_entity_decode(urldecode($this->getEmailBody($sentEmails[0]['id'])));
    $reMatches = null;
    $this->assertEquals(
      true,
      preg_match($this->emailRegExPattern, $emailContents, $reMatches) === 1
    );
    $this->assertEquals($this->fixtures['users'][2]['email'], $reMatches[1]);

    $query = "SELECT token FROM account_activations WHERE user_email = '{$this->fixtures['users'][2]['email']}'";
    $resultSet = $this->pdo->query($query)->fetchALL(\PDO::FETCH_ASSOC);
    $this->assertEquals(1, count($resultSet));
    $this->assertEquals(true, $this->hasher->check($reMatches[2], $resultSet[0]['token']));
  }
}