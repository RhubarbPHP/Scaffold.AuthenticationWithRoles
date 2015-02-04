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

class DatabaseSchema extends \Rhubarb\Scaffolds\Authentication\DatabaseSchema
{
    public function __construct()
    {
        parent::__construct(0.2);

        $this->addModel("Role", 'Rhubarb\Scaffolds\AuthenticationWithRoles\Role');
        $this->addModel("User", 'Rhubarb\Scaffolds\AuthenticationWithRoles\User');
        $this->addModel("UserRole", 'Rhubarb\Scaffolds\AuthenticationWithRoles\UserRole');
        $this->addModel("Permission", 'Rhubarb\Scaffolds\AuthenticationWithRoles\Permission');
        $this->addModel("PermissionAssignment", 'Rhubarb\Scaffolds\AuthenticationWithRoles\PermissionAssignment');
    }

    protected function defineRelationships()
    {
        $this->declareOneToManyRelationships(
            [
                "Role" =>
                    [
                        "Permissions" => "PermissionAssignment.RoleID",
                        "Users" => "User.RoleID"
                    ],
                "User" =>
                    [
                        "Permissions" => "PermissionAssignment.UserID"
                    ],
                "Permission" =>
                    [
                        "Assignments" => "PermissionAssignment.PermissionID"
                    ]

            ]
        );

        $this->declareManyToManyRelationships(
            [
                "User" =>
                    [
                        "Roles" => "UserRole.UserID_RoleID.Role:Users"
                    ]
            ]
        );

        parent::defineRelationships();
    }
}