<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;
use AppBundle\Service\DipperCoreService;

class DipperWorkCommand extends ContainerAwareCommand {

    protected function configure() {
        $this
            ->setName('dipper:work')
            ->setDescription('Run one dipper cycle')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $dipper = $this->getContainer()->get(DipperCoreService::class);

        $counter = 0;
        $throbber = new ProgressBar($output);
        $throbber->start();

        while (1) {
            try {
                $r = $dipper->cycle();            
            }
            catch (\Exception $e) {
                $output->writeln($e->getMessage());
                sleep(2);
            }

            if ($r->buy_orders_created
                + $r->buy_orders_closed
                + $r->sell_orders_created
                + $r->sell_orders_closed
                + $r->orders_cancelled
                > 0) {

                $throbber->clear();

                if (!($counter % 10)) {
                    $output->writeln('BUYS OPENED | BUYS CLOSED | SELLS OPENED | SELLS CLOSED | CANCELLED | EARNINGS');
                }

                $output->writeln(
                    $this->colStr($r->buy_orders_created, 11) .
                    $this->colStr($r->buy_orders_closed, 14) .
                    $this->colStr($r->sell_orders_created, 15) .
                    $this->colStr($r->sell_orders_closed, 15) .
                    $this->colStr($r->orders_cancelled, 12) .
                    '   ' . $r->earnings
                );

                foreach ($r->errors as $error) {
                    $output->writeln(' ! ' . $error);
                }

                $counter++;

                $throbber->display();
            }

            $throbber->advance();
        }
    }

    private function colStr($str, $width) {
        $str = $str ?: ' ';
        return str_pad($str, $width, ' ', STR_PAD_LEFT);
    }
}
