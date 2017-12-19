<?php

namespace AppBundle\Entity;

class Tier {
    
    private $id;
    private $sequence;
    private $spend;
    private $bid_spread;
    private $ask_spread;
    private $lag_limit;

    public function __toString() {
        return $this->getId() ? 'Tier ' . $this->getSequence() : 'New Tier';
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
}
