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
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Schema\Columns\ForeignKeyColumn;
use Rhubarb\Stem\Schema\ModelSchema;

class User extends \Rhubarb\Scaffolds\Authentication\User
{
    use PermissibleModelTrait {
        can as traitCan;
    }

    protected function extendSchema(ModelSchema $schema)
    {
        $schema->addColumn(new ForeignKeyColumn("RoleID"));

        parent::extendSchema($schema);
    }

    /**
     * @param string $permissionPath
     * @param Filter|null $filter
     * @return bool
     */
    public function can($permissionPath, $filter = null)
    {
        if ($filter == null) {
            $filter = $this->createPermissionPathFilter($permissionPath);
        }

        // test if user can
        if ($this->traitCan($permissionPath, $filter)) {
            return true;
        }

        // test primary role can
        if ($this->RoleID) {
            if ($this->Role->can($permissionPath, $filter)) {
                return true;
            }
        }

        // test other roles can
        /** @var Role $role */
        foreach ($this->Roles as $role) {
            if ($role->can($permissionPath, $filter)) {
                return true;
            }
        }

        // cannot
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
