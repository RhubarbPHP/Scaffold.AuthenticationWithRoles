<?php

namespace Rhubarb\Scaffolds\AuthenticationWithRoles\Tests\unit;

use Rhubarb\Crown\Application;
use Rhubarb\Crown\Layout\LayoutModule;
use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Scaffolds\Authentication\LoginProviders\LoginProvider;
use Rhubarb\Scaffolds\AuthenticationWithRoles\AuthenticationWithRolesModule;
use Rhubarb\Scaffolds\AuthenticationWithRoles\DatabaseSchema;
use Rhubarb\Scaffolds\AuthenticationWithRoles\Permission;
use Rhubarb\Scaffolds\AuthenticationWithRoles\PermissionAssignment;
use Rhubarb\Scaffolds\AuthenticationWithRoles\PermissionException;
use Rhubarb\Scaffolds\AuthenticationWithRoles\Role;
use Rhubarb\Scaffolds\AuthenticationWithRoles\RolePermissionDefinitions;
use Rhubarb\Scaffolds\AuthenticationWithRoles\User;
use Rhubarb\Scaffolds\AuthenticationWithRoles\UserRole;
use Rhubarb\Stem\Repositories\Offline\Offline;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\SolutionSchema;

class UserTest extends RhubarbTestCase
{
    protected function setUp()
    {
        parent::setUp();

        Repository::setDefaultRepositoryClassName(Offline::class);
        SolutionSchema::registerSchema("Authentication", DatabaseSchema::class);

        $app = new Application();
        $app->registerModule(new AuthenticationWithRolesModule(LoginProvider::class));
        $app->unitTesting = true;
        $app->initialiseModules();

        LayoutModule::disableLayout();
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

    public function testRolePermissionManager()
    {
        self::assertCount(0, Role::all());
        self::assertCount(0, Permission::all());
        AuthenticationWithRolesModule::updateRolePermissions();
        self::assertCount(0, Role::all());
        self::assertCount(0, Permission::all());

        /** @var RolePermissionDefinitions $definitions */
        $definitions = RolePermissionDefinitions::singleton();
        $definitions->allowPermissionsForRole('r1', ['p1']);
        $definitions->allowPermissionsForRole('r2', []);
        AuthenticationWithRolesModule::updateRolePermissions();
        $p1 = Permission::findFirst();
        $r1 = Role::findFirst();
        $r2 = Role::findLast();
        self::assertEquals('p1', $p1->PermissionPath);
        self::assertEquals('r1', $r1->RoleName);
        self::assertEquals('r2', $r2->RoleName);
        self::assertTrue($r1->can('p1'));
        self::assertFalse($r2->can('p1'));

        $definitions->allowPermissionsForRole('r2', ['p1/p2']);
        AuthenticationWithRolesModule::updateRolePermissions();
        self::assertTrue($r1->can('p1/p2'));
        self::assertTrue($r2->can('p1/p2'));

        $definitions->denyPermissionsForRole('r1', ['p1/p2/p3']);
        AuthenticationWithRolesModule::updateRolePermissions();
        self::assertFalse($r1->can('p1/p2/p3'));
        self::assertTrue($r2->can('p1/p2/p3'));
    }
}
