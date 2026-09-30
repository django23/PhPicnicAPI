<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Details of one wallet transaction, including the items that were paid for.
 */
final readonly class FetchWalletTransactionDetails
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @return array<mixed>
     */
    public function execute(string $transactionId): array
    {
        return $this->api->get(ApiEndpoint::WALLET_TRANSACTION->path($transactionId));
    }
}
