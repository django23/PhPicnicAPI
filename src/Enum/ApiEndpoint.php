<?php

declare(strict_types=1);

namespace PhPicnic\Enum;

/**
 * Every Picnic API path this client calls, relative to the base URL. Cases with
 * "%s" placeholders take URL-encoded parameters through {@see path()}.
 */
enum ApiEndpoint: string
{
    case LOGIN = '/user/login';
    case TWO_FACTOR_GENERATE = '/user/2fa/generate';
    case TWO_FACTOR_VERIFY = '/user/2fa/verify';
    case USER = '/user';
    case SEARCH_PAGE_RESULTS = '/pages/search-page-results?search_term=%s';
    case CART = '/cart';
    case CART_ADD_PRODUCT = '/cart/add_product';
    case CART_ADD_PRODUCTS = '/cart/products/add';
    case CART_REMOVE_PRODUCT = '/cart/remove_product';
    case CART_CLEAR = '/cart/clear';
    case CART_SET_DELIVERY_SLOT = '/cart/set_delivery_slot';
    case CART_DELIVERY_SLOTS = '/cart/delivery_slots';
    case SHOPPING_LISTS = '/lists';
    case SHOPPING_LIST = '/lists/%s';
    case SHOPPING_LIST_SUBLIST = '/lists/%s?sublist=%s';
    case DELIVERY = '/deliveries/%s';
    case DELIVERY_SCENARIO = '/deliveries/%s/scenario';
    case DELIVERY_POSITION = '/deliveries/%s/position';
    case DELIVERIES_SUMMARY = '/deliveries/summary';

    /**
     * The request path with every parameter URL-encoded into its placeholder.
     */
    public function path(string ...$parameters): string
    {
        return sprintf($this->value, ...array_map(rawurlencode(...), $parameters));
    }
}
