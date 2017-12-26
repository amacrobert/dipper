<?php

namespace AppBundle\Service;

use GuzzleHttp\Client;

class GdaxService {

    //const BASE_URL = 'https://api-public.sandbox.gdax.com'; // sandbox
    const BASE_URL = 'https://api.gdax.com'; // real site
    const RATE_LIMIT = 1; // Allowable API calls per second

    private $config;
    private $http;
    private $candles = null;

    public function __construct($config, Client $http) {
        $this->config = $config;
        $this->http = $http;
    }

    public function ppo($product) {
        $ema12 = $this->ema12($product);
        $ema26 = $this->ema26($product);

        return 100 * ($ema12 - $ema26) / $ema26;
    }

    public function clearCandles() {
        $this->candles = null;
        return $this;
    }

    public function ema12($product) {
        return $this->ema($this->getCloses($product), 12);
    }

    public function ema26($product) {
        return $this->ema($this->getCloses($product), 26);
    }

    // exponential moving average
    public function ema($values, $periods) {
        $values = array_reverse(array_slice($values, 0, $periods));
        $periods = count($values);
        $last_ema = $values[0];

        $k = 2 / ($periods + 1);
        for ($i = 0; $i < $periods; $i++) {
            $last_ema = $last_ema + ($k * ($values[$i] - $last_ema));
        }

        return $last_ema;
    }

    // simple moving average
    public function sma($values, $periods) {
        $values = array_slice($values, 0, $periods);
        $periods = count($values);

        return array_sum($values) / $periods;
    }

    public function getCloses($product) {
        $closes = array_map(function($candle) {
            return $candle[4];
        }, $this->getCandles($product));

        return $closes;
    }

    public function getCandles($product) {
        if (!$this->candles) {
            $this->candles = $this->callGdax('/products/' . $product . '/candles');
        }
        return $this->candles;
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

    public function postOrder($body) {
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
