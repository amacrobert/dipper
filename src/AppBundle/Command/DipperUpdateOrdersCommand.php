<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;
use AppBundle\Service\DipperCoreService;

class DipperUpdateOrdersCommand extends ContainerAwareCommand {

    protected function configure() {
        $this
            ->setName('dipper:update-orders')
            ->setDescription('Run one dipper cycle')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $dipper = $this->getContainer()->get(DipperCoreService::class);
        $dipper->runUpdateOrders($output);
    }
}
