<?php

namespace Rhubarb\Scaffolds\AuthenticationWithRoles;

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Filters\AndGroup;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Filters\OrGroup;
use Rhubarb\Stem\Models\Model;

/**
 * Helpful methods for models which can be granted permissions using a PermissionAssignment record
 */
trait PermissibleModelTrait
{

    /**
     * @param Permission $permission
     *
     * @return int The associated Permission Assignment record ID
     * @throws PermissionException
     */
    public function allow(Permission $permission)
    {
        return $this->setPermissionSetting($permission, true);
    }

    /**
     * @param Permission $permission
     *
     * @return int The associated Permission Assignment record ID
     * @throws PermissionException
     */
    public function deny(Permission $permission)
    {
        return $this->setPermissionSetting($permission, false);
    }

    /**
     * @param Permission $permission
     * @param bool|true $allowed
     *
     * @return int The associated Permission Assignment record ID
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
                    new Equals($foreignKeyField, $foreignKeyValue),
                ])
            );
        } catch (RecordNotFoundException $ex) {
            $assignment = new PermissionAssignment();
            $assignment->PermissionID = $permission->PermissionID;
            $assignment->$foreignKeyField = $foreignKeyValue;
        }
        $assignment->Access = $allowed ? 'Allowed' : 'Denied';
        $assignment->save();

        return $assignment->PermissionAssignmentID;
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

        /** @var Collection $permissionCollection */
        $permissionCollection = $this->Permissions;
        $permissionCollection->intersectWith(
            Permission::find($filter),
            'PermissionID',
            'PermissionID',
            ['PermissionPath']
        );
        $permissionCollection->addSort('PermissionPath', false);

        if (count($permissionCollection) > 0) {
            $assignedPermission = $permissionCollection[0];
            return $assignedPermission->Access == "Allowed";
        }

        return false;
    }

    /**
     * @param string $permissionPath
     * @return OrGroup
     */
    protected function createPermissionPathFilter($permissionPath)
    {
        /**
         * use the most specific permission available
         * In the case where Manage/Staff/Fire is NOT specified on the system BUT
         * Manage/Staff *is* We want to apply the permission for Manage/Staff
         */
        $permissionPathParts = preg_split('|[\/]+|', $permissionPath);
        $permissionPathPartsString = "";
        $filters = [];

        foreach ($permissionPathParts as $pathPart) {
            $permissionPathPartsString .= (strlen($permissionPathPartsString) > 0 ? "/" : "") . $pathPart;
            $filters[] = new Equals("PermissionPath", $permissionPathPartsString);
        }

        return new OrGroup($filters);
    }
}
