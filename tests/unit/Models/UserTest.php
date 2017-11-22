<?php

namespace Tests\unit\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
  public function testRolesItShouldDefineTheRelationshipWithTheRoleModelAndReturnTheRelations()
  {
    $belongsToMock = $this->createMock(BelongsTo::class);
    $userModel = $this->getMockBuilder(User::class)
      ->setMethods(['belongsTo'])
      ->getMock();

    $userModel->expects($this->once())
    ->method('belongsTo')
    ->with('App\Models\Role', 'role_id', 'id')
    ->willReturn($belongsToMock);

    $this->assertEquals($belongsToMock, $userModel->roles());
  }
}