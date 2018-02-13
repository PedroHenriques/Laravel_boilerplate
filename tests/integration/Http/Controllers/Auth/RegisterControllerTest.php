<?php

namespace Tests\integration\Http\Auth\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\integration\BaseIntegrationCase;

class RegisterControllerTest extends BaseIntegrationCase
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
      ],
      'account_activations' => [],
    ];
  }

  public function setUp(): void
  {
    parent::setUp();
    parent::clearEmails();
    $this->pdo = parent::getPdo();
    $this->app = $this->getApp();
    $this->controller = $this->app->make('App\Http\Controllers\Auth\RegisterController');
  }

  public function testRegisterIsCreatingTheNewUserAccountAndSendingTheActivationEmailIfTheNewAccountDataIsValid()
  {
    $getParams = [
      'username' => 'new username',
      'email' => 'new@email.com',
      'password' => 'password',
      'password_confirmation' => 'password',
    ];
    $request = new Request($getParams, [], [], [], [], [], []);

    $this->assertEquals(
      \Illuminate\Http\RedirectResponse::class,
      get_class($this->controller->register($request))
    );

    $query = 'SELECT username, email, password, is_active, role_id FROM users WHERE id='.(count($this->fixtures['users']) + 1);
    $resultSet = $this->pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC);
    $this->assertEquals(1, count($resultSet));

    $configRepo = $this->app->make('Illuminate\Contracts\Config\Repository');
    $hasher = $this->app->make('Illuminate\Contracts\Hashing\Hasher');

    $newUser = $resultSet[0];
    $this->assertEquals(true, $hasher->check($getParams['password'], $newUser['password']));
    $this->assertEquals($getParams['username'], $newUser['username']);
    $this->assertEquals($getParams['email'], $newUser['email']);
    $this->assertEquals(0, $newUser['is_active']);
    $this->assertEquals($configRepo->get('roles.newUserRoleId'), $newUser['role_id']);

    $sentEmails = parent::getEmails();
    $this->assertEquals(1, count($sentEmails));

    $query = "SELECT count(user_email) as count FROM account_activations WHERE user_email = '{$getParams['email']}'";
    $this->assertEquals(1, $this->pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC)[0]['count']);
  }

  public function testRegisterIsNotCreatingTheNewUserAccountIfTheNewAccountUsernameIsAlreadyTaken()
  {
    $request = new Request(
      [
        'username' => $this->fixtures['users'][0]['username'],
        'email' => 'new@email.com',
        'password' => 'password',
        'password_confirmation' => 'password',
      ],
      [], [], [], [], [], []
    );

    try {
      $exceptionThrown = false;
      $this->controller->register($request);
    } catch (ValidationException $e) {
      $exceptionThrown = true;
    }
    $this->assertEquals(true, $exceptionThrown);

    $this->assertEquals(
      count($this->fixtures['users']),
      $this->pdo->query('SELECT count(id) as count FROM users')->fetchAll(\PDO::FETCH_ASSOC)[0]['count']
    );

    $sentEmails = parent::getEmails();
    $this->assertEquals(0, count($sentEmails));

    $this->assertEquals(
      count($this->fixtures['account_activations']),
      $this->pdo->query('SELECT count(user_email) as count FROM account_activations')->fetchAll(\PDO::FETCH_ASSOC)[0]['count']
    );
  }

  public function testRegisterIsNotCreatingTheNewUserAccountIfTheNewAccountEmailIsAlreadyTaken()
  {
    $request = new Request(
      [
        'username' => 'new username',
        'email' => $this->fixtures['users'][0]['email'],
        'password' => 'password',
        'password_confirmation' => 'password',
      ],
      [], [], [], [], [], []
    );

    try {
      $exceptionThrown = false;
      $this->controller->register($request);
    } catch (ValidationException $e) {
      $exceptionThrown = true;
    }
    $this->assertEquals(true, $exceptionThrown);

    $this->assertEquals(
      count($this->fixtures['users']),
      $this->pdo->query('SELECT count(id) as count FROM users')->fetchAll(\PDO::FETCH_ASSOC)[0]['count']
    );

    $sentEmails = parent::getEmails();
    $this->assertEquals(0, count($sentEmails));

    $this->assertEquals(
      count($this->fixtures['account_activations']),
      $this->pdo->query('SELECT count(user_email) as count FROM account_activations')->fetchAll(\PDO::FETCH_ASSOC)[0]['count']
    );
  }
}