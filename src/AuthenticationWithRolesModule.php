<?php

/*
 *	Copyright 2015 RhubarbPHP
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

namespace Rhubarb\Scaffolds\AuthenticationWithRoles;

use Rhubarb\Custard\Command\CustardCommand;
use Rhubarb\Scaffolds\Authentication\AuthenticationModule;
use Rhubarb\Scaffolds\AuthenticationWithRoles\Commands\UpdateRolePermissionsCommand;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\Not;
use Rhubarb\Stem\Filters\OneOf;
use Rhubarb\Stem\Schema\SolutionSchema;

/**
 * Adds the security groups and security options to the base login scaffold
 */
class AuthenticationWithRolesModule extends AuthenticationModule
{
    public function __construct($loginProviderClassName = null, $urlToProtect = "/", $loginUrl = "/login/")
    {
        parent::__construct($loginProviderClassName, $urlToProtect, $loginUrl);
    }

    public function initialise()
    {
        parent::initialise();

        SolutionSchema::registerSchema('Authentication', DatabaseSchema::class);
    }

    public static function updateRolePermissions()
    {
        /** @var Role[] $roleRecords */
        $roleRecords = [];
        /** @var Permission $permissionRecords */
        $permissionRecords = [];

        $permissionAssignmentIDs = [];

        $permissionMaintainer = function (
            $permissionRules,
            $allow
        ) use (
            &$permissionRecords,
            &$roleRecords,
            &$permissionAssignmentIDs
        ) {
            foreach ($permissionRules as $roleName => $permissions) {
                if (!isset($roleRecords[$roleName])) {
                    try {
                        $role = Role::findFirst(new Equals('RoleName', $roleName));
                    } catch (RecordNotFoundException $ex) {
                        $role = new Role();
                        $role->RoleName = $roleName;
                        $role->save();
                    }
                    $roleRecords[$roleName] = $role;
                }

                foreach ($permissions as $permissionPath) {
                    if (!isset($permissionRecords[$permissionPath])) {
                        try {
                            $permission = Permission::findFirst(new Equals('PermissionPath', $permissionPath));
                        } catch (RecordNotFoundException $ex) {
                            $permission = new Permission();
                            $permission->PermissionPath = $permissionPath;
                            $permission->PermissionName = $permissionPath;
                            $permission->save();
                        }
                        $permissionRecords[$permissionPath] = $permission;
                    }
                    $permissionAssignmentIDs[] = $allow
                        ? $roleRecords[$roleName]->allow($permissionRecords[$permissionPath])
                        : $roleRecords[$roleName]->deny($permissionRecords[$permissionPath]);
                }
            }
        };

        /** @var RolePermissionDefinitions $settings */
        $settings = RolePermissionDefinitions::singleton();

        $permissionMaintainer($settings->allowRolePermissions, true);
        $permissionMaintainer($settings->denyRolePermissions, false);

        if (count($permissionAssignmentIDs) > 0) {
            PermissionAssignment::find(
                new Not(new OneOf('PermissionAssignmentID', $permissionAssignmentIDs))
            )->deleteAll();
        }
    }

    /**
     * @return CustardCommand[]
     */
    public function getCustardCommands()
    {
        $commands = parent::getCustardCommands();
        $commands[] = new UpdateRolePermissionsCommand();

        return $commands;
    }
}
