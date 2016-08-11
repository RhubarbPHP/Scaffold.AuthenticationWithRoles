<?php

namespace Rhubarb\Scaffolds\AuthenticationWithRoles;

use Rhubarb\Stem\Custard\RequiresRepositoryCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateRolePermissionsCommand extends RequiresRepositoryCommand
{
    protected function configure()
    {
        $this->setName('auth:update-role-permissions')
            ->setDescription('Updates Roles and Permissions according to a matrix specified in the application. Useful the application does not allow users to customise permissions.');

        parent::configure();
    }

    protected function executeWithConnection(InputInterface $input, OutputInterface $output)
    {
        AuthenticationWithRolesModule::updateRolePermissions();
        $output->writeln('Role Permissions Updated');
    }
}
