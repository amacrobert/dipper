<?php

namespace AppBundle\Entity;

class GdaxOrder {

    private $id;
    // From GDAX API
    private $gdax_id;
    private $price;
    private $size;
    private $product_id;
    private $side;
    private $stp;
    private $type;
    private $time_in_force;
    private $post_only;
    private $created_at;
    private $fill_fees;
    private $filled_size;
    private $executed_value;
    private $status;
    private $settled;

    public function getId() {
        return $this->id;
    }

    public function setGdaxId($gdaxId) {
        $this->gdax_id = $gdaxId;
        return $this;
    }

    public function getGdaxId() {
        return $this->gdax_id;
    }

    public function setPrice($price) {
        $this->price = $price;
        return $this;
    }

    public function getPrice() {
        return $this->price;
    }

    public function setSize($size) {
        $this->size = $size;
        return $this;
    }

    public function getSize() {
        return $this->size;
    }

    public function setProductId($product_id) {
        $this->product_id = $product_id;
        return $this;
    }

    public function getProductId() {
        return $this->product_id;
    }

    public function setSide($side) {
        $this->side = $side;
        return $this;
    }

    public function getSide() {
        return $this->side;
    }

    public function setStp($stp) {
        $this->stp = $stp;
        return $this;
    }

    public function getStp() {
        return $this->stp;
    }

    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    public function getType() {
        return $this->type;
    }

    public function setTimeInForce($time_in_force) {
        $this->time_in_force = $time_in_force;
        return $this;
    }

    public function getTimeInForce() {
        return $this->time_in_force;
    }

    public function setPostOnly($post_only) {
        $this->post_only = $post_only;
        return $this;
    }

    public function getPostOnly() {
        return $this->post_only;
    }

    public function setCreatedAt($created_at) {
        $this->created_at = $created_at;
        return $this;
    }

    public function getCreatedAt() {
        return $this->created_at;
    }

    public function setFillFees($fill_fees) {
        $this->fill_fees = $fill_fees;
        return $this;
    }

    public function getFillFees() {
        return $this->fill_fees;
    }

    public function setFilledSize($filled_size) {
        $this->filled_size = $filled_size;
        return $this;
    }

    public function getFilledSize() {
        return $this->filled_size;
    }

    public function setExecutedValue($executed_value) {
        $this->executed_value = $executed_value;
        return $this;
    }

    public function getExecutedValue() {
        return $this->executed_value;
    }

    public function setStatus($status) {
        $this->status = $status;
        return $this;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setSettled($settled) {
        $this->settled = $settled;
        return $this;
    }

    public function getSettled() {
        return $this->settled;
    }
}
