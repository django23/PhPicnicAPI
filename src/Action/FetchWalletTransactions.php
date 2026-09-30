<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use InvalidArgumentException;
use PhPicnic\Dto\PayloadReader;
use PhPicnic\Dto\WalletTransaction;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * One page of wallet transactions (1-based).
 */
final readonly class FetchWalletTransactions
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @return list<WalletTransaction>
     *
     * @throws InvalidArgumentException when the page number is below 1
     */
    public function execute(int $pageNumber = 1): array
    {
        if ($pageNumber < 1) {
            throw new InvalidArgumentException('Pages start at 1.');
        }

        return PayloadReader::hydrateList(
            $this->api->post(ApiEndpoint::WALLET_TRANSACTIONS->path(), ['page_number' => $pageNumber]),
            WalletTransaction::fromArray(...),
        );
    }
}
