<?php

namespace Rhubarb\Scaffolds\AuthenticationWithRoles\Tests;

use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Scaffolds\AuthenticationWithRoles\DatabaseSchema;
use Rhubarb\Scaffolds\AuthenticationWithRoles\Permission;
use Rhubarb\Scaffolds\AuthenticationWithRoles\PermissionAssignment;
use Rhubarb\Scaffolds\AuthenticationWithRoles\PermissionException;
use Rhubarb\Scaffolds\AuthenticationWithRoles\Role;
use Rhubarb\Scaffolds\AuthenticationWithRoles\User;
use Rhubarb\Scaffolds\AuthenticationWithRoles\UserRole;
use Rhubarb\Stem\Schema\SolutionSchema;

class UserTest extends RhubarbTestCase
{
    protected function setUp()
    {
        parent::setUp();

        SolutionSchema::registerSchema("Authentication", DatabaseSchema::class);

        User::ClearObjectCache();
        Role::ClearObjectCache();
        UserRole::ClearObjectCache();
        Permission::ClearObjectCache();
        PermissionAssignment::ClearObjectCache();
    }

    public function testRoleCanBeAdded()
    {
        $role = new Role();
        $role->RoleName = "Roll";
        $role->Save();

        $user = new User();
        $user->Username = "bob";
        $user->Forename = "bob";
        $user->Save();
        $user->AddToRole($role);

        $this->assertCount(1, UserRole::Find());
        $this->assertEquals($user->UniqueIdentifier, UserRole::Find()[0]->UserID);
        $this->assertEquals($role->UniqueIdentifier, UserRole::Find()[0]->RoleID);
    }

    public function testSeparateRolesCanBeAdded()
    {
        $role = new Role();
        $role->RoleName = "Roll";
        $role->Save();

        $role2 = new Role();
        $role2->RoleName = "EggRoll";
        $role2->Save();

        $user = new User();
        $user->Username = "bob";
        $user->Forename = "bob";
        $user->Save();
        $user->AddToRole($role);
        $user->AddToRole($role2);

        $this->assertCount(2, UserRole::Find());
    }

    public function testRoleCanBeAddedTwiceWithoutFailing()
    {
        $role = new Role();
        $role->RoleName = "Roll";
        $role->Save();

        $user = new User();
        $user->Username = "bob";
        $user->Forename = "bob";

        $user->AddToRole($role);
        $user->AddToRole($role);

        $this->assertCount(1, UserRole::Find());
    }

    public function testRoleCanBeRemoved()
    {
        $role = new Role();
        $role->RoleName = "Roll";
        $role->Save();

        $user = new User();
        $user->Username = "bob";
        $user->Forename = "bob";

        $user->AddToRole($role);
        $this->assertCount(1, UserRole::Find());

        $user->RemoveFromRole($role);
        $this->assertCount(0, UserRole::Find());
    }

    public function testOneOfTwoRolesCanBeRemoved()
    {
        $role = new Role();
        $role->RoleName = "Roll";
        $role->Save();

        $role2 = new Role();
        $role2->RoleName = "EggRoll";
        $role2->Save();

        $user = new User();
        $user->Username = "bob";
        $user->Forename = "bob";
        $user->Save();
        $user->AddToRole($role);
        $user->AddToRole($role2);

        $this->assertCount(2, UserRole::Find());

        $user->RemoveFromRole($role);
        $this->assertEquals("EggRoll", UserRole::Find()[0]->Role->RoleName);
    }

    public function testUserHasPermission()
    {
        $eatPermission = new Permission();
        $eatPermission->PermissionPath = "Goat/Eat";
        $eatPermission->PermissionName = "Eat";
        $eatPermission->Save();

        $user = new User();
        $user->Username = "bob";
        $user->Forename = "bob";
        $user->Save();
        $user->Allow($eatPermission);

        $this->assertTrue($user->Can($eatPermission->PermissionPath), "Can eat fail");

        $strokePermission = new Permission();
        $strokePermission->PermissionPath = "Goat/Stroke";
        $strokePermission->PermissionName = "Stroke";
        $strokePermission->Save();

        $user->Deny($strokePermission);

        $this->assertFalse($user->Can($strokePermission->PermissionPath), "can not stroke fail");
    }

    public function testIfNoPermissionThenDenied()
    {
        $user = new User();
        $user->Username = "bob";
        $user->Forename = "bob";
        $user->Save();

        $this->assertFalse($user->Can("Fire/Eat"));
    }

    public function testCannotAddPermissionToUnsavedUser()
    {
        $user = new User();
        $user->Username = "bob";
        $user->Forename = "bob";
        $this->setExpectedException(PermissionException::class);

        $eatPermission = new Permission();
        $eatPermission->PermissionPath = "Goat/Eat";
        $eatPermission->PermissionName = "Eat";
        $eatPermission->Save();
        $user->Allow($eatPermission);
    }

    public function testCannotAddUnsavedPermission()
    {
        $user = new User();
        $user->Username = "bob";
        $user->Forename = "bob";
        $user->Save();

        $this->setExpectedException(PermissionException::class);

        $eatPermission = new Permission();
        $eatPermission->PermissionPath = "Goat/Eat";
        $eatPermission->PermissionName = "Eat";
        $user->Allow($eatPermission);
    }

    public function testParentageOfPermissions()
    {
        $user = new User();
        $user->Username = "bob";
        $user->Forename = "bob";
        $user->Save();

        $permission = new Permission();
        $permission->PermissionPath = "Staff/Manage";
        $permission->Save();

        $user->Allow($permission);

        $permission = new Permission();
        $permission->PermissionPath = "Staff/Manage/Fire";
        $permission->Save();

        $this->assertTrue($user->Can("Staff/Manage/Fire"));
    }
}
