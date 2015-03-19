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

use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\OrGroup;
use Rhubarb\Stem\Schema\Columns\ForeignKey;
use Rhubarb\Stem\Schema\ModelSchema;

class User extends \Rhubarb\Scaffolds\Authentication\User
{
    protected function extendSchema(ModelSchema $schema)
    {
        $schema->addColumn(new ForeignKey("RoleID"));

        parent::extendSchema($schema);
    }

    public function allow(Permission $permission)
    {
        $this->setPermissionSetting($permission, true);
    }

    public function deny(Permission $permission)
    {
        $this->setPermissionSetting($permission, false);
    }

    private function setPermissionSetting(Permission $permission, $allowed = true)
    {
        if ($permission->isNewRecord()) {
            throw new PermissionException("The permission has not been saved.");
        }

        if ($this->isNewRecord()) {
            throw new PermissionException("The user has not been saved.");
        }

        $assignment = new PermissionAssignment();
        $assignment->PermissionID = $permission->PermissionID;
        $assignment->UserID = $this->UniqueIdentifier;
        $assignment->Access = ($allowed) ? "Allowed" : "Denied";
        $assignment->save();
    }

    public function can($permissionPath)
    {
        /**
         * use the most specific permission available
         * In the case where Manage/Staff/Fire is NOT specified on the system BUT
         * Manage/Staff *is* We want to apply the permission for Manage/Staff
         */
        $permissionPathParts = preg_split("|[\\/]+|", $permissionPath);
        $permissionPathPartsString = "";
        $filters = array();

        foreach ($permissionPathParts as $pathPart) {
            $permissionPathPartsString .= (strlen($permissionPathPartsString) > 0 ? "/" : "") . $pathPart;
            $filters[] = new Equals("Permission.PermissionPath", $permissionPathPartsString);
        }

        $finalFilter = new OrGroup($filters);

        $permissionCollection = $this->Permissions;
        $permissionCollection->filter($finalFilter);
        $permissionCollection->addSort("Permission.PermissionPath", false);

        if (count($permissionCollection) > 0) {
            $assignedPermission = $permissionCollection[0];
            return $assignedPermission->Access == "Allowed";
        }

        if ($this->RoleID) {
            if ($this->Role->can($permissionPath, $finalFilter)) {
                return true;
            }
        }

        /** @var Role $role */
        foreach ($this->Roles as $role) {
            if ($role->can($permissionPath, $finalFilter)) {
                return true;
            }
        }

        return false;
    }

    public function addToRole(Role $role)
    {
        $existingUsersInRole = $role->Users;
        $existingUsersInRole->filter(new Equals("UserID", $this->UniqueIdentifier));

        if (count($existingUsersInRole) == 0) {
            $role->Users->append($this);
        }
    }

    public function removeFromRole(Role $role)
    {
        $existingUsersInRole = $role->UsersRaw;
        $existingUsersInRole->filter(new Equals("UserID", $this->UniqueIdentifier));
        $existingUsersInRole->deleteAll();
    }

    /**
     * Checks if user has a specific role
     * @param $name string
     * @return bool
     */
    public function hasRole($name)
    {
        if ($this->RoleID) {
            $role = new Role($this->RoleID);
            if ($role->RoleName == $name) {
                return true;
            }
        }

        foreach ($this->Roles as $role) {
            if ($role->RoleName == $name) {
                return true;
            }
        }
        return false;
    }
}