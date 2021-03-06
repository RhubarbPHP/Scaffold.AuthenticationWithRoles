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
use Rhubarb\Stem\Schema\Columns\AutoIncrementColumn;
use Rhubarb\Stem\Schema\Columns\StringColumn;
use Rhubarb\Stem\Schema\ModelSchema;

/**
 *
 * @property string $RoleName
 */
class Role extends Model
{

    use PermissibleModelTrait;

    /**
     * Returns the schema for this data object.
     *
     * @return \Rhubarb\Stem\Schema\ModelSchema
     */
    protected function createSchema()
    {
        $schema = new ModelSchema("tblAuthenticationRole");
        $schema->addColumn(
            new AutoIncrementColumn("RoleID"),
            new StringColumn("RoleName", 40)
        );

        $schema->labelColumnName = "RoleName";

        return $schema;
    }

    protected function afterDelete()
    {
        parent::afterDelete();

        // Remove UserRole and PermissionAssignment records
        $this->UsersRaw->deleteAll();
        $this->Permissions->deleteAll();
    }
}
