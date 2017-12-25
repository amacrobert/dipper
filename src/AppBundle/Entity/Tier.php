<?php

namespace AppBundle\Entity;

class Tier implements \JsonSerializable {

    private $id;
    private $sequence;
    private $spend;
    private $bid_spread;
    private $ask_spread;
    private $lag_limit;
    private $buy_max_ppo;
    private $sell_min_ppo;
    private $active = false;

    public function __toString() {
        return $this->getId() ? 'Tier ' . $this->getSequence() : 'New Tier';
    }

    public function jsonSerialize() {
        return [
            'id' => $this->getId(),
            'sequence' => $this->getSequence(),
            'spend' => $this->getSpend(),
            'buy_max_ppo' => $this->getBuyMaxPPO(),
            'active' => $this->isActive(),
        ];
    }

    public function getId() {
        return $this->id;
    }

    public function setSequence($sequence) {
        $this->sequence = $sequence;
        return $this;
    }

    public function getSequence() {
        return $this->sequence;
    }

    public function setSpend($spend) {
        $this->spend = $spend;
        return $this;
    }

    public function getSpend() {
        return $this->spend;
    }

    public function setBidSpread($bid_spread) {
        $this->bid_spread = $bid_spread;
        return $this;
    }

    public function getBidSpread() {
        return $this->bid_spread;
    }

    public function setAskSpread($ask_spread) {
        $this->ask_spread = $ask_spread;
        return $this;
    }

    public function getAskSpread() {
        return $this->ask_spread;
    }

    public function setLagLimit($lagLimit) {
        $this->lag_limit = $lagLimit;
        return $this;
    }

    public function getLagLimit() {
        return $this->lag_limit;
    }

    public function getBuyMaxPPO() {
        return $this->buy_max_ppo;
    }

    public function setBuyMaxPPO($ppo) {
        $this->buy_max_ppo = $ppo;
        return $this;
    }

    public function getSellMinPPO() {
        return $this->sell_min_ppo;
    }

    public function setSellMinPPO($ppo) {
        $this->sell_min_ppo = $ppo;
        return $this;
    }

    public function isActive() {
        return (bool)$this->active;
    }

    public function setActive($active) {
        $this->active = $active;
        return $this;
    }
}
