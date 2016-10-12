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
            ->addOption('f', null, null, 'Forces update - does not prompt user');

        parent::configure();
    }

    protected function executeWithConnection(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $force = $input->getOption('f');

        if (!$force) {
            $output->writeln('<comment>Warning: New roles may be created, and all permissions associated with a role may be updated/removed if not specified in RolePermissionDefinitions.</comment>');
        }
        $verify = new Question(
            '<question>Are you sure you want to proceed? [y/n] </question>'
        );
        $verify->setValidator(function ($value) use ($output) {
            if (stripos($value, 'y') !== 0) {
                $output->writeln('<error>Aborted</error>');
                return false;
            }
            return true;
        });
        if ($force || $helper->ask($input, $output, $verify)) {
            if (!$force) {
                $output->writeln('<comment>If you would like to suppress user verification in the future, use the --f option</comment>');
            }
            AuthenticationWithRolesModule::updateRolePermissions();
            $output->writeln('<info>Role Permissions Updated</info>');
        }
    }
}
