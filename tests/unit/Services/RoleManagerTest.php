<?php

namespace Tests\unit\Services;

use App\Models\User;
use App\Services\RoleManager;
use Illuminate\Contracts\Config\Repository as Config;
use PHPUnit\Framework\TestCase;

class RoleManagerTest extends TestCase
{
  public function setUp()
  {
    $this->configMock = $this->createMock(Config::class);
  }

  public function testConstructorIfTheRoleHierarchyCanNotBeFoundInTheRolesConfigFileItShouldThrowAnExceptionWithAnErrorMessage()
  {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Failed to find the role hierarchy in the roles config file.');

    $this->configMock->expects($this->once())
    ->method('get')
    ->with('roles.hierarchy')
    ->willReturn(null);

    new RoleManager($this->configMock);
  }

  public function testHasIfTheUserHasTheExactRoleBeingRequestedItShouldReturnTrue()
  {
    $userModel = new User(['role_id' => 1]);

    $this->configMock->expects($this->once())
    ->method('get')
    ->with('roles.hierarchy')
    ->willReturn([
      2 => 1,
      3 => 2,
      4 => 3,
    ]);

    $roleManager = new RoleManager($this->configMock);
    $this->assertEquals(true, $roleManager->has($userModel, 1));
  }

  public function testHasIfTheUserHasTheExactRoleBeingRequestedAndTheRoleHierarchyIsEmptyItShouldReturnTrue()
  {
    $userModel = new User(['role_id' => 1]);

    $this->configMock->expects($this->once())
    ->method('get')
    ->with('roles.hierarchy')
    ->willReturn([]);

    $roleManager = new RoleManager($this->configMock);
    $this->assertEquals(true, $roleManager->has($userModel, 1));
  }

  public function testHasIfTheUserHasARoleThatIsHigherInTheHierarchyThanTheRoleBeingRequestedItShouldReturnTrue()
  {
    $userModel = new User(['role_id' => 3]);

    $this->configMock->expects($this->once())
    ->method('get')
    ->with('roles.hierarchy')
    ->willReturn([
      2 => 1,
      3 => 2,
      4 => 3,
    ]);

    $roleManager = new RoleManager($this->configMock);
    $this->assertEquals(true, $roleManager->has($userModel, 2));
  }

  public function testHasIfTheUserHasARoleThatIsHigherInTheHierarchyThanTheRoleBeingRequestedAndTheRoleHierarchyIsEmptyItShouldReturnFalse()
  {
    $userModel = new User(['role_id' => 3]);

    $this->configMock->expects($this->once())
    ->method('get')
    ->with('roles.hierarchy')
    ->willReturn([]);

    $roleManager = new RoleManager($this->configMock);
    $this->assertEquals(false, $roleManager->has($userModel, 2));
  }

  public function testHasIfTheUserHasARoleThatIsLowerInTheHierarchyThanTheRoleBeingRequestedItShouldReturnFalse()
  {
    $userModel = new User(['role_id' => 3]);

    $this->configMock->expects($this->once())
    ->method('get')
    ->with('roles.hierarchy')
    ->willReturn([
      2 => 1,
      3 => 2,
      4 => 3,
    ]);

    $roleManager = new RoleManager($this->configMock);
    $this->assertEquals(false, $roleManager->has($userModel, 4));
  }

  public function testHasIfTheUserHasARoleThatIsLowerInTheHierarchyThanTheRoleBeingRequestedAndTheRoleHierarchyIsEmptyItShouldReturnFalse()
  {
    $userModel = new User(['role_id' => 3]);

    $this->configMock->expects($this->once())
    ->method('get')
    ->with('roles.hierarchy')
    ->willReturn([]);

    $roleManager = new RoleManager($this->configMock);
    $this->assertEquals(false, $roleManager->has($userModel, 4));
  }

  public function testAssignItShouldSetTheUserModelPropertyToTheProvidedRoleIdThenCallTheSaveorfailMethodOnTheUserModelAndReturnTrue()
  {
    $userMock = $this->getMockBuilder(User::class)
      ->setMethods(['saveOrFail'])
      ->setConstructorArgs([['role_id' => 2]])
      ->getMock();
    
    $this->configMock->expects($this->once())
    ->method('get')
    ->with('roles.hierarchy')
    ->willReturn([
      2 => 1,
      3 => 2,
      4 => 3,
    ]);

    $actualRoleId = null;
    $userMock->expects($this->once())
    ->method('saveOrFail')
    ->with($this->callback(function () use (&$actualRoleId, $userMock) {
      $actualRoleId = $userMock->role_id;
      return(true);
    }))
    ->willReturn(true);

    $roleManager = new RoleManager($this->configMock);
    $this->assertEquals(true, $roleManager->assign($userMock, 4));
    $this->assertEquals(4, $actualRoleId);
  }

  public function testAssignIfTheModelFailsToBePersistedToTheDbItShouldReturnFalse()
  {
    $userMock = $this->getMockBuilder(User::class)
      ->setMethods(['saveOrFail'])
      ->setConstructorArgs([['role_id' => 2]])
      ->getMock();
    
    $this->configMock->expects($this->once())
    ->method('get')
    ->with('roles.hierarchy')
    ->willReturn([
      2 => 1,
      3 => 2,
      4 => 3,
    ]);

    $actualRoleId = null;
    $userMock->expects($this->once())
    ->method('saveOrFail')
    ->with($this->callback(function () use (&$actualRoleId, $userMock) {
      $actualRoleId = $userMock->role_id;
      return(true);
    }))
    ->willReturn(false);

    $roleManager = new RoleManager($this->configMock);
    $this->assertEquals(false, $roleManager->assign($userMock, 4));
    $this->assertEquals(4, $actualRoleId);
  }

  public function testAssignIfTheUserAlreadyHasTheProvidedRoleItShouldReturnTrueWithoutCallingTheDb()
  {
    $userMock = $this->getMockBuilder(User::class)
      ->setMethods(['saveOrFail'])
      ->setConstructorArgs([['role_id' => 2]])
      ->getMock();
    
    $this->configMock->expects($this->once())
    ->method('get')
    ->with('roles.hierarchy')
    ->willReturn([
      2 => 1,
      3 => 2,
      4 => 3,
    ]);

    $userMock->expects($this->exactly(0))
    ->method('saveOrFail');

    $roleManager = new RoleManager($this->configMock);
    $this->assertEquals(true, $roleManager->assign($userMock, 2));
    $this->assertEquals(2, $userMock->role_id);
  }

  public function testPromoteIfTheUserHasARoleThatIsLowerInTheHierarchyThanTheRoleBeingRequestedItShouldSetTheUserModelPropertyToTheProvidedRoleIdThenCallTheSaveorfailMethodOnTheUserModelAndReturnTrue()
  {
    $userMock = $this->getMockBuilder(User::class)
      ->setMethods(['saveOrFail'])
      ->setConstructorArgs([['role_id' => 1]])
      ->getMock();
    
    $this->configMock->expects($this->once())
    ->method('get')
    ->with('roles.hierarchy')
    ->willReturn([
      2 => 1,
      3 => 2,
      4 => 3,
    ]);

    $actualRoleId = null;
    $userMock->expects($this->once())
    ->method('saveOrFail')
    ->with($this->callback(function () use (&$actualRoleId, $userMock) {
      $actualRoleId = $userMock->role_id;
      return(true);
    }))
    ->willReturn(true);

    $roleManager = new RoleManager($this->configMock);
    $this->assertEquals(true, $roleManager->promote($userMock, 3));
    $this->assertEquals(3, $actualRoleId);
  }

  public function testPromoteIfTheUserHasARoleThatIsLowerInTheHierarchyThanTheRoleBeingRequestedAndTheModelFailsToBePersistedToTheDbItShouldReturnFalse()
  {
    $userMock = $this->getMockBuilder(User::class)
      ->setMethods(['saveOrFail'])
      ->setConstructorArgs([['role_id' => 1]])
      ->getMock();
    
    $this->configMock->expects($this->once())
    ->method('get')
    ->with('roles.hierarchy')
    ->willReturn([
      2 => 1,
      3 => 2,
      4 => 3,
    ]);

    $actualRoleId = null;
    $userMock->expects($this->once())
    ->method('saveOrFail')
    ->with($this->callback(function () use (&$actualRoleId, $userMock) {
      $actualRoleId = $userMock->role_id;
      return(true);
    }))
    ->willReturn(false);

    $roleManager = new RoleManager($this->configMock);
    $this->assertEquals(false, $roleManager->promote($userMock, 3));
    $this->assertEquals(3, $actualRoleId);
  }

  public function testPromoteIfTheUserHasARoleThatIsHigherInTheHierarchyThanTheRoleBeingRequestedItShouldNotMakeAnyChangeToTheUserAndReturnTrue()
  {
    $userMock = $this->getMockBuilder(User::class)
      ->setMethods(['saveOrFail'])
      ->setConstructorArgs([['role_id' => 3]])
      ->getMock();
    
    $this->configMock->expects($this->once())
    ->method('get')
    ->with('roles.hierarchy')
    ->willReturn([
      2 => 1,
      3 => 2,
      4 => 3,
    ]);

    $userMock->expects($this->exactly(0))
    ->method('saveOrFail');

    $roleManager = new RoleManager($this->configMock);
    $this->assertEquals(true, $roleManager->promote($userMock, 2));
    $this->assertEquals(3, $userMock->role_id);
  }

  public function testPromoteIfTheUserHasTheExactRoleThatIsBeingRequestedItShouldNotMakeAnyChangeToTheUserAndReturnTrue()
  {
    $userMock = $this->getMockBuilder(User::class)
      ->setMethods(['saveOrFail'])
      ->setConstructorArgs([['role_id' => 3]])
      ->getMock();
    
    $this->configMock->expects($this->once())
    ->method('get')
    ->with('roles.hierarchy')
    ->willReturn([
      2 => 1,
      3 => 2,
      4 => 3,
    ]);

    $userMock->expects($this->exactly(0))
    ->method('saveOrFail');

    $roleManager = new RoleManager($this->configMock);
    $this->assertEquals(true, $roleManager->promote($userMock, 3));
    $this->assertEquals(3, $userMock->role_id);
  }

  public function testPromoteIfTheRoleHierarchyIsEmptyItShouldReturnFalse()
  {
    $userMock = $this->getMockBuilder(User::class)
      ->setMethods(['saveOrFail'])
      ->setConstructorArgs([['role_id' => 1]])
      ->getMock();
    
    $this->configMock->expects($this->once())
    ->method('get')
    ->with('roles.hierarchy')
    ->willReturn([]);

    $userMock->expects($this->exactly(0))
    ->method('saveOrFail');

    $roleManager = new RoleManager($this->configMock);
    $this->assertEquals(false, $roleManager->promote($userMock, 3));
  }

  public function testDemoteIfTheUserHasARoleThatIsHigherInTheHierarchyThanTheRoleBeingRequestedItShouldSetTheUserModelPropertyToTheProvidedRoleIdThenCallTheSaveorfailMethodOnTheUserModelAndReturnTrue()
  {
    $userMock = $this->getMockBuilder(User::class)
      ->setMethods(['saveOrFail'])
      ->setConstructorArgs([['role_id' => 3]])
      ->getMock();
  
    $this->configMock->expects($this->once())
    ->method('get')
    ->with('roles.hierarchy')
    ->willReturn([
      2 => 1,
      3 => 2,
      4 => 3,
    ]);

    $actualRoleId = null;
    $userMock->expects($this->once())
    ->method('saveOrFail')
    ->with($this->callback(function () use (&$actualRoleId, $userMock) {
      $actualRoleId = $userMock->role_id;
      return(true);
    }))
    ->willReturn(true);

    $roleManager = new RoleManager($this->configMock);
    $this->assertEquals(true, $roleManager->demote($userMock, 1));
    $this->assertEquals(1, $actualRoleId);
  }

  public function testDemoteIfTheUserHasARoleThatIsHigherInTheHierarchyThanTheRoleBeingRequestedAndTheModelFailsToBePersistedToTheDbItShouldReturnFalse()
  {
    $userMock = $this->getMockBuilder(User::class)
      ->setMethods(['saveOrFail'])
      ->setConstructorArgs([['role_id' => 3]])
      ->getMock();
  
    $this->configMock->expects($this->once())
    ->method('get')
    ->with('roles.hierarchy')
    ->willReturn([
      2 => 1,
      3 => 2,
      4 => 3,
    ]);

    $actualRoleId = null;
    $userMock->expects($this->once())
    ->method('saveOrFail')
    ->with($this->callback(function () use (&$actualRoleId, $userMock) {
      $actualRoleId = $userMock->role_id;
      return(true);
    }))
    ->willReturn(false);

    $roleManager = new RoleManager($this->configMock);
    $this->assertEquals(false, $roleManager->demote($userMock, 1));
    $this->assertEquals(1, $actualRoleId);
  }

  public function testDemoteIfTheUserHasARoleThatIsLowerInTheHierarchyThanTheRoleBeingRequestedItShouldNotMakeAnyChangeToTheUserAndReturnTrue()
  {
    $userMock = $this->getMockBuilder(User::class)
      ->setMethods(['saveOrFail'])
      ->setConstructorArgs([['role_id' => 2]])
      ->getMock();
  
    $this->configMock->expects($this->once())
    ->method('get')
    ->with('roles.hierarchy')
    ->willReturn([
      2 => 1,
      3 => 2,
      4 => 3,
    ]);

    $userMock->expects($this->exactly(0))
    ->method('saveOrFail');

    $roleManager = new RoleManager($this->configMock);
    $this->assertEquals(true, $roleManager->demote($userMock, 3));
    $this->assertEquals(2, $userMock->role_id);
  }

  public function testDemoteIfTheUserHasTheExactRoleThatIsBeingRequestedItShouldNotMakeAnyChangeToTheUserAndReturnTrue()
  {
    $userMock = $this->getMockBuilder(User::class)
      ->setMethods(['saveOrFail'])
      ->setConstructorArgs([['role_id' => 2]])
      ->getMock();
  
    $this->configMock->expects($this->once())
    ->method('get')
    ->with('roles.hierarchy')
    ->willReturn([
      2 => 1,
      3 => 2,
      4 => 3,
    ]);

    $userMock->expects($this->exactly(0))
    ->method('saveOrFail');

    $roleManager = new RoleManager($this->configMock);
    $this->assertEquals(true, $roleManager->demote($userMock, 2));
    $this->assertEquals(2, $userMock->role_id);
  }

  public function testDemoteIfTheRoleHierarchyIsEmptyItShouldReturnFalse()
  {
    $userMock = $this->getMockBuilder(User::class)
      ->setMethods(['saveOrFail'])
      ->setConstructorArgs([['role_id' => 3]])
      ->getMock();
  
    $this->configMock->expects($this->once())
    ->method('get')
    ->with('roles.hierarchy')
    ->willReturn([]);

    $userMock->expects($this->exactly(0))
    ->method('saveOrFail');

    $roleManager = new RoleManager($this->configMock);
    $this->assertEquals(false, $roleManager->demote($userMock, 1));
  }
}