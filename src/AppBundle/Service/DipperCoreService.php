<?php

namespace AppBundle\Service;

use AppBundle\Entity\GdaxOrder as Order;
use AppBundle\Entity\OrderPair;

use Symfony\Component\Console\Helper\ProgressBar;

class DipperCoreService {

    private $gdax;
    private $em;

    public function __construct(\AppBundle\Service\GdaxService $gdax, $em) {
        $this->gdax = $gdax;
        $this->em = $em;
        $this->product = 'LTC-USD';
    }

    public function cycle() {

        $stats = (object)[
            'buy_orders_created' => 0,
            'buy_orders_closed' => 0,
            'sell_orders_created' => 0,
            'sell_orders_closed' => 0,
            'orders_cancelled' => 0,
            'earnings' => 0,
            'errors' => [],
        ];

        $open_order_pairs = $this->getOpenOrderPairs();

        $book = $this->gdax->getBook($this->product);
        $market_bid = $book->bids[0][0] ?? 100;
        $market_ask = $book->asks[0][0] ?? 100.01;

        // For testing only
        //$market_bid = 20;
        //$market_ask = 20.01;

        foreach ($open_order_pairs as $order_pair) {

            $buy_order = $order_pair->getBuyOrder();
            $sell_order = $order_pair->getSellOrder();

            // Buy order hasn't been placed yet - create it
            if (!$buy_order) {
                $spend = $order_pair->getTier()->getSpend();
                $spread = $order_pair->getTier()->getSpread();
                $coin_price = $market_ask - $spread;
                $perfect_size = $spend / $coin_price;
                $coin_size = floor($perfect_size * 1e7) / 1e7;

                try {
                    $buy_order = $this->buy(round($coin_price, 2), $coin_size);                
                    $order_pair
                        ->setBuyOrder($buy_order)
                        ->setStatus('open')
                    ;

                    $stats->buy_orders_created++;
                }
                catch (\Exception $e) {
                    $stats->errors[] = $e->getMessage();
                }
            }

            // Buy order exists
            else {

                // No sell order exists - check status of paired buy order
                if (!$sell_order) {

                    try {
                        $gdax_order_id = $buy_order->getGdaxId();
                        $gdax_order = $this->gdax->getOrder($gdax_order_id);
                        $buy_order = $this->makeOrderFromGdax($gdax_order);                        
                    }
                    catch (\Exception $e) {
                        $order_pair
                            ->setStatus('cancelled')
                            ->setCompletedAt(new \DateTime)
                            ->setActive(false)
                        ;
                        $stats->orders_cancelled++;
                        $stats->errors[] = $e->getMessage();
                        continue;
                    }

                    // Buy order has been settled - create corresponding sell order
                    if ($buy_order->getSettled()) {
                        $stats->buy_orders_closed++;

                        $spread = $order_pair->getTier()->getSpread();
                        $spend = $order_pair->getTier()->getSpend();

                        $coin_price = $buy_order->getPrice() + ($spread * 2);

                        // Recoup any fees from the buy
                        $coin_price += ($buy_order->getPrice() / $buy_order->getExecutedValue()) * $buy_order->getFillFees();

                        // Always ask above the market bid to ensure limit order
                        if ($coin_price <= $market_bid) {
                            $coin_price = $market_bid + .10;
                        }

                        // Sell the buy volume for a higher amount
                        $coin_size = $buy_order->getFilledSize();

                        $sell_order = $this->sell(round($coin_price, 2), $coin_size);
                        $order_pair->setSellOrder($sell_order);
                        $stats->sell_orders_created++;                            
                    }

                    // If buy order lags the market value beyond treshhold, cancel it
                    else {
                        $lag_limit = $order_pair->getTier()->getLagLimit();

                        if ($lag_limit && $lag_limit < $market_ask - $buy_order->getPrice()) {
                            $order_pair
                                ->setStatus('lagged-out')
                                ->setCompletedAt(new \DateTime)
                                ->setActive(false)
                            ;

                            $this->gdax->deleteOrder($buy_order->getGdaxId());

                            $stats->orders_cancelled++;
                        }
                    }
                }

                // Sell order exists - check if it's settled
                else {
                    try {
                        $gdax_order_id = $sell_order->getGdaxId();
                        $gdax_order = $this->gdax->getOrder($gdax_order_id);
                        $sell_order = $this->makeOrderFromGdax($gdax_order);

                        // Sell order is completed
                        if ($sell_order->getSettled()) {
                            $order_pair
                                ->setStatus('completed')
                                ->setCompletedAt(new \DateTime)
                                ->setActive(false)
                            ;
                            $stats->sell_orders_closed++;
                            $stats->earnings += round($sell_order->getExecutedValue() - $buy_order->getExecutedValue(), 7);
                        }
                    }
                    catch (\Exception $e) {
                        $order_pair
                            ->setStatus('cancelled')
                            ->setCompletedAt(new \DateTime)
                            ->setActive(false)
                        ;
                        $stats->orders_cancelled++;
                        $stats->errors[] = $e->getMessage();
                    }
                }
            }
        }

        $this->em->flush();

        return $stats;
    }

    public function buy($price, $size) {
        $gdax_order = $this->gdax->postLimitOrder($this->product, 'buy', $price, $size);
        $order = $this->makeOrderFromGdax($gdax_order);

        return $order;
    }

    public function sell($price, $size) {
        $gdax_order = $this->gdax->postLimitOrder($this->product, 'sell', $price, $size);
        $order = $this->makeOrderFromGdax($gdax_order);

        return $order;
    }

    public function getOpenOrderPairs() {
        $dql = "
            SELECT tier, pair
            FROM AppBundle\Entity\Tier tier
            LEFT JOIN AppBundle\Entity\OrderPair pair WITH pair.tier = tier AND pair.active = true
        ";
 
        $query = $this->em->createQuery($dql);
        $results = $query->getResult();

        $tier_pair_sets = [];

        for ($i = 0; $i < count($results); $i+=2) {
            $tier_pair_sets[] = [
                'tier' => $results[$i],
                'pair' => $results[$i+1],
            ];
        }

        $order_pairs = [];

        foreach ($tier_pair_sets as $tier_pair_set) {
            if ($tier_pair_set['pair']) {
                $order_pair = $tier_pair_set['pair'];
            }
            else {
                $order_pair = new OrderPair;
                $order_pair
                    ->setTier($tier_pair_set['tier'])
                    ->setActive(true)
                    ->setStatus('pending-creation')
                ;
                $this->em->persist($order_pair);
            }

            $order_pairs[] = $order_pair;
        }

        return $order_pairs;
    }

    public function makeOrderFromGdax($gdax_order) {
        $order = $this->em->getRepository(Order::class)->findOneBy(['gdax_id' => $gdax_order->id]);

        if (!$order) {
            $order = new Order;
            $order->setGdaxId($gdax_order->id);
            $this->em->persist($order);
        }

        $order
            ->setPrice($gdax_order->price)
            ->setSize($gdax_order->size)
            ->setProductId($gdax_order->product_id)
            ->setSide($gdax_order->side)
            ->setStp($gdax_order->stp)
            ->setType($gdax_order->type)
            ->setTimeInForce($gdax_order->time_in_force)
            ->setPostOnly($gdax_order->post_only)
            ->setCreatedAt(new \DateTime($gdax_order->created_at))
            ->setFillFees($gdax_order->fill_fees)
            ->setFilledSize($gdax_order->filled_size)
            ->setExecutedValue($gdax_order->executed_value)
            ->setStatus($gdax_order->status)
            ->setSettled($gdax_order->settled)
        ;

        return $order;
    }




    // update all orders in the datbase with their corresponding values from gdax
    public function runUpdateOrders($o) {

        $orders = $this->em->getRepository(Order::class)->findAll();
        $progress = new ProgressBar($o, count($orders));
        $progress->start();
        $stats = (object)[
            'orders_deleted' => 0,
        ];

        foreach ($orders as $order) {
            if ($order->getStatus() == 'deleted') {
                continue;
            }

            try {
                $gdax_order = $this->gdax->getOrder($order->getGdaxId());
                $order = $this->makeOrderFromGdax($gdax_order);
            }
            catch (\Exception $e) {
                $order->setStatus('deleted');
            }
            $progress->advance();
        }

        $progress->finish();
        $o->write(PHP_EOL);

        $this->em->flush();

        $o->writeln(count($orders) . ' orders checked. ' . $stats->orders_deleted . ' orders deleted.');
    }
}
