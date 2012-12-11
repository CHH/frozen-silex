<?php

namespace FrozenSilex\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FreezeCommand extends Command
{
    protected function configure()
    {
        $this->setName('freeze')
            ->setDescription('Freeze the Silex Application')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file, must return \\FrozenSilex\\Application', 'frozen_silex_config.php')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Static files are written to this directory', '_site');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = require($input->getOption('config'));
        $app->freeze();
    }
}

