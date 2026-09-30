<?php

declare(strict_types=1);

namespace PhPicnic\Resource;

use PhPicnic\Action\CancelCheckout;
use PhPicnic\Action\ConfirmOrder;
use PhPicnic\Action\FetchCheckoutTransactionStatus;
use PhPicnic\Action\FetchOrderStatus;
use PhPicnic\Action\InitiatePayment;
use PhPicnic\Action\StartCheckout;
use PhPicnic\Dto\CheckoutStartResult;
use PhPicnic\Dto\CheckoutStatus;
use PhPicnic\Dto\OrderConfirmation;
use PhPicnic\Dto\PaymentInitiation;
use PhPicnic\Exception\CheckoutIssueException;
use PhPicnic\LazyLoginApi;

/**
 * Checkout and payment: `$picnic->checkout()`. Flow: read the cart (for its modification timestamp), start, initiate the payment, poll the transaction, then confirm the order.
 */
final readonly class CheckoutResource
{
    private StartCheckout $startCheckout;

    private InitiatePayment $initiatePayment;

    private FetchCheckoutTransactionStatus $fetchCheckoutTransactionStatus;

    private CancelCheckout $cancelCheckout;

    private ConfirmOrder $confirmOrder;

    private FetchOrderStatus $fetchOrderStatus;

    public function __construct(LazyLoginApi $api)
    {
        $this->startCheckout = new StartCheckout($api);
        $this->initiatePayment = new InitiatePayment($api);
        $this->fetchCheckoutTransactionStatus = new FetchCheckoutTransactionStatus($api);
        $this->cancelCheckout = new CancelCheckout($api);
        $this->confirmOrder = new ConfirmOrder($api);
        $this->fetchOrderStatus = new FetchOrderStatus($api);
    }

    /**
     * @param list<string>|null $outOfStockArticleIds
     *
     * @throws CheckoutIssueException when the cart has issues, such as an age check
     */
    public function start(int $cartModificationTimestamp, ?array $outOfStockArticleIds = null, ?string $resolveKey = null): CheckoutStartResult
    {
        return $this->startCheckout->execute($cartModificationTimestamp, $outOfStockArticleIds, $resolveKey);
    }

    public function initiatePayment(string $orderId, string $appReturnUrl): PaymentInitiation
    {
        return $this->initiatePayment->execute($orderId, $appReturnUrl);
    }

    public function fetchTransactionStatus(string $transactionId): CheckoutStatus
    {
        return $this->fetchCheckoutTransactionStatus->execute($transactionId);
    }

    public function cancel(string $transactionId): void
    {
        $this->cancelCheckout->execute($transactionId);
    }

    public function confirmOrder(string $orderId): OrderConfirmation
    {
        return $this->confirmOrder->execute($orderId);
    }

    public function fetchOrderStatus(string $orderId): CheckoutStatus
    {
        return $this->fetchOrderStatus->execute($orderId);
    }
}
