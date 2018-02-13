<?php

namespace Tests\integration\Services;

use App\Models\User;
use Tests\integration\BaseIntegrationCase;

class RoleManagerTest extends BaseIntegrationCase
{
  protected $fixtures = [];

  public function __construct()
  {
    parent::__construct();
    $this->fixtures = [
      'roles' => [
        ['id' => 1, 'name' => 'ROLE_USER'],
        ['id' => 2, 'name' => 'ROLE_ADMIN'],
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
    ];
  }

  public function setUp(): void
  {
    parent::setUp();
    $this->pdo = parent::getPdo();
    $this->app = $this->getApp();
    $this->service = $this->app->make('App\Services\RoleManager');
  }

  public function testAssignIsChangingTheUserRoleAndPersistingItToTheDb()
  {
    $user = User::find(1);
    $this->assertEquals(true, $this->service->assign($user, 2));
    $this->assertEquals(2, $user->role_id);
    $this->assertEquals(2, $this->pdo->query('SELECT role_id FROM users WHERE id=1')->fetchAll(\PDO::FETCH_ASSOC)[0]['role_id']);
  }
}