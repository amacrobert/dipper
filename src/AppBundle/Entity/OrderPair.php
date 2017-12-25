<?php

namespace AppBundle\Entity;

class OrderPair implements \JsonSerializable {

    private $id;
    private $tier;
    private $active;
    private $status;
    private $created_at;
    private $completed_at;
    private $buy_order;
    private $sell_order;

    public function getId() {
        return $this->id;
    }

    public function jsonSerialize() {
        return [
            'id' => $this->getId(),
            'tier_id' => $this->getTier()->getId(),
            'status' => $this->getStatus(),
            'created_at' => $this->getCreatedAt(),
            'completed_at' => $this->getCompletedAt(),
            'active' => $this->isActive(),
        ];
    }

    public function setTier($tier) {
        $this->tier = $tier;
        return $this;
    }

    public function getTier() {
        return $this->tier;
    }

    public function setActive($active) {
        $this->active = $active;
        return $this;
    }

    public function isActive() {
        return $this->active;
    }

    public function setStatus($status) {
        $this->status = $status;
        return $this;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setCreatedAt($createdAt) {
        $this->created_at = $createdAt;
        return $this;
    }

    public function getCreatedAt() {
        return $this->created_at;
    }

    public function setCreatedAtToNow() {
        return $this->setCreatedAt(new \DateTime);
    }

    public function setCompletedAt($completedAt) {
        $this->completed_at = $completedAt;
        return $this;
    }

    public function setCompletedAtToNow() {
        return $this->setCompletedAt(new \DateTime);
    }

    public function getCompletedAt() {
        return $this->completed_at;
    }

    public function setBuyOrder(\AppBundle\Entity\GdaxOrder $buyOrder = null) {
        $this->buy_order = $buyOrder;
        return $this;
    }

    public function getBuyOrder() {
        return $this->buy_order;
    }

    public function setSellOrder(\AppBundle\Entity\GdaxOrder $sellOrder = null) {
        $this->sell_order = $sellOrder;
        return $this;
    }

    public function getSellOrder() {
        return $this->sell_order;
    }
}
