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

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\AutoIncrement;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\ForeignKey;
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlSchema;

class UserRole extends Model
{
    protected function createSchema()
    {
        $schema = new MySqlSchema("tblAuthenticationUserRole");
        $schema->addColumn(
            new AutoIncrement("UserRoleID"),
            new ForeignKey("UserID"),
            new ForeignKey("RoleID")
        );

        return $schema;
    }
}