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
    case SUGGEST = '/suggest?search_term=%s';
    case PAGE = '/pages/%s';
    case PAGE_TASK = '/pages/task/%s';
    case IMAGE = '/static/images/%s/%s.png';
    case USER_DEFINED_SELLABLE = '/user-defined-sellable/%s';
    case CART = '/cart';
    case CART_ADD_PRODUCT = '/cart/add_product';
    case CART_ADD_PRODUCTS = '/cart/products/add';
    case CART_REMOVE_PRODUCT = '/cart/remove_product';
    case CART_REMOVE_GROUP = '/cart/remove_group';
    case CART_CLEAR = '/cart/clear';
    case CART_SET_DELIVERY_SLOT = '/cart/set_delivery_slot';
    case CART_DELIVERY_SLOTS = '/cart/delivery_slots';
    case USER_SLOT_MINIMUM_ORDER_VALUE = '/user-slot-minimum-order-value/minimum';
    case CHECKOUT_START = '/cart/checkout/start';
    case CHECKOUT_INITIATE_PAYMENT = '/cart/checkout/initiate_payment';
    case CHECKOUT_TRANSACTION_STATUS = '/cart/checkout/%s/status';
    case CHECKOUT_CANCEL = '/cart/checkout/cancel';
    case CHECKOUT_ORDER_CONFIRM = '/cart/checkout/order/%s/confirm';
    case CHECKOUT_ORDER_STATUS = '/cart/checkout/order/%s/status';
    case DELIVERY = '/deliveries/%s';
    case DELIVERY_SCENARIO = '/deliveries/%s/scenario';
    case DELIVERY_POSITION = '/deliveries/%s/position';
    case DELIVERIES_SUMMARY = '/deliveries/summary';
    case DELIVERY_CANCEL = '/order/delivery/%s/cancel';
    case DELIVERY_RATING = '/deliveries/%s/rating';
    case DELIVERY_RESEND_INVOICE = '/deliveries/%s/resend_invoice_email';
    case PAYMENT_PROFILE = '/payment-profile';
    case WALLET_TRANSACTIONS = '/wallet/transactions';
    case WALLET_TRANSACTION = '/wallet/transactions/%s';
    case USER_LOGOUT = '/user/logout';
    case PHONE_VERIFICATION_GENERATE = '/user/phone_verification/generate';
    case PHONE_VERIFICATION_VERIFY = '/user/phone_verification/verify';
    case USER_INFO = '/user-info';
    case PROFILE_MENU = '/profile-menu?fetch_mgm=true';
    case USER_SUGGESTION = '/user/suggestion';
    case PUSH_REGISTER = '/user/device/register_push';
    case UPDATE_CHECK = '/update_check';
    case CONSENT_SETTINGS_PAGE = '/consents/settings-page';
    case CONSENT_GENERAL_SETTINGS_PAGE = '/consents/general/settings-page';
    case CONSENTS = '/consents';
    case CONSENTS_GENERAL = '/consents/general';
    case CS_CONTACT_INFO = '/cs-contact-info';
    case MESSAGES = '/messages';
    case REMINDERS = '/reminders';
    case PARCELS = '/parcels';
    case ONBOARDING_HOUSEHOLD = '/user-onboarding/household-details';
    case ONBOARDING_BUSINESS = '/user-onboarding/business-details';
    case ONBOARDING_SUBSCRIBE_PUSH = '/user-onboarding/subscribe-push';
    case BOOTSTRAP = '/bootstrap';
    case DEEPLINK_RESOLVE = '/deeplink/resolve';
    case CONTENT_FAQ = '/content/faq';
    case CONTENT_SEARCH_EMPTY_STATE = '/content/search_empty_state';

    /**
     * The request path with every parameter URL-encoded into its placeholder.
     */
    public function path(string ...$parameters): string
    {
        return sprintf($this->value, ...array_map(rawurlencode(...), $parameters));
    }
}
