<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\CheckoutStartResult;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\Exception\CheckoutIssueException;
use PhPicnic\LazyLoginApi;

/**
 * Start the checkout. The modification timestamp must equal the current cart's, or Picnic rejects it. May throw a CheckoutIssueException; retry with its resolve key.
 */
final readonly class StartCheckout
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @param list<string>|null $outOfStockArticleIds
     *
     * @throws CheckoutIssueException when the cart has issues, such as an age check
     */
    public function execute(int $cartModificationTimestamp, ?array $outOfStockArticleIds = null, ?string $resolveKey = null): CheckoutStartResult
    {
        $payload = ['mts' => $cartModificationTimestamp, 'oos_article_ids' => $outOfStockArticleIds];
        if ($resolveKey !== null && $resolveKey !== '') {
            $payload['resolve_key'] = $resolveKey;
        }

        return CheckoutStartResult::fromArray($this->api->post(ApiEndpoint::CHECKOUT_START->path(), $payload));
    }
}
