<?php

namespace Tests\unit\Models;

use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
  public function testUsersItShouldDefineTheRelationshipWithTheUserModelAndReturnTheRelations()
  {
    $hasManyMock = $this->createMock(HasMany::class);
    $roleModel = $this->getMockBuilder(Role::class)
      ->setMethods(['hasMany'])
      ->getMock();

    $roleModel->expects($this->once())
    ->method('hasMany')
    ->with('App\Models\User', 'role_id', 'id')
    ->willReturn($hasManyMock);

    $this->assertEquals($hasManyMock, $roleModel->users());
  }
}