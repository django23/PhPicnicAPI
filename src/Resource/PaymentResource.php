<?php

declare(strict_types=1);

namespace PhPicnic\Resource;

use InvalidArgumentException;
use PhPicnic\Action\FetchPaymentProfile;
use PhPicnic\Action\FetchWalletTransactionDetails;
use PhPicnic\Action\FetchWalletTransactions;
use PhPicnic\Dto\WalletTransaction;
use PhPicnic\LazyLoginApi;

/**
 * Payment profile and wallet: `$picnic->payments()`.
 */
final readonly class PaymentResource
{
    private FetchPaymentProfile $fetchPaymentProfile;

    private FetchWalletTransactions $fetchWalletTransactions;

    private FetchWalletTransactionDetails $fetchWalletTransactionDetails;

    public function __construct(LazyLoginApi $api)
    {
        $this->fetchPaymentProfile = new FetchPaymentProfile($api);
        $this->fetchWalletTransactions = new FetchWalletTransactions($api);
        $this->fetchWalletTransactionDetails = new FetchWalletTransactionDetails($api);
    }

    /**
     * @return array<mixed>
     */
    public function fetchProfile(): array
    {
        return $this->fetchPaymentProfile->execute();
    }

    /**
     * @return list<WalletTransaction>
     *
     * @throws InvalidArgumentException when the page number is below 1
     */
    public function fetchWalletTransactions(int $pageNumber = 1): array
    {
        return $this->fetchWalletTransactions->execute($pageNumber);
    }

    /**
     * @return array<mixed>
     */
    public function fetchWalletTransactionDetails(string $transactionId): array
    {
        return $this->fetchWalletTransactionDetails->execute($transactionId);
    }
}
