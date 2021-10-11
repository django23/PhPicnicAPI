# PhPicnic-API

[![By me a book](https://camo.githubusercontent.com/cd005dca0ef55d7725912ec03a936d3a7c8de5b5/68747470733a2f2f696d672e736869656c64732e696f2f62616467652f6275792532306d6525323061253230636f666665652d646f6e6174652d79656c6c6f772e737667)](https://www.buymeacoffee.com/djangoboy)

Inspired and copied from the Python version: https://github.com/MikeBrink/python-picnic-api

Unofficial PHP wrapper for the Picnic API. While not all API methods have been implemented yet, you'll find most of what you need to build a working application are available.

This library is not affiliated with Picnic and retrieves data from the endpoints of the mobile application. Use at your own risk.

## Get started

The easiest way to install is directly from composer:
```shell
composer install django23/PhPicnicAPI
```

## Usage

```php
<?php

// Composer
require 'vendor/autoload.php';

// Load Env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Picnic
$picninc = new \PhPicnic\Client($_ENV['username'], $_ENV['password'], $_ENV['country_code']);
// or
$picninc = new \PhPicnic\Client('probably_your@email.here', 'password', 'NL');

```

### Searching for a product
```php
echo $picnic->search('coffee');
// [{'type': 'CATEGORY', 'id': 'coffee', 'links': [{'type': 'SEARCH', 'href': 'https://storefront-prod.nl.picnicinternational.com/api/15/search?search_term=coffee'}], 'name': 'coffee', 'items': [{'type': 'SINGLE_ARTICLE', 'id': '10511523', 'decorators': [{'type': 'UNIT_QUANTITY', 'unit_quantity_text': '500 gram'}], 'name': 'Lavazza espresso koffiebonen', 'display_price': 599, 'price': 599, 'image_id': 'd3fb2888fc41514bc06dfd6b52f8622cc222d017d2651501f227a537915fcc4f', 'max_count': 50, 'unit_quantity': '500 gram', 'unit_quantity_sub': '€11.98/kg', 'tags': []}, ...
``` 

### Check cart
```php
echo $picnic->getCart();
// {'type': 'ORDER', 'id': 'shopping_cart', 'items': [], 'delivery_slots': [...
``` 

### Manipulating your cart
All of these methods will return the shopping cart.

```php
// adding 2 'Lavazza espresso koffiebonen' to cart
echo $picnic->addProduct('10511523', 2);

// removing 1 'Lavazza espresso koffiebonen' from cart
echo $picnic->removeProduct('10511523');

// clearing the cart
echo $picnic->clearCart();
```

### See upcoming deliveries
```php
echo $picnic->getCurrentDeliveries();
// []
```

### See available delivery slots
```php
echo $picnic->getDeliverySlots();
```