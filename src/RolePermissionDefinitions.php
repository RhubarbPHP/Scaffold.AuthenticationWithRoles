<?php

namespace Rhubarb\Scaffolds\AuthenticationWithRoles;

use Rhubarb\Crown\Settings;

class RolePermissionDefinitions extends Settings
{
    public $allowRolePermissions = [];
    public $denyRolePermissions = [];

    /**
     * @param string $role
     * @param array $permissions
     */
    public function allowPermissionsForRole($role, $permissions)
    {
        $this->setRolePermissionProperty($role, $permissions, true);
    }

    /**
     * @param string $role
     * @param array $permissions
     */
    public function denyPermissionsForRole($role, $permissions)
    {
        $this->setRolePermissionProperty($role, $permissions, false);
    }

    /**
     * @param string $role
     * @param array $allowPermissions
     * @param array $denyPermissions
     */
    public function setPermissionsForRole($role, $allowPermissions, $denyPermissions)
    {
        $this->setRolePermissionProperty($role, $allowPermissions, true);
        $this->setRolePermissionProperty($role, $denyPermissions, false);
    }

    /**
     * @param string $role
     * @param string $permissions
     * @param bool $allow
     */
    private function setRolePermissionProperty($role, $permissions, $allow)
    {
        $property = $allow ? 'allowRolePermissions' : 'denyRolePermissions';
        $array = $this->$property;
        $array[$role] = isset($array[$role])
            ? array_merge($array[$role], $permissions)
            : $permissions;
        $this->$property = $array;
    }
}
