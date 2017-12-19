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

        // Turn off sql logger to save memory
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $stats = (object)[
            'buys' => 0,
            'swaps' => 0,
            'sales' => 0,
            'lagouts' => 0,
            'canceled' => 0,
            'earnings' => 0,
            'errors' => [],
        ];


        $open_order_pairs = $this->getOpenOrderPairs();

        $book = $this->gdax->getBook($this->product);
        $market_bid = $book->bids[0][0] ?? 100;
        $market_ask = $book->asks[0][0] ?? 100.01;
        // For testing only
        // $market_bid = 200;
        // $market_ask = 200.01;

        $orders_by_gdax_id = $this->ordersFromGdax();

        foreach ($open_order_pairs as $order_pair) {

            $buy_order = $order_pair->getBuyOrder();
            $sell_order = $order_pair->getSellOrder();

            // Buy order hasn't been placed yet - create it
            if (!$buy_order) {
                $spend = $order_pair->getTier()->getSpend();
                $spread = $order_pair->getTier()->getBidSpread();
                $coin_price = $market_ask - $spread;
                $perfect_size = $spend / $coin_price;
                $coin_size = floor($perfect_size * 1e7) / 1e7;

                try {
                    $buy_order = $this->buy(round($coin_price, 2), $coin_size);                
                    $order_pair
                        ->setBuyOrder($buy_order)
                        ->setStatus('open')
                    ;

                    $stats->buys++;
                }
                catch (\Exception $e) {
                    $stats->errors[] = $e->getMessage();
                }
            }

            // Buy order exists
            else {

                // No sell order exists - check status of paired buy order
                if (!$sell_order) {

                    $gdax_order_id = $buy_order->getGdaxId();
                    $buy_order = $orders_by_gdax_id[$gdax_order_id] ?? null;

                    // Buy order wasn't in the order index - check for it specifically from GDAX
                    if (!$buy_order) {
                        try {
                            $gdax_order = $this->gdax->getOrder($gdax_order_id);
                            $buy_order = $this->makeOrderFromGdax($gdax_order);
                        }
                        catch (\GuzzleHttp\Exception\ClientException $e) {
                            $response = json_decode($e->getResponse()->getBody());

                            // Buy order was canceled before any of it was fulfilled
                            if ($response->message == 'NotFound') {
                                $order_pair
                                    ->setStatus('canceled')
                                    ->setCompletedAtToNow()
                                    ->setActive(false)
                                ;
                                $order_pair->getBuyOrder()->setStatus('canceled');
                                $stats->canceled++;

                                continue;
                            }
                            else {
                                throw $e;
                            }
                        }
                        catch (\Exception $e) {
                            $stats->errors[] = $e->getMessage();
                        }
                    }

                    // Buy order has been settled - create corresponding sell order
                    if ($buy_order->getSettled()) {

                        $spend = $order_pair->getTier()->getSpend();
                        $spread = $order_pair->getTier()->getAskSpread();
                        $coin_price = $buy_order->getPrice() + $spread;

                        // If the buy incurred a fee, recoup it on the sell
                        $coin_price += ($buy_order->getPrice() / $buy_order->getExecutedValue()) * $buy_order->getFillFees();

                        // Always ask above the market bid to ensure limit order
                        if ($coin_price <= $market_bid) {
                            $coin_price = $market_bid + .10;
                        }

                        // Flip the buy volume for a profit
                        $coin_size = $buy_order->getFilledSize();

                        $sell_order = $this->sell(round($coin_price, 2), $coin_size);
                        $order_pair->setSellOrder($sell_order);
                        $stats->swaps++;
                    }

                    // If buy order lags the market ask beyond the lag limit, cancel the order so it
                    // can be re-issued at a high bid next cycle
                    else {
                        $lag_limit = $order_pair->getTier()->getLagLimit();

                        if ($lag_limit && $lag_limit < $market_ask - $buy_order->getPrice()) {
                            $order_pair
                                ->setStatus('lagged-out')
                                ->setCompletedAtToNow()
                                ->setActive(false)
                            ;

                            $this->gdax->deleteOrder($buy_order->getGdaxId());

                            $stats->lagouts++;
                        }
                    }
                }

                // Sell order exists - check if it's settled
                else {
                    $gdax_order_id = $sell_order->getGdaxId();
                    $sell_order = $orders_by_gdax_id[$gdax_order_id] ?? null;

                    // Sell order wasn't in index - check GDAX for it
                    if (!$sell_order) {
                        try {
                            $gdax_order = $this->gdax->getOrder($gdax_order_id);
                            $sell_order = $this->makeOrderFromGdax($gdax_order);
                        }
                        catch (\GuzzleHttp\Exception\ClientException $e) {
                            $response = json_decode($e->getResponse()->getBody());

                            // Sell order was canceled - remove it from the OrderPair so it can be
                            // re-issued next cycle
                            if ($response->message == 'NotFound') {
                                $order_pair->getSellOrder()->setStatus('canceled');
                                $order_pair->setSellOrder(null);
                                $stats->canceled++;

                                continue;
                            }
                            else {
                                throw $e;
                            }
                        }
                        catch (\Exception $e) {
                            $stats->errors[] = $e->getMessage();
                        }
                    }

                    // Sell order is completed
                    if ($sell_order->getSettled()) {
                        $order_pair
                            ->setStatus('completed')
                            ->setCompletedAtToNow()
                            ->setActive(false)
                        ;
                        $stats->sales++;
                        $stats->earnings += round($sell_order->getExecutedValue() - $buy_order->getExecutedValue(), 7);
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

    public function makeOrderFromGdax($gdax_order, $order = null) {
        if (!$order) {
            $order = $this->em->getRepository(Order::class)->findOneBy(['gdax_id' => $gdax_order->id]);

            if (!$order) {
                $order = new Order;
                $order->setGdaxId($gdax_order->id);
                $this->em->persist($order);
            }
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
            ->setDoneAt(isset($gdax_order->done_at) ? new \DateTime($gdax_order->done_at) : null)
            ->setDoneReason($gdax_order->done_reason ?? null)
            ->setFillFees($gdax_order->fill_fees)
            ->setFilledSize($gdax_order->filled_size)
            ->setExecutedValue($gdax_order->executed_value)
            ->setStatus($gdax_order->status)
            ->setSettled($gdax_order->settled)
        ;

        return $order;
    }

    /**
     * Get the last 100 orders from GDAX and update the local orders.
     * Return local orders indexed by the GDAX order id.
     */
    public function ordersFromGdax() {
        $gdax_orders = $this->gdax->getOrders(['status' => 'all', 'product_id' => $this->product]);
        $orders_by_gdax_id = [];

        foreach ($gdax_orders as $gdax_order) {
            $order = $this->makeOrderFromGdax($gdax_order);
            $orders_by_gdax_id[$order->getGdaxId()] = $order;
        }

        return $orders_by_gdax_id;
    }

    // Sync database orders with gdax orders
    public function runUpdateOrders($o) {

        $stats = (object)[
            'orders_deleted' => 0,
            'orders_created' => 0,
        ];

        // Update all existing orders in the database from gdax
        $o->writeln('Syncing existing database orders from gdax');
        $orders = $this->em->getRepository(Order::class)->findAll();
        $progress = new ProgressBar($o, count($orders));
        $progress->start();

        foreach ($orders as $order) {
            if ($order->getStatus() == 'canceled') {
                continue;
            }

            try {
                $gdax_order = $this->gdax->getOrder($order->getGdaxId());
                $order = $this->makeOrderFromGdax($gdax_order, $order);
            }
            catch (\GuzzleHttp\Exception\ClientException $e) {
                $response = json_decode($e->getResponse()->getBody());

                if ($response->message == 'NotFound') {
                    $order->setStatus('canceled');
                    $stats->orders_deleted++;
                }
                else {
                    throw $e;
                }
            }
            $progress->advance();
        }

        $progress->finish();
        $o->write(PHP_EOL);

        $o->writeln('Checking gdax for missing orders');
        $gdax_orders = $this->gdax->getOrders(['status' => 'all', 'product_id' => $this->product]);
        $progress = new ProgressBar($o, count($gdax_orders));
        $progress->start();

        foreach ($gdax_orders as $gdax_order) {
            $order = $this->makeOrderFromGdax($gdax_order);
            if (!$order->getId()) {
                $stats->orders_created++;
            }

            $progress->advance();
        }

        $progress->finish();
        $o->write(PHP_EOL);

        $this->em->flush();

        $o->writeln(' - ' . count($gdax_orders) . ' orders checked');
        $o->writeln(' - ' . $stats->orders_deleted . ' orders deleted');
        $o->writeln(' - ' . $stats->orders_created . ' orders created');
    }
}
