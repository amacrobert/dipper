# Dipper

Dipper is a customizable trading bot for the GDAX exchange. It is compatible with the following products:

* BTC-USD
* ETH-USD
* LTC-USD

### How it works

At its core, dipper operates by placing limit orders. When a buy order is filled, it creates a corresponding sell order. When the sell order is filled, you just made money.

Each buy/sell pair is an "Order Pair." Every Order Pair has a Tier. The Tier determines how low below the market ask to bid when placing buy limit orders, how high above the market bid to ask when placing sell limit orders, and the buy size in USD.

There can only be one open Order Pair per Tier at any time. When an Order Pair's sell order is executed, the Order Pair closes and a new one opens up (with a new bid) for that Tier.

Additionally, Tiers can optionally have a lag limit. The lag limit applies to Order Pairs that have an open buy. If the market ask exceeds the pair's open bid by more than the lag limit, the order is canceled. This frees up the Tier for another bid to be placed closer to the market ask.

### How to use
1. Run `composer install` from the root
2. On gdax.com, create an API key with 'view' and 'trade' grants.
3. Enter the generated API passphrase, key, and secret in app/config/parameters.yml
4. Configure a MySQL database in parameters.yml, and run `bin/console doctrine:schema:update --force`
5. To create trading tiers, run `bin/console server:start` and go to /admin for a configurable interface. Your GDAX USD balance must be at least equal to the total spends of all tiers.
6. Run `bin/console dipper:work`

### About

Created by Andrew MacRobert
