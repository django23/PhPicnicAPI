<?php

declare(strict_types=1);

namespace PhPicnic\Resource;

use InvalidArgumentException;
use PhPicnic\Action\CancelDelivery;
use PhPicnic\Action\FetchAllDeliveries;
use PhPicnic\Action\FetchAvailableDeliverySlots;
use PhPicnic\Action\FetchCurrentDeliveries;
use PhPicnic\Action\FetchDeliveryById;
use PhPicnic\Action\FetchDeliveryDriverPosition;
use PhPicnic\Action\FetchDeliveryRoutingScenario;
use PhPicnic\Action\FetchPage;
use PhPicnic\Action\RateDelivery;
use PhPicnic\Action\ResendDeliveryInvoiceEmail;
use PhPicnic\Dto\Delivery;
use PhPicnic\Dto\DeliverySlot;
use PhPicnic\Dto\UiTree;
use PhPicnic\Enum\PageId;
use PhPicnic\LazyLoginApi;

/**
 * Deliveries and delivery slots: `$picnic->deliveries()`.
 */
final readonly class DeliveryResource
{
    private FetchAvailableDeliverySlots $fetchAvailableDeliverySlots;

    private FetchDeliveryById $fetchDeliveryById;

    private FetchAllDeliveries $fetchAllDeliveries;

    private FetchCurrentDeliveries $fetchCurrentDeliveries;

    private FetchDeliveryRoutingScenario $fetchDeliveryRoutingScenario;

    private FetchDeliveryDriverPosition $fetchDeliveryDriverPosition;

    private CancelDelivery $cancelDelivery;

    private RateDelivery $rateDelivery;

    private ResendDeliveryInvoiceEmail $resendDeliveryInvoiceEmail;

    private FetchPage $fetchPage;

    public function __construct(LazyLoginApi $api)
    {
        $this->fetchAvailableDeliverySlots = new FetchAvailableDeliverySlots($api);
        $this->fetchDeliveryById = new FetchDeliveryById($api);
        $this->fetchAllDeliveries = new FetchAllDeliveries($api);
        $this->fetchCurrentDeliveries = new FetchCurrentDeliveries($api);
        $this->fetchDeliveryRoutingScenario = new FetchDeliveryRoutingScenario($api);
        $this->fetchDeliveryDriverPosition = new FetchDeliveryDriverPosition($api);
        $this->cancelDelivery = new CancelDelivery($api);
        $this->rateDelivery = new RateDelivery($api);
        $this->resendDeliveryInvoiceEmail = new ResendDeliveryInvoiceEmail($api);
        $this->fetchPage = new FetchPage($api);
    }

    /**
     * @return list<DeliverySlot>
     */
    public function fetchAvailableSlots(): array
    {
        return $this->fetchAvailableDeliverySlots->execute();
    }

    public function fetchById(string $deliveryId): Delivery
    {
        return $this->fetchDeliveryById->execute($deliveryId);
    }

    /**
     * @return list<Delivery>
     */
    public function fetchAll(): array
    {
        return $this->fetchAllDeliveries->execute();
    }

    /**
     * @return list<Delivery>
     */
    public function fetchCurrent(): array
    {
        return $this->fetchCurrentDeliveries->execute();
    }

    public function fetchRoutingScenario(string $deliveryId): UiTree
    {
        return $this->fetchDeliveryRoutingScenario->execute($deliveryId);
    }

    public function fetchDriverPosition(string $deliveryId): UiTree
    {
        return $this->fetchDeliveryDriverPosition->execute($deliveryId);
    }

    /**
     * @return array<mixed>
     */
    public function cancel(string $deliveryId): array
    {
        return $this->cancelDelivery->execute($deliveryId);
    }

    /**
     * @throws InvalidArgumentException when the rating is outside 0 to 10
     */
    public function rate(string $deliveryId, int $rating): void
    {
        $this->rateDelivery->execute($deliveryId, $rating);
    }

    public function resendInvoiceEmail(string $deliveryId): void
    {
        $this->resendDeliveryInvoiceEmail->execute($deliveryId);
    }

    public function fetchReceiptPage(string $deliveryId): UiTree
    {
        return $this->fetchPage->execute(PageId::DELIVERY_RECEIPT, ['delivery_id' => $deliveryId]);
    }
}
