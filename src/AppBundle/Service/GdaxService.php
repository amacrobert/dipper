<?php

namespace AppBundle\Service;

use GuzzleHttp\Client;
use AppBundle\Entity\GdaxOrder as Order;

class GdaxService {

    const BASE_URL = 'https://api-public.sandbox.gdax.com'; // sandbox
    //const BASE_URL = 'https://api.gdax.com'; // real site

    private $config;
    private $http;
    private $em;

    public function __construct($config, Client $http, $em) {
        $this->config = $config;
        $this->http = $http;
        $this->em = $em;
    }

    public function getAccounts() {
        return $this->callGdax('/accounts');
    }

    public function getOrders() {
        return $this->callGdax('/orders');
    }

    public function getOrder($order_id) {
        return $this->callGdax('/orders/' . $order_id);
    }

    public function getFills() {
        return $this->callGdax('/fills');
    }

    public function getFill($order_id) {
        return $this->callGdax('/fills/' . $order_id);
    }

    public function getPosition() {
        return $this->callGdax('/position');
    }

    public function getProducts() {
        return $this->callGdax('/products');
    }

    public function getProduct($product) {
        return $this->callGdax('/products/' . $product);
    }

    public function getProductTicker($product) {
        return $this->callGdax('/products/' . $product . '/ticker');
    }

    public function postLimitOrder($product, $coin_price, $coin_size) {
        $body = [
            'type'       => 'limit',
            'side'       => 'buy',
            'product_id' => $product,
            'price'      => $coin_price,
            'size'       => $coin_size,
        ];

        return $this->callGdax('/orders', 'POST', $body);
    }

    private function callGdax($request_path, $method = 'GET', $body = []) {
        $auth_headers = $this->getAuthHeaders($request_path, $method, $body);
        $response = $this->http->request($method, self::BASE_URL . $request_path, ['headers' => $auth_headers, 'json' => $body]);

        return json_decode($response->getBody());
    }

    private function getAuthHeaders($request_path = '', $method = 'GET', $body = '') {
        $timestamp = time();

        return [
            'CB-ACCESS-KEY' => $this->config['key'],
            'CB-ACCESS-SIGN' => $this->signature($request_path, $timestamp, $method, $body),
            'CB-ACCESS-TIMESTAMP' => $timestamp,
            'CB-ACCESS-PASSPHRASE' => $this->config['passphrase'],
        ];
    }

    private function signature($request_path = '', $timestamp = false, $method = 'GET', $body = '') {

        $body = is_array($body) ? json_encode($body) : $body;
        $timestamp = $timestamp ? $timestamp : time();

        $what = $timestamp . $method . $request_path . $body;

        return base64_encode(hash_hmac("sha256", $what, base64_decode($this->config['secret']), true));
    }

    public function orderFromGdax($gdax_order) {
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

        $this->em->flush();
    }
}
