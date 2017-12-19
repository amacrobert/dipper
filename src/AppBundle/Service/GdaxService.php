<?php

namespace AppBundle\Service;

use GuzzleHttp\Client;

class GdaxService {

    //const BASE_URL = 'https://api-public.sandbox.gdax.com'; // sandbox
    const BASE_URL = 'https://api.gdax.com'; // real site

    private $config;
    private $http;

    public function __construct($config, Client $http) {
        $this->config = $config;
        $this->http = $http;
    }

    public function getBook($product, $level = 1) {
        return $this->callGdax('/products/' . $product . '/book?level=' . $level);
    }

    public function getAccounts() {
        return $this->callGdax('/accounts');
    }

    public function getOrders($params = []) {
        $query = http_build_query($params);
        return $this->callGdax('/orders?' . $query);
    }

    public function getOrder($order_id) {
        return $this->callGdax('/orders/' . $order_id);
    }

    public function deleteOrder($order_id) {
        return $this->callGdax('/orders/' . $order_id, 'DELETE');
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

    public function postLimitOrder($product, $side, $coin_price, $coin_size) {
        $body = [
            'type'       => 'limit',
            'side'       => $side,
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
}
