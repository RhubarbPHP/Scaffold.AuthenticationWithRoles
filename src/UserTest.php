<?php


namespace Rhubarb\Scaffolds\AuthenticationWithRoles;

use Rhubarb\Crown\UnitTesting\CoreTestCase;


class UserTest extends CoreTestCase
{
    protected function setUp()
    {
        parent::setUp();

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
        $this->setExpectedException(__NAMESPACE__ . "\PermissionException");

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

        $this->setExpectedException(__NAMESPACE__ . "\PermissionException");

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
