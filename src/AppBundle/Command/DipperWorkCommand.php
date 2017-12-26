<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;
use AppBundle\Service\DipperCoreService;
use AppBundle\Service\GdaxService;

class DipperWorkCommand extends ContainerAwareCommand {

    protected function configure() {
        $this
            ->setName('dipper:work')
            ->setDescription('Run one dipper cycle')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $dipper = $this->getContainer()->get(DipperCoreService::class);

        $this->counter = 0;
        $this->total_earnings = 0;
        $throbber = new ProgressBar($output);
        $throbber->start();

        $t_start = $t_end = 0;

        while (1) {

            $t_exec = ($t_end - $t_start) * 1000000;
            $t_delay = (1000000 / GdaxService::RATE_LIMIT) - $t_exec;

            if ($t_delay > 0) {
                usleep($t_delay);
            }

            $t_start = microtime(true);

            try {
                $r = $dipper->cycle($throbber->getProgress());
                $this->total_earnings += $r->earnings;
                $this->output($r, $output, $throbber);
            }
            catch (\GuzzleHttp\Exception\BadResponseException $e) {
                $response = json_decode($e->getResponse()->getBody());
                $status_code = $e->getResponse()->getStatusCode();
                $output->write(' <error>' . $status_code . '</error>');

                switch ($status_code) {
                    // Rate limit exceeded - cool down
                    case 429:
                        sleep(5);
                        break;

                    case 400:
                        $throbber->clear();
                        $output->writeln($e->getMessage());
                        $throbber->display();

                    case 443:
                    case 500:
                    case 504:
                        sleep(10);
                        break;

                    default:
                        throw $e;
                }
            }

            $throbber->advance();
            $output->write(' PPO: ' . round($r->ppo, 4) . '%');

            $t_end = microtime(true);
        }
    }

    private function output($r, $output, $throbber) {
        if ($r->buys + $r->swaps + $r->sales + $r->canceled + count($r->errors) > 0) {

            $throbber->clear();

            if (!($this->counter % 20)) {
                $output->writeln('BUYS | SWAPS | SALES | LAGOUTS | CANCELED | EARNINGS  | SESSION TOTAL');
            }

            $output->writeln(
                $this->colStr($r->buys, 4) .
                $this->colStr($r->swaps, 8) .
                $this->colStr($r->sales, 8) .
                $this->colStr($r->lagouts, 10) .
                $this->colStr($r->canceled, 11) .
                '   ' . str_pad($r->earnings, 9, $r->earnings > 0 ? '0' : ' ', STR_PAD_RIGHT) .
                '   ' . $this->total_earnings
            );

            foreach ($r->errors as $error) {
                $output->writeln(' ! ' . $error);
            }

            $throbber->display();
            $this->counter++;
        }
    }

    private function colStr($str, $width) {
        $str = $str ?: '-';
        return str_pad($str, $width, ' ', STR_PAD_LEFT);
    }

}
