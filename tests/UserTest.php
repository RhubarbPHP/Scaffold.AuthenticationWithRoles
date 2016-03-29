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

        User::clearObjectCache();
        Role::clearObjectCache();
        UserRole::clearObjectCache();
        Permission::clearObjectCache();
        PermissionAssignment::clearObjectCache();
    }

    public function testRoleCanBeAdded()
    {
        $role = new Role();
        $role->RoleName = "Roll";
        $role->save();

        $user = new User();
        $user->Username = "bob";
        $user->Forename = "bob";
        $user->save();
        $user->addToRole($role);

        $this->assertCount(1, UserRole::find());
        $this->assertEquals($user->UniqueIdentifier, UserRole::find()[0]->UserID);
        $this->assertEquals($role->UniqueIdentifier, UserRole::find()[0]->RoleID);
    }

    public function testSeparateRolesCanBeAdded()
    {
        $role = new Role();
        $role->RoleName = "Roll";
        $role->save();

        $role2 = new Role();
        $role2->RoleName = "EggRoll";
        $role2->save();

        $user = new User();
        $user->Username = "bob";
        $user->Forename = "bob";
        $user->save();
        $user->addToRole($role);
        $user->addToRole($role2);

        $this->assertCount(2, UserRole::find());
    }

    public function testRoleCanBeAddedTwiceWithoutFailing()
    {
        $role = new Role();
        $role->RoleName = "Roll";
        $role->save();

        $user = new User();
        $user->Username = "bob";
        $user->Forename = "bob";

        $user->addToRole($role);
        $user->addToRole($role);

        $this->assertCount(1, UserRole::find());
    }

    public function testRoleCanBeRemoved()
    {
        $role = new Role();
        $role->RoleName = "Roll";
        $role->save();

        $user = new User();
        $user->Username = "bob";
        $user->Forename = "bob";

        $user->addToRole($role);
        $this->assertCount(1, UserRole::find());

        $user->removeFromRole($role);
        $this->assertCount(0, UserRole::find());
    }

    public function testOneOfTwoRolesCanBeRemoved()
    {
        $role = new Role();
        $role->RoleName = "Roll";
        $role->save();

        $role2 = new Role();
        $role2->RoleName = "EggRoll";
        $role2->save();

        $user = new User();
        $user->Username = "bob";
        $user->Forename = "bob";
        $user->save();
        $user->addToRole($role);
        $user->addToRole($role2);

        $this->assertCount(2, UserRole::find());

        $user->removeFromRole($role);
        $this->assertEquals("EggRoll", UserRole::find()[0]->Role->RoleName);
    }

    public function testUserHasPermission()
    {
        $eatPermission = new Permission();
        $eatPermission->PermissionPath = "Goat/Eat";
        $eatPermission->PermissionName = "Eat";
        $eatPermission->save();

        $user = new User();
        $user->Username = "bob";
        $user->Forename = "bob";
        $user->save();
        $user->allow($eatPermission);

        $this->assertTrue($user->can($eatPermission->PermissionPath), "Can eat fail");

        $strokePermission = new Permission();
        $strokePermission->PermissionPath = "Goat/Stroke";
        $strokePermission->PermissionName = "Stroke";
        $strokePermission->save();

        $user->deny($strokePermission);

        $this->assertFalse($user->can($strokePermission->PermissionPath), "can not stroke fail");
    }

    public function testIfNoPermissionThenDenied()
    {
        $user = new User();
        $user->Username = "bob";
        $user->Forename = "bob";
        $user->save();

        $this->assertFalse($user->can("Fire/Eat"));
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
        $eatPermission->save();
        $user->allow($eatPermission);
    }

    public function testCannotAddUnsavedPermission()
    {
        $user = new User();
        $user->Username = "bob";
        $user->Forename = "bob";
        $user->save();

        $this->setExpectedException(PermissionException::class);

        $eatPermission = new Permission();
        $eatPermission->PermissionPath = "Goat/Eat";
        $eatPermission->PermissionName = "Eat";
        $user->allow($eatPermission);
    }

    public function testParentageOfPermissions()
    {
        $user = new User();
        $user->Username = "bob";
        $user->Forename = "bob";
        $user->save();

        $permission = new Permission();
        $permission->PermissionPath = "Staff/Manage";
        $permission->save();

        $user->allow($permission);

        $permission = new Permission();
        $permission->PermissionPath = "Staff/Manage/Fire";
        $permission->save();

        $this->assertTrue($user->can("Staff/Manage/Fire"));
    }
}
