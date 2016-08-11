<?php

namespace Rhubarb\Scaffolds\AuthenticationWithRoles\Commands;

use Rhubarb\Scaffolds\AuthenticationWithRoles\AuthenticationWithRolesModule;
use Rhubarb\Stem\Custard\RequiresRepositoryCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class UpdateRolePermissionsCommand extends RequiresRepositoryCommand
{
    protected function configure()
    {
        $this->setName('auth:update-role-permissions')
            ->setDescription('Updates Roles and Permissions according to a definitions specified in the application. Useful if the application does not allow users to customise role permissions.')
            ->addArgument('force');

        parent::configure();
    }

    protected function executeWithConnection(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $force = $input->getArgument('force') === '-f';

        $verify = new Question(
            'Warning: New roles may be created, and all permissions associated with a role may be updated/removed if not specified in RolePermissionDefinitions. Are you sure you want to proceed? [y/n] '
        );
        $verify->setValidator(function ($value) use ($output) {
            if (stripos($value, 'y') !== 0) {
                $output->writeln('Aborted');
                return false;
            }
            return true;
        });
        if ($force || $helper->ask($input, $output, $verify)) {
            AuthenticationWithRolesModule::updateRolePermissions();
            $output->writeln('Role Permissions Updated');
        }
    }
}
