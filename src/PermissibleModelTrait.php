<?php

namespace Rhubarb\Scaffolds\AuthenticationWithRoles;

use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Filters\AndGroup;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Models\Model;

/**
 * Helpful methods for models which can be granted permissions using a PermissionAssignment record
 */
trait PermissibleModelTrait
{

    /**
     * @param Permission $permission
     *
     * @throws PermissionException
     */
    public function allow(Permission $permission)
    {
        $this->setPermissionSetting($permission, true);
    }

    /**
     * @param Permission $permission
     *
     * @throws PermissionException
     */
    public function deny(Permission $permission)
    {
        $this->setPermissionSetting($permission, false);
    }

    /**
     * @param Permission $permission
     * @param bool|true  $allowed
     *
     * @throws PermissionException
     * @throws \Exception
     * @throws \Rhubarb\Stem\Exceptions\ModelConsistencyValidationException
     */
    protected function setPermissionSetting(Permission $permission, $allowed = true)
    {
        /** @var Model $this */
        if ($permission->isNewRecord()) {
            throw new PermissionException("The permission has not been saved.");
        }

        if ($this->isNewRecord()) {
            throw new PermissionException("The model has not been saved.");
        }

        $foreignKeyField = $this->getUniqueIdentifierColumnName();
        $foreignKeyValue = $this->getUniqueIdentifier();
        try {
            $assignment = PermissionAssignment::findFirst(
                new AndGroup([
                    new Equals('PermissionID', $permission->PermissionID),
                    new Equals($foreignKeyField, $foreignKeyValue)
                ])
            );
        } catch (RecordNotFoundException $ex) {
            $assignment = new PermissionAssignment();
            $assignment->PermissionID = $permission->PermissionID;
            $assignment->$foreignKeyField = $foreignKeyValue;
        }
        $assignment->Access = $allowed ? 'Allowed' : 'Denied';
        $assignment->save();
    }
}